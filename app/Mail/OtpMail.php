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
    public int $minutes;

    public function __construct(string $otpCode, string $userName = 'there', int $minutes = 10)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
        $this->minutes = $minutes;
    }

    public function build()
    {
        return $this->subject('Your BoomBuy Verification Code')
            ->view('emails.otp');
    }
}