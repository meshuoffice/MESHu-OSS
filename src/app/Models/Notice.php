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
}
