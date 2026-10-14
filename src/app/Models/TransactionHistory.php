<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TransactionHistory extends Model
{
    protected $table = 'transaction_histories';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'user_id',
        'status',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 指定取引のステータス変更履歴（操作ユーザー名・企業名付き）を取得
     */
    public static function getHistoriesWithUser(int $transactionId, string $direction = 'DESC'): array
    {
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        return DB::table('transaction_histories as th')
            ->select([
                'th.*',
                'u.user_name',
                'u.company_id',
                'c.name as company_name',
                'c.abbreviated_name as company_abbreviated_name',
            ])
            ->leftJoin('users as u', 'u.id', '=', 'th.user_id')
            ->leftJoin('companies as c', 'c.id', '=', 'u.company_id')
            ->where('th.transaction_id', $transactionId)
            ->orderBy('th.created_at', $dir)
            ->orderBy('th.id', $dir)
            ->get()
            ->map(fn($item) => (array)$item)
            ->all();
    }

    /**
     * 自社に関係する最新の取引アクティビティ履歴を取得
     */
    public static function getRecentActivitiesForCompany(int $companyId, int $limit = 5, ?int $assignedUserId = null): array
    {
        $builder = DB::table('transaction_histories as th')
            ->select([
                'th.id as history_id',
                'th.transaction_id',
                'th.status as history_status',
                'th.note as history_note',
                'th.created_at as activity_at',
                't.title as transaction_title',
                't.status as current_status',
                't.total_amount',
                't.tax_amount',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'buyer_c.name as buyer_company_name',
                'buyer_c.abbreviated_name as buyer_abbreviated_name',
                'seller_c.name as seller_company_name',
                'seller_c.abbreviated_name as seller_abbreviated_name',
                'u.user_name as operator_user_name',
            ])
            ->join('transactions as t', 't.id', '=', 'th.transaction_id')
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id');

        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        return $builder->leftJoin('companies as buyer_c', 'buyer_c.id', '=', 'cl.buyer_company_id')
            ->leftJoin('companies as seller_c', 'seller_c.id', '=', 'cl.seller_company_id')
            ->leftJoin('users as u', 'u.id', '=', 'th.user_id')
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->orderByDesc('th.created_at')
            ->orderByDesc('th.id')
            ->limit($limit)
            ->get()
            ->map(fn($item) => (array)$item)
            ->all();
    }
}
