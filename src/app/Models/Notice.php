<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notice extends Model
{
    protected $table = 'notices';

    protected $fillable = [
        'sender_type',
        'company_id',
        'created_by_admin_id',
        'created_by_user_id',
        'category',
        'target_scope',
        'title',
        'content',
        'is_important',
        'is_published',
        'view_count',
        'published_at',
    ];

    protected $casts = [
        'is_important' => 'boolean',
        'is_published' => 'boolean',
        'view_count'   => 'integer',
        'published_at' => 'datetime',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function createdByAdmin(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'created_by_admin_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(NoticeAttachment::class, 'notice_id');
    }

    /**
     * 一般企業向け閲覧可能なお知らせスコープ
     */
    public function scopeForCompany($query, int $companyId, bool $isAuthenticated = false)
    {
        return $query->where('is_published', true)
            ->where(function ($q) use ($companyId, $isAuthenticated) {
                // 1. 事務局発信
                $q->where(function ($adminQuery) use ($isAuthenticated) {
                    $adminQuery->where('sender_type', 'admin')
                        ->where(function ($scopeQuery) use ($isAuthenticated) {
                            $scopeQuery->where('target_scope', 'all')
                                ->orWhere('target_scope', $isAuthenticated ? 'verified' : 'unverified');
                        });
                })
                // 2. つながり承認企業発信
                ->orWhere(function ($partnerQuery) use ($companyId) {
                    $partnerQuery->where('sender_type', 'company')
                        ->where('company_id', '!=', $companyId)
                        ->whereExists(function ($sub) use ($companyId) {
                            $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                ->from('company_links')
                                ->where('status', 'approved')
                                ->where(function ($linkQ) use ($companyId) {
                                    $linkQ->whereRaw('buyer_company_id = ? AND seller_company_id = notices.company_id', [$companyId])
                                          ->orWhereRaw('seller_company_id = ? AND buyer_company_id = notices.company_id', [$companyId]);
                                });
                        });
                })
                // 3. 自社発信
                ->orWhere(function ($myQuery) use ($companyId) {
                    $myQuery->where('sender_type', 'company')
                        ->where('company_id', $companyId);
                });
            });
    }
}
