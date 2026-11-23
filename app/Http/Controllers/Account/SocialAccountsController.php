<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Google sign-in: connect it to the account, or disconnect it. */
class SocialAccountsController extends Controller
{
    public function show(Request $request): Response
    {
        return Inertia::render('account/social-accounts', [
            'google' => ['connected' => $request->user()->google_id !== null],
        ]);
    }

    public function disconnectGoogle(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Without Google or a password there'd be no way back in.
        if (! $user->hasPassword()) {
            return back()->with('error', 'Set a password first, so you can still sign in after disconnecting Google.');
        }

        $user->forceFill(['google_id' => null, 'avatar_url' => null])->save();

        return back()->with('success', 'Google is disconnected. Sign in with your email and password from now on.');
    }
}
