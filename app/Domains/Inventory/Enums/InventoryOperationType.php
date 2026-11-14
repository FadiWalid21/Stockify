<?php

namespace App\Domains\Inventory\Enums;

enum InventoryOperationType: string
{
    case STOCK_IN = 'stock_in';
    case STOCK_OUT = 'stock_out';
    case OPENING_BALANCE = 'opening_balance';
    case ADJUSTMENT = 'adjustment';
}
