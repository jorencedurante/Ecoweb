<?php

namespace Tests\Feature;

use App\Models\ClaimItem;
use Tests\TestCase;

class ItemsQuantityDefaultTest extends TestCase
{
    public function test_every_reward_card_starts_at_zero_quantity(): void
    {
        ClaimItem::create([
            'item_name' => 'Coupon', 'description' => 'Coupon', 'points_required' => 20,
            'quantity' => 5, 'status' => 'Available',
        ]);
        ClaimItem::create([
            'item_name' => 'Pad Paper', 'description' => 'Pad Paper', 'points_required' => 200,
            'quantity' => 3, 'status' => 'Available',
        ]);

        $html = (string) $this->get(route('public.items'))->assertOk()->getContent();

        $cards = preg_match_all('/<input type="number" id="claim-qty-/', $html);
        $this->assertGreaterThanOrEqual(2, $cards);
        $this->assertSame($cards, preg_match_all('/<input type="number" id="claim-qty-[^>]*min="0"[^>]*step="1"[^>]*value="0"/', $html));
        $this->assertStringNotContainsString('value="1" required aria-label="Quantity"', $html);
        $this->assertSame($cards, substr_count($html, '<strong class="js-total-points">0</strong>'));
        $this->assertStringContainsString('min="0"', $html);
        $this->assertStringContainsString('step="1"', $html);
    }

    public function test_zero_quantity_submission_is_still_rejected_by_the_server(): void
    {
        $item = ClaimItem::create([
            'item_name' => 'Pencil', 'description' => 'Pencil', 'points_required' => 100,
            'quantity' => 10, 'status' => 'Available',
        ]);

        $this->post(route('public.items.request'), [
            'claim_item_id' => $item->id,
            'quantity' => 0,
            'lrn' => '1234567890',
            'request_token' => 'test-token-' . uniqid(),
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('student_claims', 0);
    }
}
