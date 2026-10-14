<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'company_link_id',
        'status',
        'title',
        'desired_delivery_date',
        'delivery_date',
        'is_tax_exempt',
        'tax_rate',
        'amount_rounding',
        'tax_rounding',
        'subtotal_amount',
        'shipping_fee',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'buyer_remarks',
        'seller_remarks',
        'decline_category',
        'decline_reason',
    ];

    protected $casts = [
        'desired_delivery_date' => 'date',
        'delivery_date'         => 'date',
        'is_tax_exempt'         => 'integer',
        'tax_rate'              => 'integer',
        'subtotal_amount'       => 'integer',
        'shipping_fee'          => 'integer',
        'discount_amount'       => 'integer',
        'tax_amount'            => 'integer',
        'total_amount'          => 'integer',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function companyLink(): BelongsTo
    {
        return $this->belongsTo(CompanyLink::class, 'company_link_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class, 'transaction_id')->orderBy('line_no', 'asc');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TransactionHistory::class, 'transaction_id')->orderBy('id', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TransactionAttachment::class, 'transaction_id')->orderBy('id', 'desc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TransactionComment::class, 'transaction_id')->orderBy('created_at', 'asc');
    }

    public static array $statuses = [
        'estimate_request'  => '見積依頼',
        'estimate_replied'  => '見積回答',
        'estimate_declined' => '見積辞退',
        'order_placed'      => '注文',
        'order_accepted'    => '注文回答',
        'order_declined'    => '注文辞退',
    ];

    public static array $buyerStatusLabels = [
        'estimate_request'  => '見積回答待ち',
        'estimate_replied'  => '見積回答受領 (要注文確認)',
        'estimate_declined' => '見積辞退',
        'order_placed'      => '注文中 (受注承諾待ち)',
        'order_accepted'    => '取引完了 (注文承諾済)',
        'order_declined'    => '注文辞退',
    ];

    public static array $sellerStatusLabels = [
        'estimate_request'  => '見積 (要回答)',
        'estimate_replied'  => '見積回答済',
        'estimate_declined' => '見積辞退',
        'order_placed'      => '注文 (要確認)',
        'order_accepted'    => '注文回答 (完了)',
        'order_declined'    => '注文辞退',
    ];

    public static array $statusBadgeClasses = [
        'estimate_request'  => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
        'estimate_replied'  => 'bg-info-subtle text-info-emphasis border border-info-subtle',
        'estimate_declined' => 'bg-secondary-subtle text-secondary border border-secondary',
        'order_placed'      => 'bg-primary-subtle text-primary border border-primary-subtle',
        'order_accepted'    => 'bg-success-subtle text-success border border-success-subtle',
        'order_declined'    => 'bg-secondary-subtle text-secondary border border-secondary',
    ];

    public static array $statusIcons = [
        'estimate_request'  => 'bi-hourglass-split',
        'estimate_replied'  => 'bi-chat-left-dots-fill',
        'estimate_declined' => 'bi-x-circle',
        'order_placed'      => 'bi-cart-check-fill',
        'order_accepted'    => 'bi-check-all',
        'order_declined'    => 'bi-x-circle',
    ];

    public static array $roundingTypes = [
        'round' => '四捨五入',
        'floor' => '切り捨て',
        'ceil'  => '切り上げ',
    ];

    public static function getLabel(string $status, string $role = 'common'): string
    {
        return match ($role) {
            'buyer'  => static::$buyerStatusLabels[$status] ?? static::$statuses[$status] ?? $status,
            'seller' => static::$sellerStatusLabels[$status] ?? static::$statuses[$status] ?? $status,
            default  => static::$statuses[$status] ?? $status,
        };
    }

    public static function getRoundingLabel(?string $type): string
    {
        return static::$roundingTypes[$type ?? 'round'] ?? '四捨五入';
    }

    public static function getBadgeHtml(string $status, string $role = 'common', bool $showIcon = true, string $extraClass = ''): string
    {
        $label = static::getLabel($status, $role);
        $badgeClass = static::$statusBadgeClasses[$status] ?? 'bg-secondary-subtle text-secondary border';
        $iconHtml = '';

        if ($showIcon && !empty(static::$statusIcons[$status])) {
            $iconHtml = '<i class="bi ' . e(static::$statusIcons[$status]) . ' me-1"></i>';
        }

        $classes = trim('badge ' . $badgeClass . ' px-2 py-1 ' . $extraClass);
        return '<span class="' . e($classes) . '">' . $iconHtml . e($label) . '</span>';
    }

    /**
     * 購入側（買手: buyer）の取引一覧をページネーション付きで取得
     */
    public static function getBuyerPaginatedList(int $myCompanyId, array $filters = [], int $perPage = 10, int $page = 1, ?int $assignedUserId = null): array
    {
        return static::getPaginatedListForRole($myCompanyId, 'buyer', $filters, $perPage, $page, $assignedUserId);
    }

    /**
     * 販売側（売手: seller）の取引一覧をページネーション付きで取得
     */
    public static function getSellerPaginatedList(int $myCompanyId, array $filters = [], int $perPage = 10, int $page = 1, ?int $assignedUserId = null): array
    {
        return static::getPaginatedListForRole($myCompanyId, 'seller', $filters, $perPage, $page, $assignedUserId);
    }

    /**
     * 買手/売手共通の取引一覧取得クエリ
     */
    protected static function getPaginatedListForRole(int $myCompanyId, string $role, array $filters, int $perPage, int $page, ?int $assignedUserId = null): array
    {
        $builder = DB::table('transactions as t')
            ->select([
                't.id',
                't.company_link_id',
                't.status',
                't.title',
                't.desired_delivery_date',
                't.delivery_date',
                't.is_tax_exempt',
                't.tax_rate',
                't.amount_rounding',
                't.tax_rounding',
                't.subtotal_amount',
                't.shipping_fee',
                't.discount_amount',
                't.tax_amount',
                't.total_amount',
                't.buyer_remarks',
                't.seller_remarks',
                't.decline_category',
                't.decline_reason',
                't.created_at',
                't.updated_at',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
                'partner.city as partner_city',
                'partner.address as partner_address',
                'cb.status as partner_badge_status',
                DB::raw('(SELECT COUNT(*) FROM transaction_items ti WHERE ti.transaction_id = t.id) as item_count'),
                DB::raw('(SELECT COUNT(*) FROM transaction_attachments ta WHERE ta.transaction_id = t.id) as attachment_count'),
                DB::raw('(SELECT COUNT(*) FROM transaction_comments tc WHERE tc.transaction_id = t.id) as comment_count'),
            ])
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id');

        // 個別担当絞り込み
        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        if ($role === 'buyer') {
            $builder->where('cl.buyer_company_id', $myCompanyId)
                    ->join('companies as partner', 'partner.id', '=', 'cl.seller_company_id');
        } else {
            $builder->where('cl.seller_company_id', $myCompanyId)
                    ->join('companies as partner', 'partner.id', '=', 'cl.buyer_company_id');
        }

        $builder->leftJoin('company_badges as cb', 'cb.company_id', '=', 'partner.id');

        // ステータスフィルター
        if (!empty($filters['status'])) {
            $builder->where('t.status', $filters['status']);
        }

        // キーワード検索（件名、相手企業名）
        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $builder->where(function ($q) use ($kw) {
                $q->where('t.title', 'ILIKE', "%{$kw}%")
                  ->orWhere('partner.name', 'ILIKE', "%{$kw}%")
                  ->orWhere('partner.abbreviated_name', 'ILIKE', "%{$kw}%");
            });
        }

        $countBuilder = clone $builder;
        $totalCount   = $countBuilder->count();

        $offset = ($page - 1) * $perPage;
        $list = $builder->orderByDesc('t.updated_at')
                        ->orderByDesc('t.id')
                        ->offset($offset)
                        ->limit($perPage)
                        ->get()
                        ->map(fn($item) => (array)$item)
                        ->all();

        return [
            'list'       => $list,
            'totalCount' => $totalCount,
            'perPage'    => $perPage,
            'page'       => $page,
            'pageCount'  => (int)ceil($totalCount / $perPage),
        ];
    }

    /**
     * 取引詳細情報（相手企業情報・つながり情報含む）を取得
     */
    public static function getTransactionDetail(int $transactionId, int $myCompanyId, ?int $assignedUserId = null): ?array
    {
        $builder = DB::table('transactions as t')
            ->select([
                't.*',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                DB::raw("CASE WHEN cl.buyer_company_id = {$myCompanyId} THEN 'buyer' ELSE 'seller' END AS my_role"),
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
                'partner.corporate_number as partner_corporate_number',
                'partner.zip_code as partner_zip_code',
                'partner.prefecture_code as partner_prefecture_code',
                'partner.city as partner_city',
                'partner.address as partner_address',
                'partner.tel as partner_tel',
                'partner.homepage as partner_homepage',
                'partner.tax_rate as partner_standard_tax_rate',
                'partner.amount_rounding as partner_amount_rounding',
                'partner.tax_rounding as partner_tax_rounding',
                'cb.status as partner_badge_status',
            ])
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id')
            ->join('companies as partner', function ($join) use ($myCompanyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$myCompanyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->leftJoin('company_badges as cb', 'cb.company_id', '=', 'partner.id');

        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        $builder->where('t.id', $transactionId)
                ->where(function ($q) use ($myCompanyId) {
                    $q->where('cl.buyer_company_id', $myCompanyId)
                      ->orWhere('cl.seller_company_id', $myCompanyId);
                });

        $row = $builder->first();
        if (!$row) {
            return null;
        }

        $res = (array)$row;
        $prefName = Company::PREFECTURES[(int)($res['partner_prefecture_code'] ?? 0)] ?? '';
        $res['partner_full_address'] = trim($prefName . ($res['partner_city'] ?? '') . ($res['partner_address'] ?? ''));
        return $res;
    }

    /**
     * 自社（および担当ユーザー）が当該取引へのアクセス権限を有しているか検証
     */
    public static function canAccess(int $transactionId, int $myCompanyId, ?int $assignedUserId = null): bool
    {
        $builder = DB::table('transactions as t')
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id');

        if ($assignedUserId !== null) {
            $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                    ->where('ual.user_id', $assignedUserId);
        }

        $builder->where('t.id', $transactionId)
                ->where(function ($q) use ($myCompanyId) {
                    $q->where('cl.buyer_company_id', $myCompanyId)
                      ->orWhere('cl.seller_company_id', $myCompanyId);
                });

        return $builder->count() > 0;
    }

    /**
     * ダッシュボード用: 購入（進行中）および販売（未処理）のステータス別件数を集計取得
     */
    public static function getDashboardTransactionCounts(int $myCompanyId, ?int $assignedUserId = null): array
    {
        // 1. 買手（購入）側
        $buyerBuilder = DB::table('transactions as t')
            ->select('t.status', DB::raw('COUNT(*) as cnt'))
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id');

        if ($assignedUserId !== null) {
            $buyerBuilder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                         ->where('ual.user_id', $assignedUserId);
        }

        $buyerRows = $buyerBuilder->where('cl.buyer_company_id', $myCompanyId)
            ->groupBy('t.status')
            ->get();

        $buyerCounts = [];
        foreach ($buyerRows as $r) {
            $buyerCounts[$r->status] = (int)$r->cnt;
        }

        $buyerEstimateRequest = $buyerCounts['estimate_request'] ?? 0;
        $buyerEstimateReplied = $buyerCounts['estimate_replied'] ?? 0;
        $buyerOrderPlaced     = $buyerCounts['order_placed'] ?? 0;
        $buyerTotalProgress   = $buyerEstimateRequest + $buyerEstimateReplied + $buyerOrderPlaced;

        // 2. 売手（販売）側
        $sellerBuilder = DB::table('transactions as t')
            ->select('t.status', DB::raw('COUNT(*) as cnt'))
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id');

        if ($assignedUserId !== null) {
            $sellerBuilder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                          ->where('ual.user_id', $assignedUserId);
        }

        $sellerRows = $sellerBuilder->where('cl.seller_company_id', $myCompanyId)
            ->groupBy('t.status')
            ->get();

        $sellerCounts = [];
        foreach ($sellerRows as $r) {
            $sellerCounts[$r->status] = (int)$r->cnt;
        }

        $sellerEstimatePending = $sellerCounts['estimate_request'] ?? 0;
        $sellerOrderPending    = $sellerCounts['order_placed'] ?? 0;
        $sellerTotalPending    = $sellerEstimatePending + $sellerOrderPending;

        return [
            'buyer' => [
                'total'           => $buyerTotalProgress,
                'estimateRequest' => $buyerEstimateRequest,
                'estimateReplied' => $buyerEstimateReplied,
                'orderPlaced'     => $buyerOrderPlaced,
            ],
            'seller' => [
                'total'           => $sellerTotalPending,
                'estimatePending' => $sellerEstimatePending,
                'orderPending'    => $sellerOrderPending,
            ],
        ];
    }

    /**
     * ダッシュボード用: アクションが必要な取引タスク一覧を取得
     */
    public static function getPendingTasks(int $myCompanyId, int $limit = 5, ?int $assignedUserId = null): array
    {
        // 1. 販売側: 見積依頼(未回答) または 注文(未承諾)
        $sellerBuilder = DB::table('transactions as t')
            ->select([
                't.id',
                't.status',
                't.title',
                't.desired_delivery_date',
                't.created_at',
                DB::raw("'seller' AS task_role"),
                'partner.name AS partner_name',
            ])
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id')
            ->join('companies as partner', 'partner.id', '=', 'cl.buyer_company_id');

        if ($assignedUserId !== null) {
            $sellerBuilder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                          ->where('ual.user_id', $assignedUserId);
        }

        $sellerBuilder->where('cl.seller_company_id', $myCompanyId)
                      ->whereIn('t.status', ['estimate_request', 'order_placed']);

        // 2. 購入側: 見積回答受領(要注文確認)
        $buyerBuilder = DB::table('transactions as t')
            ->select([
                't.id',
                't.status',
                't.title',
                't.desired_delivery_date',
                't.created_at',
                DB::raw("'buyer' AS task_role"),
                'partner.name AS partner_name',
            ])
            ->join('company_links as cl', 'cl.id', '=', 't.company_link_id')
            ->join('companies as partner', 'partner.id', '=', 'cl.seller_company_id');

        if ($assignedUserId !== null) {
            $buyerBuilder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                         ->where('ual.user_id', $assignedUserId);
        }

        $buyerBuilder->where('cl.buyer_company_id', $myCompanyId)
                     ->where('t.status', 'estimate_replied');

        $sellerTasks = $sellerBuilder->get()->map(fn($item) => (array)$item)->all();
        $buyerTasks  = $buyerBuilder->get()->map(fn($item) => (array)$item)->all();

        $allTasks = array_merge($sellerTasks, $buyerTasks);

        usort($allTasks, function ($a, $b) {
            $dateA = !empty($a['desired_delivery_date']) ? $a['desired_delivery_date'] : substr((string)$a['created_at'], 0, 10);
            $dateB = !empty($b['desired_delivery_date']) ? $b['desired_delivery_date'] : substr((string)$b['created_at'], 0, 10);
            return strcmp($dateA, $dateB);
        });

        return array_slice($allTasks, 0, $limit);
    }

    /**
     * ダッシュボード用: 今月の取引総額（成約済み）と前月比を算出
     */
    public static function getMonthlyTransactionTotal(int $myCompanyId, ?int $assignedUserId = null): array
    {
        $currentMonthStart = date('Y-m-01 00:00:00');
        $currentMonthEnd   = date('Y-m-t 23:59:59');

        $lastMonthStart    = date('Y-m-01 00:00:00', strtotime('first day of last month'));
        $lastMonthEnd      = date('Y-m-t 23:59:59', strtotime('last day of last month'));

        $calcTotal = function (string $start, string $end) use ($myCompanyId, $assignedUserId) {
            $builder = DB::table('transactions as t')
                ->join('company_links as cl', 'cl.id', '=', 't.company_link_id')
                ->where(function ($q) use ($myCompanyId) {
                    $q->where('cl.buyer_company_id', $myCompanyId)
                      ->orWhere('cl.seller_company_id', $myCompanyId);
                })
                ->where('t.status', 'order_accepted')
                ->where('t.updated_at', '>=', $start)
                ->where('t.updated_at', '<=', $end);

            if ($assignedUserId !== null) {
                $builder->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                        ->where('ual.user_id', $assignedUserId);
            }

            return (float)$builder->sum('t.total_amount');
        };

        $currentTotal = $calcTotal($currentMonthStart, $currentMonthEnd);
        $lastTotal    = $calcTotal($lastMonthStart, $lastMonthEnd);

        $growthRate = null;
        if ($lastTotal > 0) {
            $growthRate = round((($currentTotal - $lastTotal) / $lastTotal) * 100, 1);
        } elseif ($currentTotal > 0) {
            $growthRate = 100.0;
        } else {
            $growthRate = 0.0;
        }

        $formatAmount = function (float $amount): string {
            if ($amount >= 1000000) {
                return '¥' . number_format($amount / 1000000, 1) . 'M';
            } elseif ($amount >= 1000) {
                return '¥' . number_format($amount / 1000, 0) . 'K';
            } else {
                return '¥' . number_format($amount);
            }
        };

        return [
            'currentMonthTotal' => $currentTotal,
            'lastMonthTotal'    => $lastTotal,
            'growthRate'        => $growthRate,
            'formattedTotal'    => $formatAmount($currentTotal),
            'exactTotal'        => '¥' . number_format($currentTotal),
        ];
    }
}
