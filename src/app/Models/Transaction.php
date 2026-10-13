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
        'status',
        'title',
        'desired_delivery_date',
        'delivery_date',
        'is_tax_exempt',
        'tax_rate',
        'amount_rounding',
        'tax_rounding',
        'subtotal_amount',
        'shipping_fee',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'buyer_remarks',
        'seller_remarks',
        'decline_category',
        'decline_reason',
    ];

    protected $casts = [
        'desired_delivery_date' => 'date',
        'delivery_date'         => 'date',
        'is_tax_exempt'         => 'integer',
        'tax_rate'              => 'integer',
        'subtotal_amount'       => 'integer',
        'shipping_fee'          => 'integer',
        'discount_amount'       => 'integer',
        'tax_amount'            => 'integer',
        'total_amount'          => 'integer',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
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
