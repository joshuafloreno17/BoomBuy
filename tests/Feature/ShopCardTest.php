<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Support\SellerShop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

/**
 * The shop card on a product page: Chat Now / View Shop and the shop's
 * numbers (ratings, products, sold, joined, response rate and time).
 */
class ShopCardTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    private function message(int $from, int $to, string $at, string $kind = 'text'): void
    {
        $message = Message::create(['sender_id' => $from, 'recipient_id' => $to, 'message' => 'Hi', 'kind' => $kind]);
        $message->forceFill(['created_at' => $at])->save();
    }

    public function test_response_rate_and_time_come_from_real_chats(): void
    {
        $seller = $this->makeSeller();
        [$ana, $ben, $cy] = [$this->makeUser(), $this->makeUser(), $this->makeUser()];

        $this->assertSame(['response_rate' => null, 'response_time' => null], SellerShop::responses($seller->id));

        // Ana: answered after 10 minutes. Ben: only the auto-reply (doesn't count). Cy: answered after 3 hours.
        $this->message($ana->id, $seller->id, now()->subDays(2));
        $this->message($seller->id, $ana->id, now()->subDays(2)->addMinutes(10));
        $this->message($ben->id, $seller->id, now()->subDay());
        $this->message($seller->id, $ben->id, now()->subDay()->addSecond(), Message::AUTO_REPLY);
        $this->message($cy->id, $seller->id, now()->subHours(5));
        $this->message($seller->id, $cy->id, now()->subHours(2));

        // A chat the seller started, and one older than 90 days, are left out.
        $this->message($seller->id, $this->makeUser()->id, now()->subHour());
        $this->message($this->makeUser()->id, $seller->id, now()->subDays(120));

        $this->assertSame(['response_rate' => 67, 'response_time' => 'within hours'], SellerShop::responses($seller->id));
    }

    public function test_product_page_shows_the_shop_card(): void
    {
        $seller = $this->makeSeller('shoes', 'Sole Mates');
        $product = $this->makeProduct($seller);

        $this->get(route('product.details', $product->id))
            ->assertOk()
            ->assertSee('pd-shop-card', false)
            ->assertSee('Sole Mates')
            ->assertSee('View Shop')
            ->assertSee('Chat Now')
            ->assertSeeInOrder(['Ratings', 'Response Rate', 'Joined', 'Products', 'Response Time', 'Sold']);

        // A buyer gets Chat Now to the seller; the seller viewing their own product doesn't.
        $this->actingAsUser($this->makeUser())->get(route('product.details', $product->id))
            ->assertSee(route('messages.thread', [$seller->id, 'product' => $product->id]), false);
        $this->flushSession();
        $this->actingAsUser($seller)->get(route('product.details', $product->id))->assertDontSee('Chat Now');
    }
}
