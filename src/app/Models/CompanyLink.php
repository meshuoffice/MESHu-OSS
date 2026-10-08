<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLink extends Model
{
    protected $table = 'company_links';

    protected $fillable = [
        'buyer_company_id',
        'seller_company_id',
        'status',
    ];

    public function buyerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'buyer_company_id');
    }

    public function sellerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'seller_company_id');
    }

    public function assignedUsers(): HasMany
    {
        return $this->hasMany(UserAssignedLink::class, 'company_link_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'company_link_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'company_link_id');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }
}
