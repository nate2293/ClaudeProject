<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out sessions that began before the account's `sessions_revoked_at`.
 * Works with every session store (database, Redis...), unlike deleting session
 * rows, and for accounts without a password, unlike AuthenticateSession.
 */
class EnsureSessionIsCurrent
{
    public const SESSION_KEY = 'auth.signed_in_at';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->sessions_revoked_at) {
            $signedInAt = $request->session()->get(self::SESSION_KEY);

            if (! $signedInAt || $signedInAt < $user->sessions_revoked_at->getTimestamp()) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('home')->with('error', 'Please sign in again.');
            }
        }

        return $next($request);
    }
}
