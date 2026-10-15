<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * 複合主キーに対応した update / delete クエリの条件設定
     */
    protected function setKeysForSaveQuery($query)
    {
        $keys = $this->getKeyName();
        if (!is_array($keys)) {
            return parent::setKeysForSaveQuery($query);
        }

        foreach ($keys as $keyName) {
            $query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
        }

        return $query;
    }

    /**
     * 複合主キー用のキー値取得
     */
    protected function getKeyForSaveQuery($keyName = null)
    {
        if ($keyName === null) {
            $keyName = $this->getKeyName();
        }

        if (is_array($keyName)) {
            return array_map(fn($k) => $this->original[$k] ?? $this->getAttribute($k), $keyName);
        }

        return $this->original[$keyName] ?? $this->getAttribute($keyName);
    }

    public function companyLink(): BelongsTo
    {
        return $this->belongsTo(CompanyLink::class, 'company_link_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
