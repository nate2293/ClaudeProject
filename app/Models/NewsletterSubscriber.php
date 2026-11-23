<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Str;

/**
 * A newsletter sign-up. Double opt-in: an address may only be emailed once it
 * has been confirmed, and stops the moment it unsubscribes.
 */
class NewsletterSubscriber extends Model
{
    use Prunable;

    /** Unconfirmed sign-ups are deleted after this long, and their links stop working. */
    public const CONFIRM_WITHIN_DAYS = 7;

    protected $fillable = ['email'];

    protected $hidden = ['token'];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'unsubscribed_at' => 'datetime'];
    }

    /** Addresses that may receive the newsletter. @param Builder<self> $query */
    public function scopeMailable(Builder $query): void
    {
        $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function isMailable(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    /** Start (or restart) the opt-in: a fresh secret link, valid for CONFIRM_WITHIN_DAYS. */
    public function startOptIn(): void
    {
        $this->forceFill([
            'token' => Str::random(64),
            'confirmed_at' => null,
            'unsubscribed_at' => null,
            'updated_at' => now(),
        ])->save();
    }

    public function confirmUrl(): string
    {
        return route('newsletter.confirm', $this->token);
    }

    /** Put this link in every newsletter (and the List-Unsubscribe header). */
    public function unsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', $this->token);
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return static::query()
            ->whereNull('confirmed_at')
            ->where('updated_at', '<=', now()->subDays(self::CONFIRM_WITHIN_DAYS));
    }
}
