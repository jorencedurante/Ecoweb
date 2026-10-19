<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class ItemClaimFlowTest extends TestCase
{
    private function makeItem(array $overrides = []): ClaimItem
    {
        return ClaimItem::create(array_merge([
            'item_name' => 'Ballpen',
            'description' => 'ballpen',
            'points_required' => 100,
            'quantity' => 14,
            'status' => 'Available',
        ], $overrides));
    }

    private function makeLegacyPendingClaim($student, $item, int $quantity, array $overrides = []): StudentClaim
    {
        // A claim created before the reservation workflow existed:
        // points and stock were never deducted up front.
        return StudentClaim::create(array_merge([
            'student_id' => $student->id,
            'claim_item_id' => $item->id,
            'quantity' => $quantity,
            'item_name' => $item->item_name,
            'points_deducted' => $item->points_required * $quantity,
            'points_before' => $student->total_points,
            'points_after' => $student->total_points - ($item->points_required * $quantity),
            'claim_date' => now()->toDateString(),
            'remarks' => 'Legacy pending claim',
            'status' => 'Pending',
            'is_reserved' => false,
        ], $overrides));
    }

    public function test_item_card_shows_required_points_and_available_pieces(): void
    {
        $this->makeItem(['quantity' => 14, 'points_required' => 100]);

        $response = $this->get(route('public.items'));

        $response->assertOk();
        $response->assertSee('Ballpen');
        $response->assertSee('Required Points');
        $response->assertSee('<strong>100 Points</strong>', false);
        $response->assertSee('14 Pieces Available');
    }

    public function test_request_reserves_points_and_stock_then_approval_keeps_them(): void
    {
        // TEST A + TEST B: request 2 of 14 pieces at 100 pts each from 500 pts.
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14, 'points_required' => 100]);

        $response = $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
        ]);

        $response->assertRedirect(route('public.items'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString(
            'Claim request submitted! 200 points and 2 item pieces have been reserved while awaiting admin approval.',
            session('success')
        );

        $claim = StudentClaim::where('student_id', $student->id)
            ->where('claim_item_id', $item->id)
            ->where('status', 'Pending')
            ->first();

        $this->assertNotNull($claim);
        $this->assertSame(2, (int) $claim->quantity);
        $this->assertSame(200, (int) $claim->points_deducted);
        $this->assertSame(500, (int) $claim->points_before);
        $this->assertSame(300, (int) $claim->points_after);
        $this->assertTrue((bool) $claim->is_reserved);

        // Points and pieces are reserved immediately while pending.
        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame('Available', $item->fresh()->status);
        $this->assertSame(300, (int) $student->fresh()->total_points);

        $approve = $this->actingAs($admin)->patch(route('claims.approve', $claim->id));
        $approve->assertSessionHas('success');

        // Approval keeps the reservation: no second deduction of any kind.
        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame('Available', $item->fresh()->status);
        $this->assertSame(300, (int) $student->fresh()->total_points);

        $claim->refresh();
        $this->assertSame('Approved', $claim->status);
        $this->assertSame(200, (int) $claim->points_deducted);
        $this->assertSame(500, (int) $claim->points_before);
        $this->assertSame(300, (int) $claim->points_after);
        $this->assertNotNull($claim->approved_at);
    }

    public function test_request_exceeding_available_stock_is_rejected(): void
    {
        // TEST F: available 5, request 6
        $student = $this->createStudent(null, ['total_points' => 1000]);
        $item = $this->makeItem(['quantity' => 5]);

        $response = $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 6,
        ]);

        $response->assertRedirect(route('public.items'));
        $response->assertSessionHasErrors('quantity');
        $this->assertStringContainsString(
            'Only 5 piece(s) left',
            session('errors')->first('quantity')
        );

        $this->assertSame(0, StudentClaim::where('claim_item_id', $item->id)->count());
        $this->assertSame(5, (int) $item->fresh()->quantity);
        $this->assertSame(1000, (int) $student->fresh()->total_points);
    }

    public function test_zero_stock_item_shows_out_of_stock_with_disabled_claim_button(): void
    {
        // Available 0
        $this->makeItem(['quantity' => 0, 'status' => 'Available']);

        $response = $this->get(route('public.items'));

        $response->assertOk();
        $response->assertSee('Ballpen');
        $response->assertSee('Out of Stock');
        $response->assertSee('<strong>100 Points</strong>', false);

        $this->assertMatchesRegularExpression(
            '/Request Claim for Ballpen"[^>]*\bdisabled\b/',
            $response->getContent()
        );
    }

    public function test_request_is_rejected_when_student_has_insufficient_points(): void
    {
        // TEST E: student points 50, total cost 100
        $student = $this->createStudent(null, ['total_points' => 50]);
        $item = $this->makeItem(['quantity' => 14, 'points_required' => 100]);

        $response = $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect(route('public.items'));
        $response->assertSessionHasErrors([
            'claim_item_id' => 'Insufficient points. You need 100 points for this request.',
        ]);

        $this->assertSame(0, StudentClaim::where('claim_item_id', $item->id)->count());
        $this->assertSame(50, (int) $student->fresh()->total_points);
        $this->assertSame(14, (int) $item->fresh()->quantity);
    }

    public function test_simultaneous_requests_cannot_oversell_inventory(): void
    {
        // TEST G: only 1 piece available, two students request it.
        $studentOne = $this->createStudent(null, ['total_points' => 500]);
        $studentTwo = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 1]);

        $this->post(route('public.items.request'), [
            'lrn' => $studentOne->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 1,
        ])->assertSessionHas('success');

        $second = $this->post(route('public.items.request'), [
            'lrn' => $studentTwo->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $second->assertRedirect(route('public.items'));
        $second->assertSessionHasErrors();

        // Only one reservation exists and inventory is never negative.
        $this->assertSame(1, StudentClaim::count());
        $this->assertSame(0, StudentClaim::where('student_id', $studentTwo->id)->count());
        $this->assertSame(0, (int) $item->fresh()->quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->fresh()->quantity);
        $this->assertSame('Unavailable', $item->fresh()->status);
        $this->assertSame(400, (int) $studentOne->fresh()->total_points);
        $this->assertSame(500, (int) $studentTwo->fresh()->total_points);
    }

    public function test_form_submitted_twice_creates_only_one_reservation(): void
    {
        // TEST H: the same form is submitted twice quickly (double click).
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);
        $token = (string) Str::uuid();

        $payload = [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
            'request_token' => $token,
        ];

        $this->post(route('public.items.request'), $payload)
            ->assertSessionHas('success');

        $this->post(route('public.items.request'), $payload)
            ->assertSessionHas('success');

        $this->assertSame(1, StudentClaim::where('student_id', $student->id)->count());
        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $student->fresh()->total_points);
    }

    public function test_second_request_is_blocked_while_a_pending_claim_exists(): void
    {
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 1,
        ])->assertSessionHas('success');

        $second = $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 1,
        ]);

        $second->assertSessionHasErrors();

        $this->assertSame(1, StudentClaim::where('student_id', $student->id)->count());
        $this->assertSame(13, (int) $item->fresh()->quantity);
        $this->assertSame(400, (int) $student->fresh()->total_points);
    }

    public function test_failed_claim_creation_rolls_back_point_and_stock_deductions(): void
    {
        // TEST I: a failure while creating the claim must undo everything.
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        StudentClaim::creating(function (): void {
            throw new RuntimeException('Simulated claim creation failure.');
        });

        try {
            $response = $this->post(route('public.items.request'), [
                'lrn' => $student->lrn,
                'claim_item_id' => $item->id,
                'quantity' => 2,
            ]);

            $response->assertStatus(500);
        } finally {
            StudentClaim::flushEventListeners();
        }

        $this->assertSame(0, StudentClaim::where('student_id', $student->id)->count());
        $this->assertSame(500, (int) $student->fresh()->total_points);
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame('Available', $item->fresh()->status);
    }

    public function test_approving_the_same_claim_twice_deducts_only_once(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
        ])->assertSessionHas('success');

        $claim = StudentClaim::where('student_id', $student->id)->where('claim_item_id', $item->id)->where('status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('claims.approve', $claim->id))
            ->assertSessionHas('success');

        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $student->fresh()->total_points);

        $second = $this->actingAs($admin)->patch(route('claims.approve', $claim->id));
        $second->assertSessionHasErrors([
            'claim' => 'This claim request is no longer pending.',
        ]);

        // Only one stock deduction and one points deduction.
        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $student->fresh()->total_points);
        $this->assertSame('Approved', $claim->fresh()->status);
    }

    public function test_rejected_claim_restores_reserved_points_and_stock(): void
    {
        // TEST C
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 3,
        ])->assertSessionHas('success');

        // Reserved immediately while pending.
        $this->assertSame(11, (int) $item->fresh()->quantity);
        $this->assertSame(200, (int) $student->fresh()->total_points);

        $claim = StudentClaim::where('student_id', $student->id)->where('claim_item_id', $item->id)->where('status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('claims.reject', $claim->id), ['rejected_reason' => 'Not enough points budget.'])
            ->assertSessionHas('success');

        $claim->refresh();
        $this->assertSame('Rejected', $claim->status);
        $this->assertSame('Not enough points budget.', $claim->rejected_reason);

        // The original reservation is returned to the same student and item.
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);

        // The historical claim record keeps the original reservation figures.
        $this->assertSame(3, (int) $claim->quantity);
        $this->assertSame(300, (int) $claim->points_deducted);
        $this->assertSame(500, (int) $claim->points_before);
        $this->assertSame(200, (int) $claim->points_after);
    }

    public function test_rejecting_the_same_claim_twice_refunds_only_once(): void
    {
        // TEST D
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 3,
        ])->assertSessionHas('success');

        $claim = StudentClaim::where('student_id', $student->id)->where('status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('claims.reject', $claim->id))
            ->assertSessionHas('success');

        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);

        $second = $this->actingAs($admin)->patch(route('claims.reject', $claim->id));
        $second->assertSessionHasErrors([
            'claim' => 'This claim request is no longer pending.',
        ]);

        // No additional refund, no extra inventory.
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);
        $this->assertSame('Rejected', $claim->fresh()->status);
    }

    public function test_legacy_pending_claim_approval_deducts_points_and_stock_once(): void
    {
        // Existing pending claims created before the reservation workflow
        // keep their original behavior: deduction happens at approval, once.
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $claim = $this->makeLegacyPendingClaim($student, $item, 2);

        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);

        $this->actingAs($admin)
            ->patch(route('claims.approve', $claim->id))
            ->assertSessionHas('success');

        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $student->fresh()->total_points);
        $this->assertSame('Approved', $claim->fresh()->status);

        $second = $this->actingAs($admin)->patch(route('claims.approve', $claim->id));
        $second->assertSessionHasErrors([
            'claim' => 'This claim request is no longer pending.',
        ]);

        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $student->fresh()->total_points);
    }

    public function test_rejecting_a_legacy_pending_claim_does_not_refund(): void
    {
        // Nothing was ever reserved for a legacy pending claim, so rejecting
        // it must not hand out free points or inventory.
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $claim = $this->makeLegacyPendingClaim($student, $item, 2);

        $this->actingAs($admin)
            ->patch(route('claims.reject', $claim->id))
            ->assertSessionHas('success');

        $this->assertSame('Rejected', $claim->fresh()->status);
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);
    }

    public function test_legacy_pending_claim_approval_is_blocked_when_stock_is_gone(): void
    {
        // Legacy approval still validates stock before deducting.
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $claim = $this->makeLegacyPendingClaim($student, $item, 4);

        // Stock disappears after the request was made (e.g. admin claim).
        $item->update(['quantity' => 2]);

        $this->actingAs($admin)
            ->patch(route('claims.approve', $claim->id))
            ->assertSessionHasErrors([
                'claim' => 'Not enough item stock available. Only 2 left.',
            ]);

        $this->assertSame(2, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);
        $this->assertSame('Pending', $claim->fresh()->status);
    }

    public function test_items_page_shows_total_required_for_quantity(): void
    {
        $this->makeItem(['quantity' => 14, 'points_required' => 100]);

        $response = $this->get(route('public.items'));

        $response->assertOk();
        $response->assertSee('Total Required:');
        $response->assertSee('data-points="100"', false);
        $response->assertSee('data-available="14"', false);
        $response->assertSee('min="1"', false);
        $response->assertSee('max="14"', false);
        $response->assertSee('name="request_token"', false);
    }

    public function test_admin_pending_claims_list_shows_requested_quantity(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 3,
        ])->assertSessionHas('success');

        $response = $this->actingAs($admin)->get(route('claims.index'));

        $response->assertOk();
        $response->assertSee('Pending Item Claims');
        $response->assertSee('Qty');
        $response->assertSee('Ballpen');
        // Points Required column shows the stored total for this request.
        $response->assertSee('300');
    }
}
