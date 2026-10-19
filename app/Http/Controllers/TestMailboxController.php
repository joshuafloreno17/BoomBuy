<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

/**
 * /mailbox — the test sheets' mail catcher (App\Support\MailCatcher).
 * Only mail to @example.com test accounts is ever kept here.
 */
class TestMailboxController extends Controller
{
    public function index()
    {
        $to = strtolower(trim((string) request('to')));

        $mails = DB::table('test_mails')
            ->when($to !== '', fn ($q) => $q->where('to', 'like', '%' . $to . '%'))
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'to', 'subject', 'created_at']);

        return view('pages.mailbox', ['mails' => $mails, 'to' => $to, 'open' => null]);
    }

    public function show(int $id)
    {
        $open = DB::table('test_mails')->where('id', $id)->first();
        abort_unless($open, 404);

        $mails = DB::table('test_mails')->where('to', $open->to)->orderByDesc('id')->limit(100)->get(['id', 'to', 'subject', 'created_at']);

        return view('pages.mailbox', ['mails' => $mails, 'to' => $open->to, 'open' => $open]);
    }
}
