<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyBadge extends Model
{
    protected $table = 'company_badges';

    protected $fillable = [
        'company_id',
        'verifier_owner_id',
        'status',
        'signature',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'verifier_owner_id');
    }
}
