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
        'is_all_assigned',
        'notify_transaction_buyer',
        'notify_transaction_seller',
        'notify_chat',
        'notify_notice',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'is_all_assigned'          => 'integer',
        'notify_transaction_buyer' => 'integer',
        'notify_transaction_seller'=> 'integer',
        'notify_chat'              => 'integer',
        'notify_notice'            => 'integer',
        'created_at'               => 'datetime',
        'updated_at'               => 'datetime',
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
