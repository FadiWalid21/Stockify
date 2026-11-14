<?php

namespace App\Domains\Inventory\Enums;

enum StockMovementType: string
{
    case IN = 'in';
    case OUT = 'out';
}