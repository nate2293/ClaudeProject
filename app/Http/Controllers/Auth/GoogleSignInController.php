<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\SignInWithGoogle;
use App\Auth\ReturnUrl;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;
use Throwable;

/**
 * "Continue with Google": sends the shopper to Google, and back to the shop page
 * they started on, signed in, with their bag intact.
 */
class GoogleSignInController extends Controller
{
    public function redirect(Request $request): SymfonyRedirect
    {
        ReturnUrl::rememberFrom($request);

        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            report(new \RuntimeException('Google sign-in clicked but GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET are not set.'));

            return $this->backToShop($request, 'Google sign-in isn’t available just now. Please sign in with your email instead.');
        }

        // Always show the account chooser, so a shared computer doesn't silently reuse the last account.
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request, SignInWithGoogle $signIn): RedirectResponse
    {
        if ($request->filled('error')) { // e.g. access_denied: the shopper pressed Cancel.
            return $this->backToShop($request, 'Google sign-in was cancelled.');
        }

        try {
            $user = $signIn->handle(Socialite::driver('google')->user());
        } catch (Throwable $e) {
            // Includes a tampered or expired `state` (CSRF protection) and unverified emails.
            report($e);

            return $this->backToShop($request, 'We couldn’t sign you in with Google. Please try again.');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate(); // Keeps the bag; issues a new session ID.

        $redirect = redirect()->intended(route('home', absolute: false));

        return $signIn->removedUnverifiedPassword
            ? $redirect->with('success', 'You’re signed in with Google. For your security we removed the old password on this account; you can set a new one in Account settings.')
            : $redirect->with('success', "Signed in as {$user->name}.");
    }

    private function backToShop(Request $request, string $message): RedirectResponse
    {
        return redirect()->intended(route('home', absolute: false))->with('error', $message);
    }
}
