<?php

namespace App\Domains\Product\Services;

use App\Domains\Company\Contracts\TenantContextInterface;
use App\Domains\Product\Models\Product;
use App\Support\Query\Pagination;
use App\Support\Query\QueryFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function getAll(array $params = []): LengthAwarePaginator
    {
        $query = Product::query();

        $status = $params['status'] ?? 'active';

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status !== 'all') {
            $query->where('is_active', true);
        }

        QueryFilter::apply(
            $query,
            $params,
            searchableFields: ['name', 'sku', 'description'],
            filterableFields: ['category_id', 'unit_id'],
            sortableFields: ['name', 'sku', 'created_at'],
        );

        if (! isset($params['sort_by'])) {
            $query->latest();
        }

        return $query->paginate(
            Pagination::perPage(
                isset($params['per_page'])
                    ? (int) $params['per_page']
                    : null
            )
        );
    }

    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            if (! isset($data['sku']) || $data['sku'] === '') {
                $data['sku'] = $this->generateSku();
            }

            return Product::create($data);
        });
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }

    public function delete(Product $product): void
    {
        if (
            $product->inventoryBalances()->exists() ||
            $product->stockMovements()->exists()
        ) {
            throw ValidationException::withMessages([
                'product' => [
                    'This product cannot be deleted because it has inventory records or stock movement history. Deactivate it instead.',
                ],
            ]);
        }

        $product->delete();
    }

    private function generateSku(): string
    {
        $companyId = app(TenantContextInterface::class)
            ->getCompanyId();

        // Ensure the company's sequence row exists.
        DB::table('product_sku_sequences')->insertOrIgnore([
            'company_id' => $companyId,
            'next_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Lock the sequence row for this transaction.
        $sequence = DB::table('product_sku_sequences')
            ->where('company_id', $companyId)
            ->lockForUpdate()
            ->first();

        $number = (int) $sequence->next_number;

        do {
            $sku = 'PRD-' . str_pad(
                (string) $number,
                6,
                '0',
                STR_PAD_LEFT
            );

            $number++;
        } while (
            Product::query()
                ->where('sku', $sku)
                ->exists()
        );

        // Save the next available number.
        DB::table('product_sku_sequences')
            ->where('company_id', $companyId)
            ->update([
                'next_number' => $number,
                'updated_at' => now(),
            ]);

        return $sku;
    }

    public function deactivate(Product $product): Product
    {
        $product->update([
            'is_active' => false,
        ]);

        return $product->refresh();
    }

    public function activate(Product $product): Product
    {
        $product->update([
            'is_active' => true,
        ]);

        return $product->refresh();
    }
}
