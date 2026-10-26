<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'company_id',
        'email',
        'password_hash',
        'user_name',
        'user_name_kana',
        'department',
        'position',
        'role',
        'status',
        'last_login_at',
        'is_all_assigned',
        'notify_trade',
        'notify_comment',
        'notify_chat',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_all_assigned' => 'integer',
        'notify_trade'    => 'boolean',
        'notify_comment'  => 'boolean',
        'notify_chat'     => 'boolean',
        'last_login_at'   => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    /**
     * Laravel 認証用パスワードカラムのマッピング
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function assignedLinks(): HasMany
    {
        return $this->hasMany(UserAssignedLink::class, 'user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
