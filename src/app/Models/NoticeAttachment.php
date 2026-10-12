<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class NoticeAttachment extends Model
{
    protected $table = 'notice_attachments';

    public $timestamps = false;

    protected $fillable = [
        'notice_id',
        'file_name',
        'mime_type',
        'file_size',
        'file_data',
        'created_at',
    ];

    protected $casts = [
        'file_size'  => 'integer',
        'created_at' => 'datetime',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(Notice::class, 'notice_id');
    }

    /**
     * PostgreSQL の bytea 型へのバイナリ安全保存（0xff等の非UTF8バイナリをHEXデコードで格納）
     */
    public function setFileDataAttribute($value): void
    {
        if ($value instanceof Expression) {
            $this->attributes['file_data'] = $value;
            return;
        }

        if (is_string($value) && DB::connection()->getDriverName() === 'pgsql') {
            $this->attributes['file_data'] = DB::raw("decode('" . bin2hex($value) . "', 'hex')");
        } else {
            $this->attributes['file_data'] = $value;
        }
    }
}
