<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class ChatMessageAttachment extends Model
{
    protected $table = 'chat_message_attachments';

    public $timestamps = false;

    protected $fillable = [
        'chat_message_id',
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

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'chat_message_id');
    }

    /**
     * 添付ファイルの実バイナリデータを取得（ダウンロード配信用）
     */
    public static function getAttachmentBinary(int $attachmentId): ?array
    {
        $row = DB::table('chat_message_attachments as cma')
            ->select([
                'cma.id',
                'cma.chat_message_id',
                'cma.file_name',
                'cma.mime_type',
                'cma.file_size',
                'cma.file_data',
                'cm.company_link_id',
                'cm.company_id',
            ])
            ->join('chat_messages as cm', 'cm.id', '=', 'cma.chat_message_id')
            ->where('cma.id', $attachmentId)
            ->first();

        if (!$row) {
            return null;
        }

        $res = (array)$row;
        if (is_resource($res['file_data'])) {
            $res['file_data'] = stream_get_contents($res['file_data']);
        }

        return $res;
    }
}
