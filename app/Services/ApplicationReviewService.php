<?php

namespace App\Services;

use App\Exceptions\ActionFailed;
use App\Mail\ApplicationStatusMail;
use App\Support\Categories;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use function Illuminate\Support\defer;

/**
 * The admin approving or rejecting a seller, buyer or logistics application.
 * The applicant gets a notification and an email (sent after the response).
 */
class ApplicationReviewService
{
    public const TYPES = ['seller', 'buyer', 'logistics'];

    /** Notification titles/texts per application type. */
    private const COPY = [
        'seller' => [
            'approved' => ['Seller Application Approved', 'Congratulations! Your seller application has been approved. You can now access your seller account.'],
            'rejected' => ['Seller Application Rejected', 'Your seller application was rejected.', 'Please review your application and try again.'],
        ],
        'buyer' => [
            'approved' => ['Account Approved', 'Congratulations! Your BoomBuy account has been approved. You can now log in.'],
            'rejected' => ['Account Application Rejected', 'Your BoomBuy account application was rejected.', 'Please contact support for more information.'],
        ],
        'logistics' => [
            'approved' => ['Logistics Application Approved', 'Congratulations! Your Logistics account has been approved. You can now log in.'],
            'rejected' => ['Logistics Application Rejected', 'Your Logistics application was rejected.', 'Please review your application and try again.'],
        ],
    ];

    public static function isType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * A seller is approved for one line of business (which decides what they may list).
     *
     * @throws ActionFailed
     */
    public function approve(string $type, int $id, ?string $businessCategory = null): string
    {
        $application = $this->pending($type, $id);

        $changes = ['status' => 'Approved', 'admin_remarks' => null, 'reviewed_at' => now(), 'updated_at' => now()];

        if ($type === 'seller') {
            $category = $businessCategory ?: $application->business_category;

            if (!is_string($category) || !array_key_exists($category, Categories::LIST)) {
                throw new ActionFailed('Please choose a valid line of business before approving.');
            }

            $changes['business_category'] = $category;
        }

        if (!$this->decide($type, $id, $changes)) {
            throw new ActionFailed('Application could not be approved.');
        }

        [$title, $text] = self::COPY[$type]['approved'];
        createNotification((int) $application->user_id, $title, $text, $type, (int) $application->id);

        $this->email($application, $type, 'Approved');

        return ucfirst($type) . ' application approved.';
    }

    /** @throws ActionFailed */
    public function reject(string $type, int $id, string $remarks = ''): string
    {
        $remarks = trim($remarks);
        $application = $this->pending($type, $id);

        $decided = $this->decide($type, $id, [
            'status' => 'Rejected',
            'admin_remarks' => $remarks !== '' ? $remarks : null,
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$decided) {
            throw new ActionFailed('Application could not be rejected.');
        }

        [$title, $text, $noRemarks] = self::COPY[$type]['rejected'];
        $message = $text . ' ' . ($remarks !== '' ? 'Admin remarks: ' . $remarks : $noRemarks);

        createNotification($application->user_id, $title, $message, $type, $application->id);

        $this->email($application, $type, 'Rejected', $remarks !== '' ? $remarks : null);

        return ucfirst($type) . ' application rejected.';
    }

    private function table(string $type): string
    {
        return $type . '_applications';
    }

    private function pending(string $type, int $id): object
    {
        $application = DB::table($this->table($type))->where('id', $id)->first();

        if (!$application) {
            throw new ActionFailed('Application not found.');
        }

        if ($application->status !== 'Pending Verification') {
            throw new ActionFailed('This application was already ' . strtolower($application->status) . '.');
        }

        return $application;
    }

    /** Only while still pending, so a double click can't email the applicant twice. */
    private function decide(string $type, int $id, array $changes): bool
    {
        return (bool) DB::table($this->table($type))
            ->where('id', $id)
            ->where('status', 'Pending Verification')
            ->update($changes);
    }

    /** Emailed after the response, so the page doesn't wait on the mail server. */
    private function email(object $application, string $type, string $decision, ?string $remarks = null): void
    {
        $applicant = DB::table('users')->where('id', $application->user_id)->first();

        if (!$applicant) {
            return;
        }

        defer(function () use ($applicant, $application, $type, $decision, $remarks) {
            try {
                Mail::to($applicant->email)->send(
                    new ApplicationStatusMail($application->full_name ?? $applicant->name, $type, $decision, $remarks)
                );
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
