<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [
        'parent_id',
        'invitation_company_id',
        'url_code',
        'corporate_number',
        'name',
        'abbreviated_name',
        'abbreviated_kana',
        'sub_name',
        'zip_code',
        'prefecture_code',
        'city',
        'address',
        'homepage',
        'tel',
        'tax_rate',
        'amount_rounding',
        'tax_rounding',
        'status',
    ];

    protected $casts = [
        'prefecture_code' => 'integer',
        'tax_rate'        => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Company::class, 'parent_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'company_id');
    }

    public function badge(): HasOne
    {
        return $this->hasOne(CompanyBadge::class, 'company_id');
    }

    public function buyerLinks(): HasMany
    {
        return $this->hasMany(CompanyLink::class, 'buyer_company_id');
    }

    public function sellerLinks(): HasMany
    {
        return $this->hasMany(CompanyLink::class, 'seller_company_id');
    }

    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class, 'company_id');
    }
}
