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

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDenied(): bool
    {
        return $this->status === 'denied';
    }

    /**
     * 指定企業IDから見た自社属性（buyer / seller）を取得
     */
    public function getRoleForCompany(int $companyId): ?string
    {
        if ((int)$this->buyer_company_id === $companyId) {
            return 'buyer';
        }
        if ((int)$this->seller_company_id === $companyId) {
            return 'seller';
        }
        return null;
    }

    /**
     * 指定企業IDから見た取引先企業IDを取得
     */
    public function getPartnerCompanyId(int $myCompanyId): ?int
    {
        if ((int)$this->buyer_company_id === $myCompanyId) {
            return (int)$this->seller_company_id;
        }
        if ((int)$this->seller_company_id === $myCompanyId) {
            return (int)$this->buyer_company_id;
        }
        return null;
    }

    /**
     * 指定企業IDから見た取引先企業モデルを取得
     */
    public function getPartnerCompany(int $myCompanyId): ?Company
    {
        if ((int)$this->buyer_company_id === $myCompanyId) {
            return $this->sellerCompany;
        }
        if ((int)$this->seller_company_id === $myCompanyId) {
            return $this->buyerCompany;
        }
        return null;
    }
}

