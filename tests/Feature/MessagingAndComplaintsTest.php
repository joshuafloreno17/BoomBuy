<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\BuildsBoomBuyData;
use Tests\TestCase;

class MessagingAndComplaintsTest extends TestCase
{
    use RefreshDatabase;
    use BuildsBoomBuyData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_guests_cannot_use_messages(): void
    {
        $this->get(route('messages.index'))->assertRedirect(route('login'));
    }

    public function test_buyer_messages_a_seller_and_reading_marks_it_read(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        $this->actingAsUser($buyer)
            ->post(route('messages.store', $seller->id), ['message' => 'Is this available in size 9?'])
            ->assertRedirect(route('messages.thread', $seller->id));

        $this->assertDatabaseHas('messages', [
            'sender_id' => $buyer->id,
            'recipient_id' => $seller->id,
            'message' => 'Is this available in size 9?',
            'read_at' => null,
        ]);

        $this->flushSession();

        $this->actingAsUser($seller)
            ->get(route('messages.index'))
            ->assertOk()
            ->assertSee($buyer->name);

        $this->actingAsUser($seller)
            ->get(route('messages.thread', $buyer->id))
            ->assertOk()
            ->assertSee('Is this available in size 9?');

        $this->assertNotNull(Message::first()->read_at);
    }

    public function test_inbox_shows_one_row_per_partner_with_unread_counts(): void
    {
        $buyer = $this->makeUser();
        $sellerA = $this->makeSeller();
        $sellerB = $this->makeSeller('electronics', 'Other Shop');

        Message::create(['sender_id' => $sellerA->id, 'recipient_id' => $buyer->id, 'message' => 'First']);
        Message::create(['sender_id' => $sellerA->id, 'recipient_id' => $buyer->id, 'message' => 'Latest from A']);
        Message::create(['sender_id' => $buyer->id, 'recipient_id' => $sellerB->id, 'message' => 'Hi B']);

        $response = $this->actingAsUser($buyer)->get(route('messages.index'))->assertOk();

        $conversations = $response->viewData('conversations');
        $this->assertCount(2, $conversations);

        $withA = $conversations->firstWhere('partner_id', $sellerA->id);
        $this->assertSame('Latest from A', $withA['last_message']);
        $this->assertSame(2, $withA['unread_count']);
        $this->assertSame(0, $conversations->firstWhere('partner_id', $sellerB->id)['unread_count']);
    }

    public function test_open_thread_polls_for_new_messages_and_sends_without_reload(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        $old = Message::create(['sender_id' => $seller->id, 'recipient_id' => $buyer->id, 'message' => 'Old']);
        $new = Message::create(['sender_id' => $seller->id, 'recipient_id' => $buyer->id, 'message' => 'Brand new']);

        $this->actingAsUser($buyer)
            ->getJson(route('messages.thread', $seller->id) . '?after=' . $old->id)
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.text', 'Brand new')
            ->assertJsonPath('messages.0.mine', false);

        $this->assertNotNull($new->fresh()->read_at);

        $this->actingAsUser($buyer)
            ->postJson(route('messages.store', $seller->id), ['message' => 'Sent via fetch'])
            ->assertOk()
            ->assertJsonPath('message.text', 'Sent via fetch')
            ->assertJsonPath('message.mine', true);
    }

    public function test_empty_messages_and_messages_to_yourself_are_refused(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller();

        $this->actingAsUser($buyer)
            ->post(route('messages.store', $seller->id), ['message' => ''])
            ->assertSessionHasErrors('message');

        $this->actingAsUser($buyer)
            ->post(route('messages.store', $buyer->id), ['message' => 'Hello me'])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('messages', 0);
    }

    public function test_buyer_files_a_complaint_about_their_own_order(): void
    {
        $buyer = $this->makeUser();
        $orderId = $this->makeOrder($buyer, $this->makeProduct($this->makeSeller()));

        $this->actingAsUser($buyer)
            ->post(route('complaints.store'), [
                'subject' => 'Item arrived damaged',
                'description' => 'The box was crushed.',
                'order_id' => $orderId,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('complaints', [
            'complainant_id' => $buyer->id,
            'order_id' => $orderId,
            'status' => 'Pending',
        ]);
    }

    public function test_cannot_complain_about_someone_elses_order(): void
    {
        $buyer = $this->makeUser();
        $stranger = $this->makeUser();
        $orderId = $this->makeOrder($stranger, $this->makeProduct($this->makeSeller()));

        $this->actingAsUser($buyer)
            ->post(route('complaints.store'), [
                'subject' => 'Not mine',
                'description' => 'Trying another buyer\'s order.',
                'order_id' => $orderId,
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('complaints', 0);
    }

    public function test_admin_resolves_a_complaint_and_the_buyer_is_told(): void
    {
        $buyer = $this->makeUser();

        $complaint = Complaint::create([
            'complainant_id' => $buyer->id,
            'complainant_role' => 'buyer',
            'subject' => 'Late delivery',
            'description' => 'Still waiting.',
            'status' => 'Pending',
        ]);

        $this->actingAsAdmin()
            ->post(route('admin.complaints.update', $complaint->id), ['status' => 'Bogus'])
            ->assertSessionHas('error');

        $this->actingAsAdmin()
            ->post(route('admin.complaints.update', $complaint->id), [
                'status' => 'Resolved',
                'admin_notes' => 'Courier contacted.',
            ])
            ->assertSessionHas('success');

        $complaint->refresh();
        $this->assertSame('Resolved', $complaint->status);
        $this->assertNotNull($complaint->resolved_at);
        $this->assertDatabaseHas('notifications', ['user_id' => $buyer->id, 'title' => 'Complaint Update']);
    }

    public function test_sellers_show_up_by_shop_name_and_seen_is_reported(): void
    {
        $buyer = $this->makeUser();
        $seller = $this->makeSeller('shoes', 'Stride Footwear');

        $mine = Message::create(['sender_id' => $buyer->id, 'recipient_id' => $seller->id, 'message' => 'Hi shop']);

        $this->actingAsUser($buyer)->get(route('messages.index'))
            ->assertOk()
            ->assertSee('Stride Footwear');

        // Not read yet.
        $this->actingAsUser($buyer)->getJson(route('messages.thread', $seller->id) . '?after=' . $mine->id)
            ->assertJsonPath('seen_up_to', 0);

        $mine->update(['read_at' => now()]);

        $this->actingAsUser($buyer)->getJson(route('messages.thread', $seller->id) . '?after=' . $mine->id)
            ->assertJsonPath('seen_up_to', $mine->id);
    }

    public function test_only_the_admin_can_search_people_to_message(): void
    {
        $this->makeSeller('shoes', 'Stride Footwear');

        $this->actingAsUser($this->makeUser())
            ->getJson(route('messages.recipients', ['q' => 'stride']))
            ->assertForbidden();

        $this->flushSession();

        $this->actingAsAdmin()
            ->getJson(route('messages.recipients', ['q' => 'stride']))
            ->assertOk()
            ->assertJsonPath('people.0.name', 'Stride Footwear')
            ->assertJsonPath('people.0.role', 'seller');
    }

    public function test_conversation_list_filters_unread(): void
    {
        $buyer = $this->makeUser();
        $a = $this->makeSeller('shoes', 'Alpha Shoes');
        $b = $this->makeSeller('electronics', 'Beta Tech');

        Message::create(['sender_id' => $a->id, 'recipient_id' => $buyer->id, 'message' => 'Unread one']);
        Message::create(['sender_id' => $b->id, 'recipient_id' => $buyer->id, 'message' => 'Read one', 'read_at' => now()]);

        $this->actingAsUser($buyer)->get(route('messages.index', ['filter' => 'unread']))
            ->assertSee('Alpha Shoes')
            ->assertDontSee('Beta Tech');
    }

    public function test_new_message_suggests_people_before_and_after_one_letter(): void
    {
        $this->makeSeller('shoes', 'Stride Footwear');
        $this->makeSeller('electronics', 'Volt Tech');

        // Nothing typed yet: suggestions straight away, sellers first.
        $this->actingAsAdmin()->getJson(route('messages.recipients'))
            ->assertOk()
            ->assertJsonCount(2, 'people')
            ->assertJsonPath('people.0.role', 'seller');

        // One letter is enough; names starting with it come first.
        $this->actingAsAdmin()->getJson(route('messages.recipients', ['q' => 'v']))
            ->assertOk()
            ->assertJsonPath('people.0.name', 'Volt Tech');
    }
}
