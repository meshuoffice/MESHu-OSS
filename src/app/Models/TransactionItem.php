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
        'spec',
        'quantity',
        'unit',
        'unit_price',
        'amount',
    ];

    protected $casts = [
        'line_no'    => 'integer',
        'quantity'   => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }
}
