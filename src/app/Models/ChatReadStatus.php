<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatReadStatus extends Model
{
    protected $table = 'chat_read_statuses';

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = ['company_link_id', 'user_id'];

    protected $fillable = [
        'company_link_id',
        'user_id',
        'last_read_message_id',
        'read_at',
    ];

    protected $casts = [
        'last_read_message_id' => 'integer',
        'read_at'              => 'datetime',
    ];

    public function companyLink(): BelongsTo
    {
        return $this->belongsTo(CompanyLink::class, 'company_link_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
