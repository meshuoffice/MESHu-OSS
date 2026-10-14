<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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

    /**
     * ユーザーのアクセス権限判定
     */
    public static function canUserAccessLink(int $linkId, int $companyId, ?int $assignedUserId = null): bool
    {
        $builder = DB::table('company_links as cl')
            ->where('cl.id', $linkId)
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            });

        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        return $builder->count() > 0;
    }

    /**
     * 連携企業一覧を取得
     */
    public static function getLinksForCompany(int $companyId, array $filters = [], ?int $assignedUserId = null): array
    {
        $builder = DB::table('company_links as cl')
            ->select([
                'cl.id as link_id',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'cl.status as link_status',
                'cl.created_at as link_created_at',
                'cl.updated_at as link_updated_at',
                DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN 'buyer' ELSE 'seller' END AS my_role"),
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
                'partner.corporate_number as partner_corporate_number',
                'partner.zip_code as partner_zip_code',
                'partner.prefecture_code as partner_prefecture_code',
                'partner.city as partner_city',
                'partner.address as partner_address',
                'partner.status as partner_company_status',
                'cb.status as partner_badge_status',
            ])
            ->join('companies as partner', function ($join) use ($companyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->leftJoin('company_badges as cb', 'cb.company_id', '=', 'partner.id')
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->where('partner.status', 'active');

        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        if (!empty($filters['my_role'])) {
            if ($filters['my_role'] === 'buyer') {
                $builder->where('cl.buyer_company_id', $companyId);
            } elseif ($filters['my_role'] === 'seller') {
                $builder->where('cl.seller_company_id', $companyId);
            }
        }

        if (!empty($filters['status'])) {
            $builder->where('cl.status', $filters['status']);
        }

        return $builder->orderByDesc('cl.updated_at')
            ->get()
            ->map(function ($item) {
                $arr = (array)$item;
                $prefName = Company::PREFECTURES[(int)($arr['partner_prefecture_code'] ?? 0)] ?? '';
                $arr['partner_full_address'] = trim($prefName . ($arr['partner_city'] ?? '') . ($arr['partner_address'] ?? ''));
                return $arr;
            })
            ->all();
    }
}
