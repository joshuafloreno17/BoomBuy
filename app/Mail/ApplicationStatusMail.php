<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $role;
    public string $status;
    public ?string $remarks;

    public function __construct(string $userName, string $role, string $status, ?string $remarks = null)
    {
        $this->userName = $userName;
        $this->role = $role;
        $this->status = $status;
        $this->remarks = $remarks;
    }

    public function build()
    {
        $subject = $this->status === 'Approved'
            ? 'Your BoomBuy ' . ucfirst($this->role) . ' Application Has Been Approved'
            : 'Update on Your BoomBuy ' . ucfirst($this->role) . ' Application';

        return $this->subject($subject)
            ->view('emails.application-status', ['action' => $this->action()]);
    }

    /**
     * Where an approved applicant goes next: logistics staff to BoomBuy
     * Logistics, riders to the app (they work there only), buyers and
     * sellers to the marketplace login.
     */
    private function action(): array
    {
        return match ($this->role) {
            'logistics' => ['label' => 'Log in to BoomBuy Logistics', 'url' => route('logistics.login')],
            'rider' => ['label' => 'Get the BoomBuy app', 'url' => route('app.download')],
            default => ['label' => 'Log in to BoomBuy', 'url' => route('login')],
        };
    }
}
