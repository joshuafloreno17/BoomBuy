<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Sent to a rider a logistics company added: the link to set their password. */
class RiderActivationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public ?string $company,
        public string $url,
        public int $hours,
    ) {
    }

    public function build()
    {
        return $this->subject('Activate your BoomBuy rider account')
            ->view('emails.rider-activation');
    }
}
