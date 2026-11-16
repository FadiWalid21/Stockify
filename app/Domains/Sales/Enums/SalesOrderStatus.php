<?php

namespace App\Domains\Sales\Enums;

enum SalesOrderStatus: string
{
    case DRAFT = 'draft';
    case PLACED = 'placed';
    case CONFIRMED = 'confirmed';
    case PARTIALLY_FULFILLED = 'partially_fulfilled';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
}