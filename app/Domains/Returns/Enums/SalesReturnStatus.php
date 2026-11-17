<?php

namespace App\Domains\Returns\Enums;

enum SalesReturnStatus: string
{
    case DRAFT = 'draft';

    case APPROVED = 'approved';

    case RECEIVED = 'received';

    case COMPLETED = 'completed';

    case CANCELLED = 'cancelled';
}