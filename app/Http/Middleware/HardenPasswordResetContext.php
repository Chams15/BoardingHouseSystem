<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HardenPasswordResetContext
{
    public function handle(Request $request, Closure $next)
    {
        //reset get form based Fortify
        if ($request->isMethod('get') && $request->is('reset-password/*')) {
            $request->session()->put('password_reset.email', $request->query('email'));
            $request->session()->put('password_reset.token', $request->route('token'));
        }

        //fortify reset submit POST: /reset-password
        if ($request->isMethod('post') && $request->is('reset-password')) {
            $sessionEmail = $request->session()->get('password_reset.email');
            $sessionToken = $request->session()->get('password_reset.token');

            // force request if naa nay context
            if (! empty($sessionEmail)) {
                $request->merge(['email' => $sessionEmail]);
            }

            // Token mismatch kung gi modify ang token
            if (! empty($sessionToken) && (string) $request->input('token') !== (string) $sessionToken) {
                abort(419, 'Invalid password reset session. Please reopen your reset link.');
            }
        }

        return $next($request);
    }
}
