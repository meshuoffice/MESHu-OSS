<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TransactionComment extends Model
{
    protected $table = 'transaction_comments';

    protected $fillable = [
        'transaction_id',
        'company_id',
        'user_id',
        'comment',
    ];

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
     * 指定取引の全コメント（投稿企業・ユーザー名付き）を時系列で取得
     */
    public static function getCommentsWithAuthors(int $transactionId): array
    {
        return DB::table('transaction_comments as tc')
            ->select([
                'tc.*',
                'c.name as company_name',
                'c.abbreviated_name as company_abbreviated_name',
                'u.user_name',
                'u.department as user_department',
            ])
            ->join('companies as c', 'c.id', '=', 'tc.company_id')
            ->leftJoin('users as u', 'u.id', '=', 'tc.user_id')
            ->where('tc.transaction_id', $transactionId)
            ->orderBy('tc.created_at', 'asc')
            ->orderBy('tc.id', 'asc')
            ->get()
            ->map(fn($item) => (array)$item)
            ->all();
    }
}
