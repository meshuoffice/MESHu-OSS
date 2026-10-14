<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAssignedLink extends Model
{
    protected $table = 'user_assigned_links';

    protected $fillable = [
        'user_id',
        'company_link_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function companyLink(): BelongsTo
    {
        return $this->belongsTo(CompanyLink::class, 'company_link_id');
    }

    /**
     * link リレーション（companyLink のエイリアス）
     */
    public function link(): BelongsTo
    {
        return $this->companyLink();
    }
}
