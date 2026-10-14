<?php

namespace App\Models;

use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TransactionAttachment extends Model
{
    protected $table = 'transaction_attachments';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'company_id',
        'user_id',
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

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 指定取引の添付ファイル一覧（バイナリ本体を除くメタ情報＋アップロード企業情報）を取得
     */
    public static function getAttachmentsMetaByTransactionId(int $transactionId): array
    {
        return DB::table('transaction_attachments as ta')
            ->select([
                'ta.id',
                'ta.transaction_id',
                'ta.company_id',
                'ta.user_id',
                'ta.file_name',
                'ta.mime_type',
                'ta.file_size',
                'ta.created_at',
                'c.name as company_name',
                'u.user_name',
            ])
            ->leftJoin('companies as c', 'c.id', '=', 'ta.company_id')
            ->leftJoin('users as u', 'u.id', '=', 'ta.user_id')
            ->where('ta.transaction_id', $transactionId)
            ->orderBy('ta.created_at', 'asc')
            ->orderBy('ta.id', 'asc')
            ->get()
            ->map(fn($item) => (array)$item)
            ->all();
    }

    /**
     * ファイル実バイナリを取得（ダウンロード配信用）
     */
    public static function getAttachmentBinary(int $attachmentId): ?array
    {
        $row = DB::table('transaction_attachments')
            ->select(['id', 'transaction_id', 'company_id', 'file_name', 'mime_type', 'file_size', 'file_data'])
            ->where('id', $attachmentId)
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
