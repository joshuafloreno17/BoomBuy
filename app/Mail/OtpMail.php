<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;
    public string $userName;

    public function __construct(string $otpCode, string $userName = 'there')
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }

    public function build()
    {
        return $this->subject('Your BoomBuy Verification Code')
            ->view('emails.otp');
    }
}