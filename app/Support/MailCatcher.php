<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * The test sheets' mail catcher: example.com can never receive mail, so an
 * email whose every recipient is @example.com is kept in test_mails (read
 * at /mailbox) instead of being sent. Real addresses are sent as usual.
 */
class MailCatcher
{
    public const DOMAINS = ['example.com'];

    public static function catches(string $email): bool
    {
        $domain = strtolower(substr(strrchr(trim($email), '@') ?: '', 1));

        foreach (self::DOMAINS as $caught) {
            if ($domain === $caught || str_ends_with($domain, '.' . $caught)) {
                return true;
            }
        }

        return false;
    }

    /** Keeps the message when it is for caught addresses only; true = don't send it. */
    public static function intercept(Email $message): bool
    {
        $to = array_map(fn (Address $address) => $address->getAddress(), array_merge($message->getTo(), $message->getCc(), $message->getBcc()));

        if ($to === [] || in_array(false, array_map([self::class, 'catches'], $to), true)) {
            return false;
        }

        foreach (array_unique(array_map('strtolower', $to)) as $address) {
            DB::table('test_mails')->insert([
                'to' => $address,
                'subject' => (string) $message->getSubject(),
                'html' => is_string($html = $message->getHtmlBody()) ? $html : null,
                'text' => is_string($text = $message->getTextBody()) ? $text : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }
}
