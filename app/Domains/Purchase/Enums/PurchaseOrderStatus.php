<?php

namespace App\Domains\Purchase\Enums;

enum PurchaseOrderStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PARTIALLY_RECEIVED = 'partially_received';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
}