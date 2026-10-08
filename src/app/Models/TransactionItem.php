<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    protected $table = 'transaction_items';

    protected $fillable = [
        'transaction_id',
        'line_no',
        'item_name',
        'item_code',
        'quantity',
        'unit',
        'unit_price',
        'line_amount',
        'tax_rate',
        'is_tax_free',
        'remarks',
    ];

    protected $casts = [
        'line_no'     => 'integer',
        'quantity'    => 'decimal:2',
        'unit_price'  => 'decimal:2',
        'line_amount' => 'decimal:2',
        'tax_rate'    => 'integer',
        'is_tax_free' => 'boolean',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
