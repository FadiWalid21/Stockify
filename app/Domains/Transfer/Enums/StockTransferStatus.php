<?php

namespace App\Domains\Transfer\Enums;

enum StockTransferStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case IN_TRANSIT = 'in_transit';
    case RECEIVED = 'received';
    case CANCELLED = 'cancelled';
}