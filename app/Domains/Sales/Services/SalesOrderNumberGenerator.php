<?php

namespace App\Domains\Sales\Services;

use Illuminate\Support\Facades\DB;

class SalesOrderNumberGenerator
{
    public function generate(int $companyId): string
    {
        DB::table('sales_order_sequences')->insertOrIgnore([
            'company_id' => $companyId,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('sales_order_sequences')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        $nextNumber = $sequence->last_number + 1;

        DB::table('sales_order_sequences')
            ->where('company_id', $companyId)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return 'SO-' . str_pad(
            (string) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}
