<?php

namespace App\Http\Controllers\Admin;

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
use Illuminate\View\View;

class AdminCompanyController extends Controller
{
    /**
     * アクセス権限チェック（viewerは更新全般不可、admin以外はステータス変更・削除不可）
     */
    private function checkCompanyWriteAccess(bool $isDelete = false): ?RedirectResponse
    {
        $owner = Auth::guard('admin')->user();
        $role = $owner->role ?? session('admin_owner_role') ?? 'viewer';

        if ($isDelete) {
            if ($role !== 'admin') {
                return redirect()->route('admin.corp.index')
                    ->with('error', '企業情報の削除・ステータス変更権限がありません。（最高管理者のみ実行可能）');
            }
            return null;
        }

        if (!in_array($role, ['admin', 'reviewer'], true)) {
            return redirect()->route('admin.corp.index')
                ->with('error', '閲覧専用アカウントのため、この操作を行う権限がありません。');
        }

        return null;
    }

    /**
     * 企業一覧表示
     */
    public function index(Request $request): View
    {
        $keyword        = $request->query('keyword');
        $prefectureCode = $request->query('prefecture_code');
        $badgeStatus    = $request->query('badge_status');
        $status         = $request->query('status');

        $query = Company::query()
            ->select('companies.*', 'company_badges.status as badge_status')
            ->leftJoin('company_badges', 'company_badges.company_id', '=', 'companies.id')
            ->where('companies.status', '!=', 'deleted');

        if (!empty($keyword)) {
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

        return view('admin.owncorp.index', [
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
     * 新規企業登録フォーム表示
     */
    public function registration(): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $parentCompanies = Company::whereNull('parent_id')
            ->where('status', '!=', 'deleted')
            ->get();

        return view('admin.owncorp.registration', [
            'activeMenu'      => 'owncorp',
            'title'           => '新規企業登録 | MESHu 事務局管理',
            'prefectures'     => Prefectures::getList(),
            'parentCompanies' => $parentCompanies,
            'formData'        => session('formData', []),
            'errors'          => session('errors') ? session('errors')->getBag('default') : [],
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
            'address.required'          => '番地・建物名などは必須項目です。',
            'url_code.required'         => '専用ログインURL識別子は必須項目です。',
            'url_code.min'              => '専用ログインURL識別子は3文字以上で入力してください。',
            'url_code.alpha_dash'       => '専用ログインURL識別子は半角英数字とハイフンのみ使用可能です。',
            'url_code.unique'           => '入力された専用ログインURL識別子は既に使用されています。',
        ];

        if (!empty($postData['corporate_number'])) {
            $rules['corporate_number'] = [
                'digits:13',
                $isEdit ? "unique:companies,corporate_number,{$companyId}" : 'unique:companies,corporate_number',
            ];
            $messages['corporate_number.digits'] = '法人番号は13桁の半角数字で入力してください。';
            $messages['corporate_number.unique'] = '入力された法人番号は既に使用・登録されています。';
        }

        // 新規登録時のみ管理者アカウント情報が必須
        if (!$isEdit) {
            $rules['user_name'] = ['required', 'string', 'max:255'];
            $rules['email']     = ['required', 'string', 'email', 'max:255', 'unique:users,email'];
            $rules['password']  = ['required', 'string', 'min:8'];

            $messages['user_name.required'] = '管理者担当者名は必須項目です。';
            $messages['email.required']     = 'メールアドレスは必須項目です。';
            $messages['email.email']        = '有効なメールアドレス形式で入力してください。';
            $messages['email.unique']       = '入力されたメールアドレスは既に使用されています。';
            $messages['password.required']  = '初期パスワードは必須項目です。';
            $messages['password.min']       = '初期パスワードは8文字以上で入力してください。';
        }

        $request->validate($rules, $messages);

        return view('admin.owncorp.confirm', [
            'activeMenu'  => 'owncorp',
            'title'       => ($isEdit ? '企業情報更新確認' : '企業登録確認') . ' | MESHu 事務局管理',
            'prefectures' => Prefectures::getList(),
            'postData'    => $postData,
            'isEdit'      => $isEdit,
            'companyId'   => $companyId,
        ]);
    }

    /**
     * 新規企業登録実行
     */
    public function complete(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $postData = $request->all();

        $companyData = [
            'parent_id'        => null,
            'url_code'         => $postData['url_code'],
            'corporate_number' => $postData['corporate_number'] ?? null,
            'name'             => $postData['name'],
            'abbreviated_name' => $postData['abbreviated_name'] ?? null,
            'abbreviated_kana' => $postData['abbreviated_kana'] ?? null,
            'sub_name'         => $postData['sub_name'] ?? null,
            'zip_code'         => $postData['zip_code'] ?? null,
            'prefecture_code'  => !empty($postData['prefecture_code']) ? (int)$postData['prefecture_code'] : null,
            'city'             => $postData['city'] ?? null,
            'address'          => $postData['address'] ?? '',
            'homepage'         => $postData['homepage'] ?? null,
            'tel'              => $postData['tel'] ?? null,
            'status'           => $postData['status'] ?? 'active',
        ];

        $userData = [
            'user_name'  => $postData['user_name'] ?? '',
            'email'      => $postData['email'] ?? '',
            'password'   => $postData['password'] ?? '',
            'department' => $postData['department'] ?? null,
            'position'   => $postData['position'] ?? null,
        ];

        DB::beginTransaction();
        try {
            $company = Company::create($companyData);

            $user = User::create([
                'company_id'      => $company->id,
                'email'           => $userData['email'],
                'password_hash'   => Hash::make($userData['password']),
                'user_name'       => $userData['user_name'],
                'department'      => $userData['department'],
                'position'        => $userData['position'],
                'role'            => 'admin',
                'status'          => 'active',
                'is_all_assigned' => 0,
            ]);

            if (!empty($postData['grant_badge']) && $postData['grant_badge'] == '1') {
                $owner = Auth::guard('admin')->user();
                $ownerId = $owner ? $owner->id : (session('admin_owner_id') ?? 1);
                CompanyBadge::create([
                    'company_id'        => $company->id,
                    'verifier_owner_id' => $ownerId,
                    'status'            => 'active',
                    'signature'         => hash('sha256', $company->id . '_' . time()),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.corp.registration')
                ->withInput()
                ->with('error', '企業の保存に失敗しました: ' . $e->getMessage());
        }

        return view('admin.owncorp.complete', [
            'activeMenu'  => 'owncorp',
            'title'       => '企業登録完了 | MESHu 事務局管理',
            'companyName' => $postData['name'],
            'isEdit'      => false,
            'companyId'   => $company->id,
        ]);
    }

    /**
     * 企業詳細表示
     */
    public function show(int $id): View|RedirectResponse
    {
        $company = Company::find($id);
        if (!$company || $company->status === 'deleted') {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        $users = User::where('company_id', $id)->get();
        $badge = CompanyBadge::where('company_id', $id)->first();
        $parentCompany = $company->parent_id ? Company::find($company->parent_id) : null;
        $invitationCompany = !empty($company->invitation_company_id) ? Company::find($company->invitation_company_id) : null;
        $childCompanies = Company::where('parent_id', $id)->where('status', '!=', 'deleted')->get();

        return view('admin.owncorp.show', [
            'activeMenu'        => 'owncorp',
            'title'             => $company->name . ' 詳細 | MESHu 事務局管理',
            'company'           => $company,
            'users'             => $users,
            'badge'             => $badge,
            'parentCompany'     => $parentCompany,
            'invitationCompany' => $invitationCompany,
            'childCompanies'    => $childCompanies,
            'prefectures'       => Prefectures::getList(),
        ]);
    }

    /**
     * 企業編集フォーム表示
     */
    public function edit(int $id): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find($id);
        if (!$company || $company->status === 'deleted') {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        $parentCompanies = Company::whereNull('parent_id')
            ->where('id', '!=', $id)
            ->where('status', '!=', 'deleted')
            ->get();

        $badge = CompanyBadge::where('company_id', $id)->first();

        return view('admin.owncorp.edit', [
            'activeMenu'      => 'owncorp',
            'title'           => $company->name . ' 編集 | MESHu 事務局管理',
            'company'         => $company,
            'prefectures'     => Prefectures::getList(),
            'parentCompanies' => $parentCompanies,
            'badge'           => $badge,
        ]);
    }

    /**
     * 企業情報更新実行
     */
    public function update(Request $request, int $id): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find($id);
        if (!$company || $company->status === 'deleted') {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        $postData = $request->all();

        $rules = [
            'name'             => ['required', 'string', 'min:2', 'max:255'],
            'abbreviated_kana' => ['required', 'string', 'regex:/^[^\x{3041}-\x{3096}\x{4E00}-\x{9FFF}\x{3400}-\x{4DBF}]+$/u'],
            'abbreviated_name' => ['required', 'string', 'max:255'],
            'tel'              => ['required', 'string', 'max:50'],
            'zip_code'         => ['required', 'string', 'max:20'],
            'prefecture_code'  => ['required', 'integer', 'min:1', 'max:47'],
            'city'             => ['required', 'string', 'max:255'],
            'address'          => ['required', 'string', 'max:255'],
            'url_code'         => ['required', 'string', 'min:3', 'max:50', 'alpha_dash', "unique:companies,url_code,{$id}"],
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
            'address.required'          => '番地・建物名などは必須項目です。',
            'url_code.required'         => '専用ログインURL識別子は必須項目です。',
            'url_code.min'              => '専用ログインURL識別子は3文字以上で入力してください。',
            'url_code.alpha_dash'       => '専用ログインURL識別子は半角英数字とハイフンのみ使用可能です。',
            'url_code.unique'           => '入力された専用ログインURL識別子は既に使用されています。',
        ];

        if (!empty($postData['corporate_number'])) {
            $rules['corporate_number'] = ['digits:13', "unique:companies,corporate_number,{$id}"];
            $messages['corporate_number.digits'] = '法人番号は13桁の半角数字で入力してください。';
            $messages['corporate_number.unique'] = '入力された法人番号は既に使用・登録されています。';
        }

        $request->validate($rules, $messages);

        $updateData = [
            'parent_id'        => !empty($postData['parent_id']) ? (int)$postData['parent_id'] : null,
            'url_code'         => $postData['url_code'],
            'corporate_number' => $postData['corporate_number'] ?? null,
            'name'             => $postData['name'],
            'abbreviated_name' => $postData['abbreviated_name'] ?? null,
            'abbreviated_kana' => $postData['abbreviated_kana'] ?? null,
            'sub_name'         => $postData['sub_name'] ?? null,
            'zip_code'         => $postData['zip_code'] ?? null,
            'prefecture_code'  => !empty($postData['prefecture_code']) ? (int)$postData['prefecture_code'] : null,
            'city'             => $postData['city'] ?? null,
            'address'          => $postData['address'] ?? '',
            'homepage'         => $postData['homepage'] ?? null,
            'tel'              => $postData['tel'] ?? null,
            'status'           => $postData['status'] ?? $company->status,
        ];

        DB::beginTransaction();
        try {
            $company->update($updateData);

            // バッジトグル更新
            $existingBadge = CompanyBadge::where('company_id', $id)->first();
            $wantBadge = !empty($postData['grant_badge']) && $postData['grant_badge'] == '1';
            $owner = Auth::guard('admin')->user();
            $ownerId = $owner ? $owner->id : (session('admin_owner_id') ?? 1);

            if ($wantBadge && !$existingBadge) {
                CompanyBadge::create([
                    'company_id'        => $id,
                    'verifier_owner_id' => $ownerId,
                    'status'            => 'active',
                    'signature'         => hash('sha256', $id . '_' . time()),
                ]);
            } elseif (!$wantBadge && $existingBadge) {
                $existingBadge->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.corp.edit', ['id' => $id])
                ->withInput()
                ->with('error', '企業情報の更新に失敗しました: ' . $e->getMessage());
        }

        return view('admin.owncorp.complete', [
            'activeMenu'  => 'owncorp',
            'title'       => '企業情報更新完了 | MESHu 事務局管理',
            'companyName' => $postData['name'],
            'isEdit'      => true,
            'companyId'   => $id,
        ]);
    }

    /**
     * 企業削除/ステータス変更確認画面表示
     */
    public function deleteConfirm(int $id): View|RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess(true)) {
            return $redirect;
        }

        $company = Company::find($id);
        if (!$company || $company->status === 'deleted') {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        return view('admin.owncorp.delete', [
            'activeMenu' => 'owncorp',
            'title'      => $company->name . ' ステータス設定・削除確認 | MESHu 事務局管理',
            'company'    => $company,
        ]);
    }

    /**
     * 企業ステータス変更/削除実行
     */
    public function delete(Request $request, int $id): RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess(true)) {
            return $redirect;
        }

        $company = Company::find($id);
        if (!$company) {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        $newStatus = $request->input('status', 'deleted');
        $company->update(['status' => $newStatus]);

        $statusMsg = [
            'active'    => '企業のアカウント稼働状態（有効）に変更しました。',
            'suspended' => '企業のアカウントを停止状態に変更しました。',
            'deleted'   => '企業を削除状態に変更しました。',
        ][$newStatus] ?? 'ステータスを変更しました。';

        return redirect()->route('admin.corp.index')
            ->with('success', $statusMsg);
    }

    /**
     * 認証バッジ付与・トグル
     */
    public function toggleBadge(int $id): RedirectResponse
    {
        if ($redirect = $this->checkCompanyWriteAccess()) {
            return $redirect;
        }

        $company = Company::find($id);
        if (!$company) {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        $existingBadge = CompanyBadge::where('company_id', $id)->first();
        $owner = Auth::guard('admin')->user();
        $ownerId = $owner ? $owner->id : (session('admin_owner_id') ?? 1);

        if ($existingBadge) {
            if ($existingBadge->status === 'active') {
                $existingBadge->update(['status' => 'revoked']);
                $msg = 'MESHu認証バッジを解除しました。';
            } else {
                $existingBadge->update(['status' => 'active']);
                $msg = 'MESHu認証バッジを有効化しました。';
            }
        } else {
            CompanyBadge::create([
                'company_id'        => $id,
                'verifier_owner_id' => $ownerId,
                'status'            => 'active',
                'signature'         => hash('sha256', $id . '_' . time()),
            ]);
            $msg = 'MESHu認証バッジを付与しました。';
        }

        return redirect()->route('admin.corp.show', ['id' => $id])
            ->with('success', $msg);
    }

    /**
     * 代理ログイン機能（指定企業の最初IDの管理者として代理ログイン）
     */
    public function impersonate(int $id): RedirectResponse
    {
        $company = Company::find($id);
        if (!$company) {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業が見つかりません。');
        }

        // 対象企業に所属する管理者（role = 'admin'）の中で最も若いIDのユーザーを取得
        $adminUser = User::where('company_id', $id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->first();

        if (!$adminUser) {
            $adminUser = User::where('company_id', $id)
                ->where('role', 'admin')
                ->orderBy('id', 'asc')
                ->first();
        }

        if (!$adminUser) {
            return redirect()->route('admin.corp.index')
                ->with('error', '指定された企業には管理者アカウントが登録されていません。');
        }

        // 代理ログイン実行（webガードでログイン）
        Auth::guard('web')->login($adminUser);
        session([
            'is_impersonating'      => true,
            'impersonator_owner_id' => Auth::guard('admin')->id() ?? session('admin_owner_id'),
            'user_id'               => $adminUser->id,
            'user_name'             => $adminUser->user_name,
            'user_email'            => $adminUser->email,
            'user_role'             => $adminUser->role,
            'role'                  => $adminUser->role,
            'company_id'            => $company->id,
            'company_name'          => $company->name,
            'company_url_code'      => $company->url_code,
            'is_user_logged_in'     => true,
        ]);

        return redirect()->route('dashboard');
    }
}
