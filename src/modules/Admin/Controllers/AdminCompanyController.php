<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\User;
use App\Support\Prefectures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminCompanyController extends Controller
{
    /**
     * 事務局管理者の書込権限チェック（viewerは閲覧のみ）
     */
    private function checkCompanyWriteAccess(): ?RedirectResponse
    {
        $currentOwner = Auth::guard('admin')->user();
        if ($currentOwner && $currentOwner->role === 'viewer') {
            return redirect()->route('admin.corp.index')->with('error', '閲覧専用アカウントのため、登録・更新・削除権限がありません。');
        }
        return null;
    }

    /**
     * 企業一覧画面表示
     */
    public function index(Request $request): View
    {
        $keyword        = trim((string)$request->input('keyword', ''));
        $prefectureCode = $request->input('prefecture_code');
        $badgeStatus    = $request->input('badge_status');
        $status         = $request->input('status');

        $query = Company::query()
            ->leftJoin('company_badges', 'companies.id', '=', 'company_badges.company_id')
            ->select('companies.*', 'company_badges.status as badge_status');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('companies.name', 'like', "%{$keyword}%")
                    ->orWhere('companies.url_code', 'like', "%{$keyword}%")
                    ->orWhere('companies.corporate_number', 'like', "%{$keyword}%");
            });
        }

        if (!empty($prefectureCode)) {
            $query->where('companies.prefecture_code', (int)$prefectureCode);
        }

        if ($badgeStatus === 'verified') {
            $query->where('company_badges.status', 'active');
        } elseif ($badgeStatus === 'unverified') {
            $query->where(function ($q) {
                $q->whereNull('company_badges.status')
                    ->orWhere('company_badges.status', '!=', 'active');
            });
        }

        if (!empty($status)) {
            $query->where('companies.status', $status);
        }

        $query->orderBy('companies.id', 'desc');

        $perPage = 10;
        $companies = $query->paginate($perPage)->withQueryString();

        return view('admin::owncorp.index', [
            'activeMenu'     => 'owncorp',
            'title'          => '企業管理 | MESHu 事務局管理',
            'companies'      => $companies,
            'prefectures'    => Prefectures::getList(),
            'keyword'        => $keyword,
            'prefectureCode' => $prefectureCode,
            'badgeStatus'    => $badgeStatus,
            'status'         => $status,
        ]);
    }

    /**
     * 企業詳細画面表示
     */
    public function show(Request $request, int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        $company = Company::with(['parent', 'children', 'badge.verifier', 'users' => function ($q) {
            $q->orderBy('id', 'asc');
        }])->find($id);

        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        $invitationCompany = $company->invitation_company_id ? Company::find($company->invitation_company_id) : null;

        return view('admin::owncorp.show', [
            'activeMenu'        => 'owncorp',
            'title'             => "{$company->name} 詳細 | MESHu 事務局管理",
            'company'           => $company,
            'parentCompany'     => $company->parent,
            'childCompanies'    => $company->children ?? collect(),
            'invitationCompany' => $invitationCompany,
            'badge'             => $company->badge,
            'users'             => $company->users ?? collect(),
            'prefectures'       => Prefectures::getList(),
        ]);
    }

    /**
     * 企業新規登録画面表示
     */
    public function registration(): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        return view('admin::owncorp.registration', [
            'activeMenu'  => 'owncorp',
            'title'       => '企業新規登録 | MESHu 事務局管理',
            'prefectures' => Prefectures::getList(),
            'formData'    => session('temp_company_data', []),
            'postData'    => session('temp_company_data', []),
            'isEdit'      => false,
        ]);
    }

    /**
     * 企業登録・更新の確認画面表示（新規/編集共通）
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $postData = $request->all();
        $isEdit = !empty($postData['id']);
        $companyId = $isEdit ? (int)$postData['id'] : null;

        $rules = [
            'name'             => ['required', 'string', 'min:2', 'max:255'],
            'abbreviated_kana' => ['required', 'string', 'regex:/^[^\x{3041}-\x{3096}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]+$/u'],
            'abbreviated_name' => ['required', 'string', 'max:255'],
            'tel'              => ['required', 'string', 'max:50'],
            'zip_code'         => ['required', 'string', 'max:20'],
            'prefecture_code'  => ['required', 'integer', 'min:1', 'max:47'],
            'city'             => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:255'],
            'url_code'         => [
                'required',
                'string',
                'min:3',
                'max:50',
                'alpha_dash',
                $isEdit ? "unique:companies,url_code,{$companyId}" : 'unique:companies,url_code',
            ],
        ];

        $messages = [
            'name.required'             => '企業名・法人名は必須項目です。',
            'name.min'                  => '企業名・法人名は2文字以上で入力してください。',
            'abbreviated_kana.required' => '企業名カナは必須項目です。',
            'abbreviated_kana.regex'    => '企業名カナに「ひらがな」「漢字」を含めることはできません。カタカナ等で入力してください。',
            'abbreviated_name.required' => '法人名略称は必須項目です。',
            'tel.required'              => '電話番号は必須項目です。',
            'zip_code.required'         => '郵便番号は必須項目です。',
            'prefecture_code.required'  => '都道府県は必須項目です。',
            'prefecture_code.integer'   => '都道府県を選択してください。',
            'city.required'             => '市区町村は必須項目です。',
            'address.required'          => '番地・建物名等は必須項目です。',
            'url_code.required'         => 'URL識別コードは必須項目です。',
            'url_code.alpha_dash'       => 'URL識別コードは半角英数字、ダッシュ(-)、アンダースコア(_)のみ使用できます。',
            'url_code.unique'           => '指定されたURL識別コードは既に使用されています。',
        ];

        if (!$isEdit) {
            $rules['user_name'] = ['required', 'string', 'max:100'];
            $rules['email']     = ['required', 'string', 'email', 'max:255'];
            $rules['password']  = ['required', 'string', 'min:8'];

            $messages['user_name.required'] = '初期管理者名は必須項目です。';
            $messages['email.required']     = '初期管理者メールアドレスは必須項目です。';
            $messages['email.email']        = '有効なメールアドレス形式で入力してください。';
            $messages['password.required']  = '初期管理者パスワードは必須項目です。';
            $messages['password.min']       = 'パスワードは8文字以上で入力してください。';
        }

        $request->validate($rules, $messages);

        session(['temp_company_data' => $postData]);

        return view('admin::owncorp.confirm', [
            'activeMenu'     => 'owncorp',
            'title'          => ($isEdit ? '企業情報更新確認' : '企業登録確認') . ' | MESHu 事務局管理',
            'postData'       => $postData,
            'isEdit'         => $isEdit,
            'companyId'      => $companyId,
            'prefectures'    => Prefectures::getList(),
            'prefectureName' => Prefectures::getNameByCode((int)$postData['prefecture_code']),
        ]);
    }

    /**
     * 企業登録・更新の完了処理（DB保存）
     */
    public function complete(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $postData = session('temp_company_data');
        if (empty($postData)) {
            return redirect()->route('admin.corp.index')->with('error', 'セッションの有効期限が切れました。最初からやり直してください。');
        }

        $isEdit = !empty($postData['id']);
        $companyId = $isEdit ? (int)$postData['id'] : null;

        $companyData = [
            'name'             => $postData['name'],
            'abbreviated_name' => $postData['abbreviated_name'],
            'abbreviated_kana' => $postData['abbreviated_kana'],
            'sub_name'         => $postData['sub_name'] ?? null,
            'corporate_number' => $postData['corporate_number'] ?? null,
            'url_code'         => $postData['url_code'],
            'zip_code'         => $postData['zip_code'],
            'prefecture_code'  => (int)$postData['prefecture_code'],
            'city'             => $postData['city'],
            'address'          => $postData['address'],
            'homepage'         => $postData['homepage'] ?? null,
            'tel'              => $postData['tel'],
            'tax_rate'         => !empty($postData['tax_rate']) ? (int)$postData['tax_rate'] : 10,
            'amount_rounding'  => $postData['amount_rounding'] ?? 'round',
            'tax_rounding'     => $postData['tax_rounding'] ?? 'round',
            'status'           => $postData['status'] ?? 'active',
        ];

        DB::beginTransaction();
        try {
            if ($isEdit) {
                $company = Company::find($companyId);
                if (!$company) {
                    throw new \Exception('更新対象の企業が存在しません。');
                }
                $company->update($companyData);
                $savedCompanyId = $company->id;
            } else {
                $company = Company::create($companyData);
                $savedCompanyId = $company->id;

                User::create([
                    'company_id'      => $savedCompanyId,
                    'email'           => $postData['email'],
                    'password_hash'   => Hash::make($postData['password']),
                    'user_name'       => $postData['user_name'],
                    'user_name_kana'  => $postData['user_name_kana'] ?? null,
                    'department'      => $postData['department'] ?? null,
                    'position'        => $postData['position'] ?? null,
                    'role'            => 'admin',
                    'status'          => 'active',
                    'is_all_assigned' => 1,
                    'notify_trade'    => true,
                    'notify_comment'  => true,
                    'notify_chat'     => true,
                ]);
            }

            // 認証バッジの更新/付与
            $grantBadge = !empty($postData['grant_badge']) && (string)$postData['grant_badge'] === '1';
            $currentBadge = CompanyBadge::where('company_id', $savedCompanyId)->first();

            if ($grantBadge && !$currentBadge) {
                $currentOwner = Auth::guard('admin')->user();
                CompanyBadge::create([
                    'company_id'        => $savedCompanyId,
                    'verifier_owner_id' => $currentOwner?->id,
                    'status'            => 'active',
                    'signature'         => hash('sha256', "MESHu:Badge:{$savedCompanyId}:" . ($currentOwner?->id ?? 0) . ':' . now()->toIso8601String()),
                ]);
            } elseif (!$grantBadge && $currentBadge && $isEdit) {
                $currentBadge->delete();
            }

            DB::commit();
            session()->forget('temp_company_data');

            return view('admin::owncorp.complete', [
                'activeMenu' => 'owncorp',
                'title'      => ($isEdit ? '企業情報更新完了' : '企業登録完了') . ' | MESHu 事務局管理',
                'isEdit'     => $isEdit,
                'companyId'  => $savedCompanyId,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.corp.index')->with('error', 'データベース保存中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 企業情報編集画面表示
     */
    public function edit(Request $request, int|string $id): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::with('badge')->find((int)$id);
        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        return view('admin::owncorp.edit', [
            'activeMenu'  => 'owncorp',
            'title'       => "{$company->name} 編集 | MESHu 事務局管理",
            'company'     => $company,
            'badge'       => $company->badge,
            'prefectures' => Prefectures::getList(),
            'isEdit'      => true,
        ]);
    }

    /**
     * 企業情報更新処理
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find((int)$id);
        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        $request->merge(['id' => (int)$id]);
        return $this->confirm($request);
    }

    /**
     * 企業削除確認画面表示
     */
    public function deleteConfirm(Request $request, int|string $id): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find((int)$id);
        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        return view('admin::owncorp.delete', [
            'activeMenu' => 'owncorp',
            'title'      => "{$company->name} 削除確認 | MESHu 事務局管理",
            'company'    => $company,
        ]);
    }

    /**
     * 企業ステータス変更・削除処理
     */
    public function delete(Request $request, int|string $id): RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find((int)$id);
        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        $newStatus = $request->input('status');
        if (!in_array($newStatus, ['active', 'suspended', 'deleted'], true)) {
            return redirect()->back()->with('error', '不正なステータスが指定されました。');
        }

        $companyName = $company->name;

        DB::beginTransaction();
        try {
            $company->update(['status' => $newStatus]);

            // 企業ステータスに応じて所属ユーザーのステータスも同期
            if ($newStatus === 'deleted') {
                $company->users()->update(['status' => 'deleted']);
                $message = "企業「{$companyName}」を削除状態に更新しました。";
            } elseif ($newStatus === 'suspended') {
                $company->users()->where('status', 'active')->update(['status' => 'suspended']);
                $message = "企業「{$companyName}」のステータスを「一時停止」に更新しました。";
            } else { // active
                $company->users()->where('status', 'suspended')->update(['status' => 'active']);
                $message = "企業「{$companyName}」のステータスを「稼働」に更新しました。";
            }

            DB::commit();

            return redirect()->route('admin.corp.show', ['id' => $company->id])->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.corp.index')->with('error', 'ステータス更新中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * 認証バッジのトグル（付与 / 剥奪）
     */
    public function toggleBadge(Request $request, int|string $id): RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $id = (int)$id;
        $company = Company::with('badge')->find($id);
        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        $currentOwner = Auth::guard('admin')->user();
        $badge = $company->badge;

        if ($badge) {
            $badge->delete();
            $message = "企業「{$company->name}」の実在認証バッジを解除しました。";
        } else {
            CompanyBadge::create([
                'company_id'        => $company->id,
                'verifier_owner_id' => $currentOwner?->id,
                'status'            => 'active',
                'signature'         => hash('sha256', "MESHu:Badge:{$company->id}:" . ($currentOwner?->id ?? 0) . ':' . now()->toIso8601String()),
            ]);
            $message = "企業「{$company->name}」に実在認証バッジを付与しました。";
        }

        return redirect()->route('admin.corp.show', ['id' => $id])->with('success', $message);
    }

    /**
     * 代理ログイン処理（事務局から対象企業の初期管理者としてログイン）
     */
    public function impersonate(Request $request, int|string $id): RedirectResponse
    {
        $id = (int)$id;
        $company = Company::with(['users' => function ($q) {
            $q->orderBy('id', 'asc');
        }])->find($id);

        if (!$company) {
            return redirect()->route('admin.corp.index')->with('error', '指定された企業が見つかりません。');
        }

        $adminUser = $company->users->firstWhere('role', 'admin') ?? $company->users->first();

        if (!$adminUser) {
            return redirect()->route('admin.corp.index')->with('error', 'この企業にはログイン可能なユーザーアカウントが存在しません。');
        }

        // 企業ユーザーとしてログイン（adminセッションは保持したまま）
        Auth::guard('web')->login($adminUser);

        // 代理ログインであることをセッションにフラグ保持
        session([
            'impersonated_by_admin' => true,
            'is_impersonating'      => true,
            'current_url_code'      => $company->url_code,
        ]);

        return redirect()->route('dashboard')->with('info', "【代理ログイン中】{$company->name}（{$adminUser->user_name} 様）としてログインしました。");
    }
}
