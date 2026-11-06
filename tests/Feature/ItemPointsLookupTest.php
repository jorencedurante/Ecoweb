<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ItemPointsLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The lookup rate limiter uses the file cache store (no database
        // dependency), so isolate counters between tests.
        Cache::store('file')->flush();
    }

    private function lookup(string $lrn)
    {
        return $this->getJson(route('public.items.points', ['lrn' => $lrn]));
    }

    public function test_valid_lrn_returns_available_points(): void
    {
        $student = $this->createStudent(null, ['total_points' => 500]);

        $response = $this->lookup($student->lrn);

        $response->assertOk();
        $response->assertJson(['success' => true, 'available_points' => 500]);

        // Only the information needed for the feature is exposed.
        $this->assertCount(2, $response->json());
        $this->assertArrayNotHasKey('lrn', $response->json());
        $this->assertArrayNotHasKey('name', $response->json());
        $this->assertArrayNotHasKey('full_name', $response->json());
    }

    public function test_invalid_lrn_returns_not_found(): void
    {
        $response = $this->lookup('999999999999');

        $response->assertStatus(404);
        $response->assertJson(['success' => false, 'message' => 'Student LRN not found.']);
    }

    public function test_missing_lrn_is_validated(): void
    {
        $response = $this->getJson(route('public.items.points'));

        $response->assertStatus(422);
    }

    public function test_student_with_zero_points_returns_zero(): void
    {
        $student = $this->createStudent(null, ['total_points' => 0]);

        $this->lookup($student->lrn)
            ->assertOk()
            ->assertJson(['success' => true, 'available_points' => 0]);
    }

    public function test_archived_student_is_not_found(): void
    {
        $student = $this->createStudent(null, ['total_points' => 300, 'status' => 'Archived']);

        $this->lookup($student->lrn)->assertStatus(404);
    }

    public function test_lookup_never_modifies_points_and_reflects_claim_redemption(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = ClaimItem::create([
            'item_name' => 'Ballpen',
            'description' => 'ballpen',
            'points_required' => 100,
            'quantity' => 14,
            'status' => 'Available',
        ]);

        // Reading the balance never changes it.
        $this->lookup($student->lrn)->assertOk()->assertJson(['available_points' => 500]);
        $this->assertSame(500, (int) $student->fresh()->total_points);

        // A pending claim reserves its points immediately, so the spendable
        // balance shown drops as soon as the request is submitted.
        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
        ])->assertSessionHas('success');

        $this->lookup($student->lrn)->assertOk()->assertJson(['available_points' => 300]);

        // Approval keeps the reservation: no second deduction.
        $claim = StudentClaim::where('student_id', $student->id)->where('status', 'Pending')->firstOrFail();
        $this->actingAs($admin)->patch(route('claims.approve', $claim->id))->assertSessionHas('success');

        $this->lookup($student->lrn)->assertOk()->assertJson(['available_points' => 300]);
    }

    public function test_lookup_is_rate_limited(): void
    {
        $student = $this->createStudent(null, ['total_points' => 100]);

        for ($i = 0; $i < 30; $i++) {
            $this->lookup($student->lrn)->assertOk();
        }

        $this->lookup($student->lrn)
            ->assertStatus(429)
            ->assertJson(['success' => false]);
    }

    public function test_every_item_card_has_its_own_points_lookup_panel(): void
    {
        ClaimItem::create([
            'item_name' => 'Ballpen',
            'description' => 'ballpen',
            'points_required' => 100,
            'quantity' => 14,
            'status' => 'Available',
        ]);
        ClaimItem::create([
            'item_name' => 'Coupon',
            'description' => 'coupon',
            'points_required' => 50,
            'quantity' => 6,
            'status' => 'Available',
        ]);

        $response = $this->get(route('public.items'));
        $html = $response->getContent();

        $response->assertOk();

        // One balance panel per card and per LRN input, with no duplicate ids —
        // regardless of how many items already exist in the database.
        $formCount = preg_match_all('/class="reward-claim-form"/', $html);
        $panelCount = preg_match_all('/class="points-balance-panel js-points-panel"/', $html);
        $idCount = preg_match_all('/id="claim-lrn-(\d+)"/', $html, $idMatches);

        $this->assertGreaterThanOrEqual(2, $formCount); // our Ballpen + Coupon at minimum
        $this->assertSame($formCount, $panelCount);
        $this->assertSame($formCount, $idCount);
        $this->assertSame($idCount, count(array_unique($idMatches[1])));

        // Lookup URL comes from a Laravel-generated route, never a hardcoded host.
        $response->assertSee(json_encode(route('public.items.points')), false);
        $this->assertStringEndsWith('/items/points', route('public.items.points'));
    }
}
