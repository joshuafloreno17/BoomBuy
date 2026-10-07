<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| NO PASSWORDS IN "WHAT YOU TYPED"
|--------------------------------------------------------------------------
|
| A form with an error goes back with what was typed (back()->withInput()),
| which saves the input in the session file on the server — passwords too.
| The forms never show a password again anyway, so take them out of the
| saved input after every request instead of fixing each form.
|
*/

class ForgetFlashedPasswords
{
    public const FIELDS = ['password', 'password_confirmation', 'current_password', 'new_password', 'new_password_confirmation'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasSession()) {
            $old = $request->session()->get('_old_input');

            if (is_array($old) && array_intersect_key($old, array_flip(self::FIELDS))) {
                // Still flashed: the key stays on this request's flash list, so it goes away after the next page.
                $request->session()->put('_old_input', array_diff_key($old, array_flip(self::FIELDS)));
            }
        }

        return $response;
    }
}
