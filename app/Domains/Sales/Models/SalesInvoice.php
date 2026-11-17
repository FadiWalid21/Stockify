<?php

namespace App\Domains\Sales\Models;

use App\Domains\Company\Models\TenantModel;
use App\Domains\Customer\Models\Customer;
use App\Domains\User\Models\User;
use App\Domains\Warehouse\Models\Warehouse;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends TenantModel
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_PAID = 'partially_paid';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    protected $appends = [
        'amount_paid',
        'outstanding_amount',
    ];

    protected $fillable = [
        'customer_id',
        'sales_order_id',
        'warehouse_id',
        'user_id',
        'cancelled_by',
        'invoice_number',
        'status',
        'invoice_date',
        'due_date',
        'issued_at',
        'cancelled_at',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'subtotal' => 'decimal:4',
            'discount_total' => 'decimal:4',
            'tax_total' => 'decimal:4',
            'grand_total' => 'decimal:4',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesInvoiceItem::class);
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(SalesPaymentAllocation::class);
    }

    public function getAmountPaidAttribute(): string
    {
        // Use the preloaded aggregate when available.
        if (array_key_exists('amount_paid', $this->attributes)) {
            return (string) ($this->attributes['amount_paid'] ?? '0');
        }

        // Fallback for invoices loaded without the aggregate.
        return (string) $this->paymentAllocations()
            ->whereHas('payment', function ($query) {
                $query->where('status', SalesPayment::STATUS_COMPLETED);
            })
            ->sum('amount');
    }

    public function getOutstandingAmountAttribute(): string
    {
        return (string) BigDecimal::of((string) $this->grand_total)
            ->minus(BigDecimal::of((string) $this->amount_paid))
            ->toScale(4, RoundingMode::HalfUp);
    }
}