<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use App\Models\StudentClaim;
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

    public function test_request_creates_pending_claim_and_approval_deducts_requested_quantity(): void
    {
        // TEST A: available 14, request 2, 100 pts per piece
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

        $claim = StudentClaim::where('student_id', $student->id)
            ->where('claim_item_id', $item->id)
            ->where('status', 'Pending')
            ->first();

        $this->assertNotNull($claim);
        $this->assertSame(2, (int) $claim->quantity);
        $this->assertSame(200, (int) $claim->points_deducted);
        $this->assertSame(500, (int) $claim->points_before);
        $this->assertSame(300, (int) $claim->points_after);

        // Stock and student points remain untouched while the claim is pending.
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);

        $approve = $this->actingAs($admin)->patch(route('claims.approve', $claim->id));
        $approve->assertSessionHas('success');

        // Approval deducts the requested quantity (2), not just 1.
        $this->assertSame(12, (int) $item->fresh()->quantity);
        $this->assertSame('Available', $item->fresh()->status);
        $this->assertSame(300, (int) $student->fresh()->total_points);

        $claim->refresh();
        $this->assertSame('Approved', $claim->status);
        $this->assertSame(200, (int) $claim->points_deducted);
        $this->assertSame(300, (int) $claim->points_after);
        $this->assertNotNull($claim->approved_at);
    }

    public function test_request_exceeding_available_stock_is_rejected(): void
    {
        // TEST B: available 5, request 6
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
    }

    public function test_zero_stock_item_shows_out_of_stock_with_disabled_claim_button(): void
    {
        // TEST C: available 0
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
        // TEST D: student points 50, total cost 100
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

    public function test_approving_the_same_claim_twice_deducts_only_once(): void
    {
        // TEST E
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

    public function test_competing_approvals_cannot_oversell_stock(): void
    {
        // TEST F: two pending claims compete for limited stock
        $admin = $this->createSuperAdmin();
        $studentOne = $this->createStudent(null, ['total_points' => 500]);
        $studentTwo = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 3]);

        $this->post(route('public.items.request'), [
            'lrn' => $studentOne->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
        ])->assertSessionHas('success');

        $this->post(route('public.items.request'), [
            'lrn' => $studentTwo->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 2,
        ])->assertSessionHas('success');

        $claimOne = StudentClaim::where('student_id', $studentOne->id)->where('status', 'Pending')->firstOrFail();
        $claimTwo = StudentClaim::where('student_id', $studentTwo->id)->where('status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('claims.approve', $claimOne->id))
            ->assertSessionHas('success');

        $this->assertSame(1, (int) $item->fresh()->quantity);

        $this->actingAs($admin)
            ->patch(route('claims.approve', $claimTwo->id))
            ->assertSessionHasErrors([
                'claim' => 'Not enough item stock available. Only 1 left.',
            ]);

        // No overselling, no negative inventory, second claim untouched.
        $this->assertSame(1, (int) $item->fresh()->quantity);
        $this->assertGreaterThanOrEqual(0, (int) $item->fresh()->quantity);
        $this->assertSame(300, (int) $studentOne->fresh()->total_points);
        $this->assertSame(500, (int) $studentTwo->fresh()->total_points);
        $this->assertSame('Pending', $claimTwo->fresh()->status);
    }

    public function test_rejected_claim_does_not_consume_stock_or_points(): void
    {
        // Requirement 8
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 3,
        ])->assertSessionHas('success');

        $claim = StudentClaim::where('student_id', $student->id)->where('claim_item_id', $item->id)->where('status', 'Pending')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('claims.reject', $claim->id), ['rejected_reason' => 'Not enough points budget.'])
            ->assertSessionHas('success');

        $claim->refresh();
        $this->assertSame('Rejected', $claim->status);
        $this->assertSame('Not enough points budget.', $claim->rejected_reason);

        // Rejected requests keep history but consume nothing.
        $this->assertSame(14, (int) $item->fresh()->quantity);
        $this->assertSame(500, (int) $student->fresh()->total_points);
    }

    public function test_approval_blocked_when_stock_was_consumed_after_request(): void
    {
        // Requirement 9: no negative inventory at approval time
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(null, ['total_points' => 500]);
        $item = $this->makeItem(['quantity' => 14]);

        $this->post(route('public.items.request'), [
            'lrn' => $student->lrn,
            'claim_item_id' => $item->id,
            'quantity' => 4,
        ])->assertSessionHas('success');

        $claim = StudentClaim::where('student_id', $student->id)->where('claim_item_id', $item->id)->where('status', 'Pending')->firstOrFail();

        // Stock disappears after the request was made (e.g. immediate admin claim).
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
