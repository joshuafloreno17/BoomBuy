<?php

namespace Tests\Feature;

use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/** Messages the shop sends by itself: auto-replies, order updates, and cart suggestions. */
class ChatAutomationTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    private function autoReplies(int $sellerId, int $buyerId): int
    {
        return Message::where('sender_id', $sellerId)->where('recipient_id', $buyerId)->where('kind', Message::AUTO_REPLY)->count();
    }

    public function test_buyer_gets_the_default_auto_reply_once_and_it_is_not_unread(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller('shoes', 'Stride Co');

        $this->actingAsUser($buyer)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Hello, where is my order?'])
            ->assertOk()
            ->assertJsonPath('auto_reply.kind', 'auto_reply')
            ->assertJsonPath('auto_reply.mine', false)
            ->assertJsonPath('auto_reply.text', 'Hi! Thanks for messaging Stride Co 😊 We got your message and will reply as soon as we can.');

        // A second message soon after doesn't repeat it.
        $this->actingAsUser($buyer)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Hello?'])
            ->assertOk()
            ->assertJsonPath('auto_reply', null);

        $this->assertSame(1, $this->autoReplies($seller->id, $buyer->id));

        // It shows in the buyer's chat but adds nothing to their unread count.
        $this->assertSame(0, Message::where('recipient_id', $buyer->id)->whereNull('read_at')->count());
        $this->actingAsUser($buyer)->get(route('messages.thread', $seller->id))->assertOk()->assertSee('Auto-reply');

        // After the window it may reply again.
        $this->travel(13)->hours();
        $this->actingAsUser($buyer)->postJson(route('messages.store', $seller->id), ['message' => 'Still there?']);
        $this->assertSame(2, $this->autoReplies($seller->id, $buyer->id));
    }

    public function test_no_auto_reply_when_the_seller_replied_or_turned_it_off(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        Message::create(['sender_id' => $seller->id, 'recipient_id' => $buyer->id, 'message' => 'Hi, how can I help?']);

        $this->actingAsUser($buyer)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Is this available?'])
            ->assertJsonPath('auto_reply', null);

        // Sellers writing to buyers never trigger one.
        $this->flushSession();
        $this->actingAsUser($seller)
            ->postJson(route('messages.store', $buyer->id), ['message' => 'Yes it is'])
            ->assertJsonPath('auto_reply', null);

        // Turned off from the profile page.
        $other = $this->makeUser();
        $this->actingAsUser($seller)
            ->post(route('seller.autoreply.update'), ['auto_reply_enabled' => '0', 'auto_reply_message' => ''])
            ->assertSessionHas('success');

        $this->flushSession();
        $this->actingAsUser($other)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Hello'])
            ->assertJsonPath('auto_reply', null);
        $this->assertSame(0, $this->autoReplies($seller->id, $other->id));
    }

    public function test_seller_can_write_their_own_auto_reply(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        $this->actingAsUser($seller)->get(route('seller.profile'))->assertOk()->assertSee('Chat Auto-reply');

        $this->actingAsUser($seller)
            ->post(route('seller.autoreply.update'), ['auto_reply_enabled' => '1', 'auto_reply_message' => 'Back at 9 AM, salamat!'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('seller_applications', ['user_id' => $seller->id, 'auto_reply_message' => 'Back at 9 AM, salamat!']);

        $this->flushSession();
        $this->actingAsUser($buyer)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Hi'])
            ->assertJsonPath('auto_reply.text', 'Back at 9 AM, salamat!');

        // Too long is rejected.
        $this->flushSession();
        $this->actingAsUser($seller)
            ->post(route('seller.autoreply.update'), ['auto_reply_enabled' => '1', 'auto_reply_message' => str_repeat('a', 301)])
            ->assertSessionHasErrors('auto_reply_message');
    }

    public function test_order_changes_are_posted_in_the_chat(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $order = $this->makeOrder($buyer, $this->makeProduct($seller));

        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Processing']);
        $this->actingAsUser($seller)->post(route('seller.order.status', $order), ['status' => 'Ready for Pickup']);

        $update = Message::where('kind', Message::ORDER_UPDATE)->sole();
        $this->assertSame($seller->id, (int) $update->sender_id);
        $this->assertSame($buyer->id, (int) $update->recipient_id);
        $this->assertSame($order, (int) $update->order_id);
        $this->assertStringContainsString('ready for pickup', $update->message);
        $this->assertNotNull($update->read_at);

        // Out for delivery + delivered, from the delivery rider.
        $rider = $this->makeRider();
        DB::table('orders')->where('id', $order)->update(['status' => 'Assigned for Delivery', 'delivery_rider_id' => $rider->id]);
        $this->flushSession();
        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order), ['status' => 'Out for Delivery']);
        $this->actingAsUser($rider)->post(route('rider.delivery.status', $order), ['status' => 'Delivered']);

        $this->assertSame(3, Message::where('kind', Message::ORDER_UPDATE)->where('order_id', $order)->count());

        // The buyer sees the order card, linked to their orders page.
        $this->flushSession();
        $this->actingAsUser($buyer)
            ->get(route('messages.thread', $seller->id))
            ->assertOk()
            ->assertSee('Order update')
            ->assertSee('Order #' . $order)
            ->assertSee(route('buyer.orders') . '#order-' . $order, false);

        // A buyer's cancellation is posted too.
        $second = $this->makeOrder($buyer, $this->makeProduct($seller));
        $this->actingAsUser($buyer)->post(route('buyer.order.cancel', $second), ['cancel_reason' => \App\Support\CodPolicy::CANCEL_REASONS[0]]);
        $this->assertDatabaseHas('messages', ['order_id' => $second, 'kind' => Message::ORDER_UPDATE]);
    }

    public function test_placing_an_order_posts_a_thank_you_update(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $product = $this->makeProduct($seller, ['stock' => 5]);

        $this->actingAsUser($buyer, ['cart' => ["{$product->id}:0" => 1]])
            ->post('/checkout/place-order', [
                'address' => '1 Rizal St, Cebu City',
                'phone' => '09171234567',
                'payment' => 'Cash on Delivery',
            ]);

        $this->assertDatabaseHas('messages', ['sender_id' => $seller->id, 'recipient_id' => $buyer->id, 'kind' => Message::ORDER_UPDATE]);
    }

    public function test_chat_with_a_shop_suggests_its_items_from_the_cart(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();
        $other = $this->makeSeller('shoes', 'Other Shop');
        $inCart = $this->makeProduct($seller, ['name' => 'Canvas Tote']);
        $notInCart = $this->makeProduct($seller, ['name' => 'Leather Wallet']);
        $otherShop = $this->makeProduct($other, ['name' => 'Foreign Cap']);

        $this->actingAsUser($buyer, ['cart' => ["{$inCart->id}:0" => 1, "{$otherShop->id}:0" => 1]])
            ->get(route('messages.thread', $seller->id))
            ->assertOk()
            ->assertSee('id="chatSuggest"', false)
            ->assertSee('&quot;name&quot;:&quot;Canvas Tote', false)
            ->assertDontSee('&quot;name&quot;:&quot;Leather Wallet', false)
            ->assertDontSee('&quot;name&quot;:&quot;Foreign Cap', false);

        // Nothing of the shop in the cart (and no past orders): no suggestions.
        $this->flushSession();
        $this->actingAsUser($buyer, ['cart' => []])
            ->get(route('messages.thread', $seller->id))
            ->assertOk()
            ->assertDontSee('id="chatSuggest"', false);

        // Falls back to the last thing ordered from the shop.
        $this->makeOrder($buyer, $notInCart, 'Delivered');
        $this->actingAsUser($buyer, ['cart' => []])
            ->get(route('messages.thread', $seller->id))
            ->assertSee('id="chatSuggest"', false)
            ->assertSee('Leather Wallet');

        // Sellers don't get suggestions.
        $this->flushSession();
        $this->actingAsUser($seller)
            ->get(route('messages.thread', $buyer->id))
            ->assertDontSee('id="chatSuggest"', false);
    }
}
