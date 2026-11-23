<?php

namespace App\Enums;

enum ProductBadge: string
{
    case New = 'new';
    case Sale = 'sale';
    case Bestseller = 'bestseller';
}
