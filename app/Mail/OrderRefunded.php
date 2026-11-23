<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent each time a refund on the order is recorded. */
class OrderRefunded extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** @param  int  $amount  This refund, in minor units. */
    public function __construct(public Order $order, public int $amount)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Refund for your order {$this->order->reference}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.orders.refunded', with: ['order' => $this->order, 'amount' => $this->amount]);
    }
}
