<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $table = 'invitations';

    protected $fillable = [
        'company_id',
        'user_id',
        'invitation_code',
        'buysell',
        'email',
        'mail_subject',
        'mail_detail',
        'company_name',
        'corporate_number',
        'user_name',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getInviteeNameAttribute(): ?string
    {
        return $this->company_name;
    }

    public function getInviteeEmailAttribute(): ?string
    {
        return $this->email;
    }

    public function getInviterRoleAttribute(): ?string
    {
        return $this->buysell;
    }
}

