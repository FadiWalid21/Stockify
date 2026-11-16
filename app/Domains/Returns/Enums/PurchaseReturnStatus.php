<?php

namespace App\Domains\Returns\Enums;

enum PurchaseReturnStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case DISPATCHED = 'dispatched';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
