<?php

namespace App\Domains\Purchase\Services;

use Illuminate\Support\Facades\DB;

class PurchaseOrderNumberGenerator
{
    public function generate(int $companyId): string
    {
        DB::table('purchase_order_sequences')->insertOrIgnore([
            'company_id' => $companyId,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('purchase_order_sequences')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        $nextNumber = $sequence->last_number + 1;

        DB::table('purchase_order_sequences')
            ->where('company_id', $companyId)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return 'PO-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }
}