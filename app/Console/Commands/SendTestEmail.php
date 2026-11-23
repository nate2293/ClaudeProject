<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends one plain email straight away (not queued), to check the mail provider
 * and DNS setup: php artisan shop:test-email you@example.com
 */
class SendTestEmail extends Command
{
    protected $signature = 'shop:test-email {to : Where to send the test}';

    protected $description = 'Send a test email to check the mail provider is set up';

    public function handle(): int
    {
        $mailer = config('mail.default');
        $from = config('mail.from.address');

        try {
            Mail::raw(
                'This is a test from '.config('app.name').". If you can read it, email is working.\n\nSent through: {$mailer}",
                fn ($message) => $message->to($this->argument('to'))->subject(config('app.name').' test email'),
            );
        } catch (Throwable $e) {
            $this->error("Sending failed through \"{$mailer}\": {$e->getMessage()}");

            return self::FAILURE;
        }

        $this->info("Sent through \"{$mailer}\" from {$from}.");

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER is "log", so it was written to storage/logs/laravel.log rather than sent.');
        }

        return self::SUCCESS;
    }
}
