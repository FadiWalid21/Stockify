<?php

namespace App\Domains\Returns\Services;

use Illuminate\Support\Facades\DB;

class SalesReturnNumberGenerator
{
    public function next(int $companyId): string
    {
        DB::table('sales_return_sequences')->insertOrIgnore([
            'company_id' => $companyId,
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table('sales_return_sequences')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        $nextNumber = $sequence->last_number + 1;

        DB::table('sales_return_sequences')
            ->where('company_id', $companyId)
            ->update([
                'last_number' => $nextNumber,
                'updated_at' => now(),
            ]);

        return 'SR-' . str_pad(
            (string) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }
}