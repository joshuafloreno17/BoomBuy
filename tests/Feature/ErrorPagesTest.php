<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_pages_get_the_boombuy_404(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Go to BoomBuy');
    }

    public function test_an_expired_form_goes_back_with_a_notice_instead_of_419(): void
    {
        // The real CSRF check is skipped in tests, so stand in for it.
        Route::post('/_expired-form', fn () => abort(419))->middleware('web');

        $this->from('/products')
            ->post('/_expired-form', ['name' => 'Juan', 'password' => 'secret123'])
            ->assertRedirect('/products')
            ->assertSessionHas('bb_notice')
            ->assertSessionHasInput('name', 'Juan')
            ->assertSessionMissing('_old_input.password');

        // The next page shows it.
        $this->get('/products')->assertSee('Your session timed out', false);
    }

    public function test_ajax_requests_still_get_a_plain_419(): void
    {
        Route::post('/_expired-form', fn () => abort(419))->middleware('web');

        $this->postJson('/_expired-form')->assertStatus(419);
    }
}
