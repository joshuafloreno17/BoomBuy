<?php

namespace App\Providers;

use App\Support\MailCatcher;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Mail to the test sheets' @example.com accounts goes to /mailbox instead.
        Event::listen(MessageSending::class, fn (MessageSending $event) => MailCatcher::intercept($event->message) ? false : null);
    }
}
