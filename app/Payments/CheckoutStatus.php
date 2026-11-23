<?php

namespace App\Payments;

enum CheckoutStatus
{
    /** Payment has been captured. */
    case Paid;

    /** The customer completed checkout with a delayed payment method that hasn't settled yet. */
    case Processing;

    /** Still open: the customer hasn't completed checkout. */
    case Pending;

    /** The session expired without payment. */
    case Expired;
}
