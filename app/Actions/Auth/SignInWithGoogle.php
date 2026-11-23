<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as GoogleUser;
use RuntimeException;

/**
 * Finds or creates the shop account for a Google sign-in.
 *
 *  1. The Google account was used before (matched on Google's ID) → that account.
 *  2. An account already has the same email → link Google to it.
 *  3. Otherwise → a new account, with no password.
 *
 * Only emails Google has verified are accepted, as the email is what links accounts.
 */
class SignInWithGoogle
{
    /** Set when linking had to remove an unverified account's password (see link()). */
    public bool $removedUnverifiedPassword = false;

    public function handle(GoogleUser $google): User
    {
        $email = Str::lower(trim((string) $google->getEmail()));

        if ($email === '' || ! ($google->getRaw()['email_verified'] ?? false)) {
            throw new RuntimeException('Google did not confirm this email address.');
        }

        return DB::transaction(function () use ($google, $email) {
            $user = User::where('google_id', $google->getId())->first()
                ?? $this->link(User::where('email', $email)->lockForUpdate()->first(), $google)
                ?? $this->create($google, $email);

            $user->forceFill(['avatar_url' => $google->getAvatar() ?: $user->avatar_url])->save();

            return $user;
        });
    }

    private function link(?User $user, GoogleUser $google): ?User
    {
        if (! $user) {
            return null;
        }

        $attributes = ['google_id' => $google->getId()];

        // This shop never verified the email on password accounts, so whoever set that
        // password may not own the address: someone could register with your email
        // before you first use Google. Google has now proved ownership, so that password
        // and any sessions using it are revoked. The owner can set a new password.
        if ($user->email_verified_at === null) {
            $this->removedUnverifiedPassword = $user->password !== null;
            $attributes += [
                'email_verified_at' => now(),
                'password' => null,
                'remember_token' => Str::random(60),
                'sessions_revoked_at' => now(),
            ];
        }

        $user->forceFill($attributes)->save();

        return $user;
    }

    private function create(GoogleUser $google, string $email): User
    {
        $user = new User;
        $user->forceFill([
            'name' => $google->getName() ?: Str::before($email, '@'),
            'email' => $email,
            'email_verified_at' => now(),
            'google_id' => $google->getId(),
            'password' => null,
        ])->save();

        event(new Registered($user));

        return $user;
    }
}
