<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'company_link_id',
        'title',
        'order_number',
        'status',
        'subtotal_amount',
        'tax_amount',
        'total_amount',
        'discount_amount',
        'shipping_amount',
        'delivery_date',
        'delivery_location',
        'payment_terms',
        'buyer_notes',
        'seller_notes',
        'decline_reason',
    ];

    protected $casts = [
        'subtotal_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'delivery_date'   => 'date',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function companyLink(): BelongsTo
    {
        return $this->belongsTo(CompanyLink::class, 'company_link_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id')->orderBy('line_no', 'asc');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransactionHistory::class, 'transaction_id')->orderBy('id', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class, 'transaction_id')->orderBy('id', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TransactionComment::class, 'transaction_id')->orderBy('created_at', 'asc');
    }
}
