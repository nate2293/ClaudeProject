<?php

namespace App\Payments;

use RuntimeException;
use Throwable;

class PaymentException extends RuntimeException
{
    public static function notConfigured(): self
    {
        return new self('Payments are not configured. Add STRIPE_SECRET to your .env file.');
    }

    public static function providerError(Throwable $previous): self
    {
        return new self('The payment provider returned an error: '.$previous->getMessage(), previous: $previous);
    }
}
