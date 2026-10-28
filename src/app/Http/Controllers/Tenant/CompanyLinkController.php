<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\CompanyLinkApprovedMail;
use App\Mail\CompanyLinkRequestedMail;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CompanyLinkController extends Controller
{
    /**
     * 自社企業および管理者権限の検証
     */
    private function authorizeAdmin(): Company
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'admin') {
            abort(403, '管理者権限が必要です。');
        }

        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        return $company;
    }

    /**
     * ログイン中ユーザーが全取引先担当であるか判定
     */
    private function isAllAssignedUser(): bool
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        return (int)($user->is_all_assigned ?? 1) === 1;
    }

    /**
     * 担当ユーザーID絞り込みフィルターを取得
     */
    private function getAssignedUserIdFilter(): ?int
    {
        if ($this->isAllAssignedUser()) {
            return null;
        }

        $user = Auth::guard('web')->user();
        return $user ? (int)$user->id : null;
    }

    /**
     * 1. つながり企業一覧画面 (GET /link)
     */
    public function index(Request $request): View
    {
        $company = $this->authorizeAdmin();
        $companyId = $company->id;
        $assignedUserId = $this->getAssignedUserIdFilter();

        $page = max(1, (int)$request->query('page', 1));
        $perPage = 10;

        $filters = [
            'my_role' => $request->query('my_role'),
            'status'  => $request->query('status'),
            'keyword' => trim((string)$request->query('keyword', '')),
        ];

        $sort = $request->query('sort', 'default');

        // 最新取引起票日（最終依頼日）サブクエリ
        $latestTxSub = DB::table('transactions')
            ->select('company_link_id', DB::raw('MAX(created_at) as latest_order_at'))
            ->groupBy('company_link_id');

        // クエリ構築
        $query = DB::table('company_links as cl')
            ->select([
                'cl.id as link_id',
                'cl.buyer_company_id',
                'cl.seller_company_id',
                'cl.status as link_status',
                'cl.created_at as link_created_at',
                'cl.updated_at as link_updated_at',
                DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN 'buyer' ELSE 'seller' END as my_role"),
                'partner.id as partner_company_id',
                'partner.name as partner_name',
                'partner.abbreviated_name as partner_abbreviated_name',
                'partner.corporate_number as partner_corporate_number',
                'partner.zip_code as partner_zip_code',
                'partner.prefecture_code as partner_prefecture_code',
                'partner.city as partner_city',
                'partner.address as partner_address',
                'partner.status as partner_company_status',
                'cb.status as partner_badge_status',
                'ltx.latest_order_at',
            ])
            ->leftJoinSub($latestTxSub, 'ltx', 'ltx.company_link_id', '=', 'cl.id')
            ->join('companies as partner', function ($join) use ($companyId) {
                $join->on('partner.id', '=', DB::raw("CASE WHEN cl.buyer_company_id = {$companyId} THEN cl.seller_company_id ELSE cl.buyer_company_id END"));
            })
            ->leftJoin('company_badges as cb', 'cb.company_id', '=', 'partner.id')
            ->where(function ($q) use ($companyId) {
                $q->where('cl.buyer_company_id', $companyId)
                  ->orWhere('cl.seller_company_id', $companyId);
            })
            ->where('partner.status', 'active');

        // 個別担当絞り込み
        if ($assignedUserId !== null) {
            $query->join('user_assigned_links as ual', 'ual.company_link_id', '=', 'cl.id')
                  ->where('ual.user_id', $assignedUserId);
        }

        // 自社属性フィルター
        if (!empty($filters['my_role'])) {
            if ($filters['my_role'] === 'buyer') {
                $query->where('cl.buyer_company_id', $companyId);
            } elseif ($filters['my_role'] === 'seller') {
                $query->where('cl.seller_company_id', $companyId);
            }
        }

        // ステータスフィルター
        if (!empty($filters['status'])) {
            $query->where('cl.status', $filters['status']);
        }

        // キーワード検索
        if (!empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('partner.name', 'like', "%{$kw}%")
                  ->orWhere('partner.corporate_number', 'like', "%{$kw}%")
                  ->orWhere('partner.address', 'like', "%{$kw}%");
            });
        }

        $totalCount = $query->count();

        if ($sort === 'last_order_desc') {
            // 最終依頼日の降順（nullは末尾）
            $query->orderByRaw('CASE WHEN ltx.latest_order_at IS NULL THEN 1 ELSE 0 END ASC')
                  ->orderBy('ltx.latest_order_at', 'desc')
                  ->orderBy('cl.id', 'desc');
        } else {
            // デフォルト（ID降順）
            $query->orderBy('cl.id', 'desc');
        }

        $rows = $query->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $prefs = [
            1=>'北海道',2=>'青森県',3=>'岩手県',4=>'宮城県',5=>'秋田県',6=>'山形県',7=>'福島県',
            8=>'茨城県',9=>'栃木県',10=>'群馬県',11=>'埼玉県',12=>'千葉県',13=>'東京都',14=>'神奈川県',
            15=>'新潟県',16=>'富山県',17=>'石川県',18=>'福井県',19=>'山梨県',20=>'長野県',21=>'岐阜県',
            22=>'静岡県',23=>'愛知県',24=>'三重県',25=>'滋賀県',26=>'京都府',27=>'大阪府',28=>'兵庫県',
            29=>'奈良県',30=>'和歌山県',31=>'鳥取県',32=>'島根県',33=>'岡山県',34=>'広島県',35=>'山口県',
            36=>'徳島県',37=>'香川県',38=>'愛媛県',39=>'高知県',40=>'福岡県',41=>'佐賀県',42=>'長崎県',
            43=>'熊本県',44=>'大分県',45=>'宮崎県',46=>'鹿児島県',47=>'沖縄県'
        ];

        $links = [];
        foreach ($rows as $item) {
            $itemArray = (array)$item;
            $zip  = !empty($itemArray['partner_zip_code']) ? '〒' . $itemArray['partner_zip_code'] . ' ' : '';
            $pref = $prefs[(int)($itemArray['partner_prefecture_code'] ?? 0)] ?? '';
            $city = $itemArray['partner_city'] ?? '';
            $addr = $itemArray['partner_address'] ?? '';
            $itemArray['partner_full_address'] = trim($zip . $pref . $city . ' ' . $addr);
            $itemArray['is_authenticated']     = (($itemArray['partner_badge_status'] ?? '') === 'active');

            // 商流の熱力学：最新起票日判定（青・黄・赤）
            $latestOrderAt = $itemArray['latest_order_at'] ?? null;
            if (!$latestOrderAt) {
                $itemArray['last_order_badge'] = 'danger'; // 赤：取引無し
                $itemArray['last_order_date_formatted'] = '[取引無し]';
            } else {
                $dt = \Carbon\Carbon::parse($latestOrderAt);
                $diffDays = (int)$dt->diffInDays(now());
                $itemArray['last_order_badge'] = $diffDays <= 30 ? 'primary' : 'warning'; // 30日以内: 青, 30日超: 黄
                $itemArray['last_order_date_formatted'] = $dt->format('Y/m/d');
            }

            $links[] = $itemArray;
        }

        return view('link.index', [
            'title'          => 'つながり管理 (MESH)',
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'links'          => $links,
            'totalCount'     => $totalCount,
            'perPage'        => $perPage,
            'page'           => $page,
            'filters'        => $filters,
            'sort'           => $sort,
        ]);
    }

    /**
     * 2. 新規つながり検索画面 (GET /link/search)
     */
    public function search(Request $request): View
    {
        $company = $this->authorizeAdmin();
        $companyId = $company->id;

        $keyword         = trim((string)$request->query('keyword', ''));
        $corporateNumber = trim((string)$request->query('corporate_number', ''));
        $prefectureCode  = $request->query('prefecture_code');
        $city            = trim((string)$request->query('city', ''));
        $isVerified      = $request->query('is_verified');

        $query = Company::query()
            ->select('companies.*', 'cb.status as badge_status')
            ->leftJoin('company_badges as cb', 'cb.company_id', '=', 'companies.id')
            ->where('companies.id', '<>', $companyId)
            ->where('companies.status', 'active');

        // 既存のつながりデータが存在する企業を除外（未連携企業のみ）
        $query->whereRaw("NOT EXISTS (
            SELECT 1 FROM company_links cl 
            WHERE (cl.buyer_company_id = ? AND cl.seller_company_id = companies.id)
               OR (cl.seller_company_id = ? AND cl.buyer_company_id = companies.id)
        )", [$companyId, $companyId]);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('companies.name', 'like', "%{$keyword}%")
                  ->orWhere('companies.abbreviated_name', 'like', "%{$keyword}%")
                  ->orWhere('companies.abbreviated_kana', 'like', "%{$keyword}%");
            });
        }

        if ($corporateNumber !== '') {
            $query->where('companies.corporate_number', $corporateNumber);
        }

        if (!empty($prefectureCode)) {
            $query->where('companies.prefecture_code', (int)$prefectureCode);
        }

        if ($city !== '') {
            $query->where('companies.city', 'like', "%{$city}%");
        }

        if ($isVerified === 'verified' || (string)$isVerified === '1') {
            $query->where('cb.status', 'active');
        } elseif ((string)$isVerified === '0') {
            $query->where(function ($q) {
                $q->whereNull('cb.status')->orWhere('cb.status', '<>', 'active');
            });
        }

        $page = max(1, (int)$request->query('page', 1));
        $perPage = 10;
        $totalCount = $query->count();

        $companies = $query->orderBy('companies.id', 'asc')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $prefs = [
            1=>'北海道',2=>'青森県',3=>'岩手県',4=>'宮城県',5=>'秋田県',6=>'山形県',7=>'福島県',
            8=>'茨城県',9=>'栃木県',10=>'群馬県',11=>'埼玉県',12=>'千葉県',13=>'東京都',14=>'神奈川県',
            15=>'新潟県',16=>'富山県',17=>'石川県',18=>'福井県',19=>'山梨県',20=>'長野県',21=>'岐阜県',
            22=>'静岡県',23=>'愛知県',24=>'三重県',25=>'滋賀県',26=>'京都府',27=>'大阪府',28=>'兵庫県',
            29=>'奈良県',30=>'和歌山県',31=>'鳥取県',32=>'島根県',33=>'岡山県',34=>'広島県',35=>'山口県',
            36=>'徳島県',37=>'香川県',38=>'愛媛県',39=>'高知県',40=>'福岡県',41=>'佐賀県',42=>'長崎県',
            43=>'熊本県',44=>'大分県',45=>'宮崎県',46=>'鹿児島県',47=>'沖縄県'
        ];

        $searchResults = [];
        foreach ($companies as $comp) {
            $cArray = $comp->toArray();
            $prefName = $prefs[(int)($cArray['prefecture_code'] ?? 0)] ?? '';
            $cityStr  = $cArray['city'] ?? '';
            $addrStr  = $cArray['address'] ?? '';
            $cArray['prefectural_address'] = trim($prefName . $cityStr . ' ' . $addrStr);
            $cArray['is_authenticated']     = (($comp->badge_status ?? '') === 'active');
            $searchResults[] = $cArray;
        }

        return view('link.search', [
            'title'           => '企業検索（新しいつながり）',
            'activeMenu'      => 'settings',
            'currentCompany'  => $company,
            'searchResults'   => $searchResults,
            'isSearched'      => true,
            'totalCount'      => $totalCount,
            'perPage'         => $perPage,
            'page'            => $page,
            'keyword'         => $keyword,
            'corporateNumber' => $corporateNumber,
            'prefectureCode'  => $prefectureCode,
            'city'            => $city,
            'isVerified'      => $isVerified,
        ]);
    }

    /**
     * 3. 連携申請の確認画面 (GET /link/show/{target_company_id})
     */
    public function show(int $targetCompanyId): View|RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $myCompanyId = $company->id;

        if ($targetCompanyId === $myCompanyId) {
            return redirect()->route('tenant.link.search')->with('error', '自社宛には連携申請を行えません。');
        }

        $targetCompany = Company::find($targetCompanyId);
        if (!$targetCompany) {
            abort(404, '対象の企業が存在しません。');
        }

        $existingLink = CompanyLink::where(function ($q) use ($myCompanyId, $targetCompanyId) {
            $q->where('buyer_company_id', $myCompanyId)->where('seller_company_id', $targetCompanyId);
        })->orWhere(function ($q) use ($myCompanyId, $targetCompanyId) {
            $q->where('buyer_company_id', $targetCompanyId)->where('seller_company_id', $myCompanyId);
        })->first();

        return view('link.show', [
            'title'          => '連携申請の確認',
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetCompany'  => $targetCompany,
            'existingLink'   => $existingLink,
        ]);
    }

    /**
     * 4. 連携申請の送信 (POST /link/request)
     */
    public function requestLink(Request $request): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $myCompanyId = $company->id;

        $targetCompanyId = (int)$request->input('target_company_id');

        if ($targetCompanyId === $myCompanyId) {
            return redirect()->route('tenant.link.search')->with('error', '自社宛には連携申請を行えません。');
        }

        $targetCompany = Company::find($targetCompanyId);
        if (!$targetCompany) {
            return redirect()->back()->with('error', '対象の企業が存在しません。');
        }

        try {
            // 既存リンク確認
            $existing = CompanyLink::where(function ($q) use ($myCompanyId, $targetCompanyId) {
                $q->where('buyer_company_id', $myCompanyId)->where('seller_company_id', $targetCompanyId);
            })->orWhere(function ($q) use ($myCompanyId, $targetCompanyId) {
                $q->where('buyer_company_id', $targetCompanyId)->where('seller_company_id', $myCompanyId);
            })->first();

            if ($existing) {
                if ($existing->status === 'approved') {
                    return redirect()->back()->with('error', 'すでに連携中（承認済み）の企業です。');
                }
                if ($existing->status === 'pending') {
                    return redirect()->back()->with('error', 'すでに連携申請中（回答待ち）の企業です。');
                }
                // 過去に否認されている場合はステータスを pending に更新
                $existing->update(['status' => 'pending']);
            } else {
                CompanyLink::create([
                    'buyer_company_id'  => $myCompanyId,
                    'seller_company_id' => $targetCompanyId,
                    'status'            => 'pending',
                ]);
            }

            // 相手企業（販売側）の管理者へ通知メール送信
            $targetAdmins = User::where('company_id', $targetCompanyId)
                ->where('role', 'admin')
                ->where('status', 'active')
                ->get();

            foreach ($targetAdmins as $adminUser) {
                try {
                    Mail::to($adminUser->email)->send(
                        new CompanyLinkRequestedMail($company, $targetCompany, $adminUser)
                    );
                } catch (\Throwable $e) {
                    Log::error('連携案内メール送信エラー: ' . $e->getMessage());
                }
            }
            return redirect()->route('tenant.link.request_complete');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 5. 連携申請完了画面 (GET /link/request-complete)
     */
    public function requestComplete(): View
    {
        $company = $this->authorizeAdmin();

        return view('link.request_complete', [
            'title'          => '連携依頼 送信完了',
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
        ]);
    }

    /**
     * 6. 取引先企業詳細画面 (GET /link/detail/{link_id})
     */
    public function detail(int $linkId): View|RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $myCompanyId = $company->id;
        $assignedUserId = $this->getAssignedUserIdFilter();

        $link = CompanyLink::find($linkId);
        if (!$link) {
            abort(404, '該当のつながりデータが存在しません。');
        }

        // 自社所属チェック
        if ((int)$link->buyer_company_id !== $myCompanyId && (int)$link->seller_company_id !== $myCompanyId) {
            return redirect()->route('tenant.link.index')->with('error', '指定された取引先企業データが存在しないか、閲覧権限がありません。');
        }

        // 個別担当者の割当チェック
        if ($assignedUserId !== null) {
            $isAssigned = UserAssignedLink::where('company_link_id', $linkId)
                ->where('user_id', $assignedUserId)
                ->exists();
            if (!$isAssigned) {
                return redirect()->route('tenant.link.index')->with('error', '指定された取引先企業データが存在しないか、閲覧権限がありません。');
            }
        }

        $partnerCompanyId = $link->getPartnerCompanyId($myCompanyId);
        if (!$partnerCompanyId) {
            throw new \RuntimeException('不正な連携データです。');
        }

        $partnerCompany = Company::find($partnerCompanyId);
        if (!$partnerCompany) {
            abort(404, '取引先企業が見つかりません。');
        }

        $myRole = $link->getRoleForCompany($myCompanyId);

        // 担当従業員一覧（自社所属の個別割当ユーザー）
        $assignedUsers = DB::table('user_assigned_links as ual')
            ->select('ual.*', 'u.company_id', 'u.user_name', 'u.email', 'u.department', 'u.position', 'u.role', 'u.is_all_assigned')
            ->join('users as u', 'u.id', '=', 'ual.user_id')
            ->where('ual.company_link_id', $linkId)
            ->where('u.company_id', $myCompanyId)
            ->orderBy('u.user_name', 'asc')
            ->get()
            ->map(fn($item) => (array)$item)
            ->toArray();

        // 自社の個別担当可能従業員一覧（is_all_assigned = 0 かつ status = active）
        $companyUsers = User::where('company_id', $myCompanyId)
            ->where('is_all_assigned', 0)
            ->where('status', 'active')
            ->orderBy('user_name', 'asc')
            ->get();

        $assignedUserIds = array_map('intval', array_column($assignedUsers, 'user_id'));

        // 商流の熱力学：過去6か月の取引推移集計 (全伝票件数 & 注文回答完了成約金額)
        $startDate = now()->subMonths(5)->startOfMonth();
        $endDate   = now()->endOfMonth();

        $transactions = DB::table('transactions')
            ->where('company_link_id', $linkId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $key = $m->format('Y/m');
            $monthlyStats[$key] = [
                'year_month'    => $key,
                'total_count'   => 0,
                'success_count' => 0,
                'total_amount'  => 0.0,
                'success_rate'  => 0.0,
                'bar_percent'   => 0,
            ];
        }

        foreach ($transactions as $tx) {
            $ym = \Carbon\Carbon::parse($tx->created_at)->format('Y/m');
            if (isset($monthlyStats[$ym])) {
                $monthlyStats[$ym]['total_count']++;
                if ($tx->status === 'order_accepted') {
                    $monthlyStats[$ym]['success_count']++;
                    $monthlyStats[$ym]['total_amount'] += (float)$tx->total_amount;
                }
            }
        }

        $amounts = array_column($monthlyStats, 'total_amount');
        $maxAmount = max(array_merge([1.0], $amounts));
        $grandTotalCount   = 0;
        $grandSuccessCount = 0;
        $grandTotalAmount  = 0.0;

        foreach ($monthlyStats as &$ms) {
            if ($ms['total_count'] > 0) {
                $ms['success_rate'] = round(($ms['success_count'] / $ms['total_count']) * 100, 1);
            }
            $ms['bar_percent'] = min(100, (int)round(($ms['total_amount'] / $maxAmount) * 100));
            $grandTotalCount   += $ms['total_count'];
            $grandSuccessCount += $ms['success_count'];
            $grandTotalAmount  += $ms['total_amount'];
        }
        unset($ms);

        $grandSuccessRate = $grandTotalCount > 0 ? round(($grandSuccessCount / $grandTotalCount) * 100, 1) : 0.0;

        // 最新取引起票日
        $latestTxDate = DB::table('transactions')
            ->where('company_link_id', $linkId)
            ->max('created_at');

        $lastOrderBadge = 'danger';
        $lastOrderDateFormatted = '[取引無し]';
        if ($latestTxDate) {
            $dt = \Carbon\Carbon::parse($latestTxDate);
            $diffDays = (int)$dt->diffInDays(now());
            $lastOrderBadge = $diffDays <= 30 ? 'primary' : 'warning';
            $lastOrderDateFormatted = $dt->format('Y/m/d');
        }

        $thermoMetrics = [
            'monthly_stats'       => array_values($monthlyStats),
            'grand_total_count'   => $grandTotalCount,
            'grand_success_count' => $grandSuccessCount,
            'grand_total_amount'  => $grandTotalAmount,
            'grand_success_rate'  => $grandSuccessRate,
            'last_order_date'     => $lastOrderDateFormatted,
            'last_order_badge'    => $lastOrderBadge,
        ];

        return view('link.detail', [
            'title'           => '取引先企業詳細',
            'activeMenu'      => 'settings',
            'currentCompany'  => $company,
            'link'            => $link,
            'partnerCompany'  => $partnerCompany,
            'myRole'          => $myRole,
            'assignedUsers'   => $assignedUsers,
            'companyUsers'    => $companyUsers,
            'assignedUserIds' => $assignedUserIds,
            'thermoMetrics'   => $thermoMetrics,
        ]);
    }

    /**
     * 7. 連携ステータス更新 (POST /link/status/{link_id})
     */
    public function updateStatus(Request $request, int $linkId): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $myCompanyId = $company->id;
        $newStatus   = (string)$request->input('status');

        if (!in_array($newStatus, ['approved', 'denied', 'pending'], true)) {
            return redirect()->back()->with('error', '不正なステータス指定です。');
        }

        try {
            $link = CompanyLink::find($linkId);
            if (!$link) {
                return redirect()->back()->with('error', '該当する連携データが存在しません。');
            }

            // 認可チェック: 自社が buyer または seller に含まれているか
            if ((int)$link->buyer_company_id !== $myCompanyId && (int)$link->seller_company_id !== $myCompanyId) {
                return redirect()->back()->with('error', 'この連携ステータスを変更する権限がありません。');
            }

            $partnerName = '';
            $partnerId = $link->getPartnerCompanyId($myCompanyId);
            if ($partnerId) {
                $partnerCompany = Company::find($partnerId);
                if ($partnerCompany) {
                    $partnerName = '「' . $partnerCompany->name . '」の';
                }
            }

            $oldStatus = $link->status;
            $link->update(['status' => $newStatus]);

            // 承認時のみ買手側（buyer）管理者に承認完了メールを通知
            if ($newStatus === 'approved' && $oldStatus !== 'approved') {
                $buyerCompanyId = $link->buyer_company_id;
                $buyerCompany   = Company::find($buyerCompanyId);

                if ($buyerCompany) {
                    $buyerAdmins = User::where('company_id', $buyerCompanyId)
                        ->where('role', 'admin')
                        ->where('status', 'active')
                        ->get();

                    foreach ($buyerAdmins as $adminUser) {
                        try {
                            Mail::to($adminUser->email)->send(
                                new CompanyLinkApprovedMail($company, $buyerCompany, $adminUser)
                            );
                        } catch (\Throwable $e) {
                            Log::error('連携承認通知メール送信エラー: ' . $e->getMessage());
                        }
                    }
                }
            }

            $statusLabel = $newStatus === 'approved' ? '承認' : '否認（連携停止）';
            return redirect()->back()->with('success', $partnerName . '連携ステータスを「' . $statusLabel . '」に変更しました。');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * 8. 担当従業員一括更新 (POST /link/assign-users/{link_id})
     */
    public function assignUsers(Request $request, int $linkId): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $myCompanyId = $company->id;

        if (!$this->isAllAssignedUser()) {
            return redirect()->route('tenant.link.detail', ['link_id' => $linkId])
                ->with('error', '担当従業員の変更権限がありません。');
        }

        $link = CompanyLink::find($linkId);
        if (!$link || $link->getPartnerCompanyId($myCompanyId) === null) {
            abort(403, '権限がありません。');
        }

        $userIds = $request->input('user_ids', []);
        if (!is_array($userIds)) {
            $userIds = [];
        }

        // 自社所属ユーザーのみに限定
        $validUserIds = User::where('company_id', $myCompanyId)
            ->whereIn('id', array_map('intval', $userIds))
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($linkId, $validUserIds, $myCompanyId) {
            // 自社の担当割当のみクリア
            $existingCompanyUserIds = User::where('company_id', $myCompanyId)->pluck('id');
            UserAssignedLink::where('company_link_id', $linkId)
                ->whereIn('user_id', $existingCompanyUserIds)
                ->delete();

            foreach ($validUserIds as $uId) {
                UserAssignedLink::create([
                    'company_link_id' => $linkId,
                    'user_id'         => $uId,
                ]);
            }
        });

        return redirect()->route('tenant.link.detail', ['link_id' => $linkId])
            ->with('success', '担当従業員の割り当てを変更・保存しました。');
    }
}
