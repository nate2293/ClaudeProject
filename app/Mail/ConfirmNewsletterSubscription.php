<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Double opt-in: the address joins the list only after this link is clicked. */
class ConfirmNewsletterSubscription extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Please confirm your newsletter subscription');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.newsletter.confirm', with: [
            'confirmUrl' => $this->subscriber->confirmUrl(),
        ]);
    }
}
