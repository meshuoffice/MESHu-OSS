<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * テナント整合性および自社管理者権限の検証
     */
    private function authorizeAdmin(): Company
    {
        $user = Auth::guard('web')->user();

        // 1. 管理者権限チェック (role === 'admin')
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
     * 自社所属の対象ユーザーを取得（他社ユーザーの越境アクセスを404遮断）
     */
    private function getTenantUser(Company $company, int $userId): User
    {
        return User::where('company_id', $company->id)->where('id', $userId)->firstOrFail();
    }

    /**
     * 従業員一覧・検索
     */
    public function index(Request $request): View
    {
        $company = $this->authorizeAdmin();
        $currentUser = Auth::guard('web')->user();

        $searchWord = trim((string)$request->query('search', ''));
        $role = trim((string)$request->query('role', ''));
        $status = trim((string)$request->query('status', ''));

        $query = User::where('company_id', $company->id)
            ->where('id', '!=', $currentUser->id);

        if ($searchWord !== '') {
            $query->where(function ($q) use ($searchWord) {
                $q->where('user_name', 'like', "%{$searchWord}%")
                  ->orWhere('user_name_kana', 'like', "%{$searchWord}%")
                  ->orWhere('email', 'like', "%{$searchWord}%");
            });
        }

        if ($role !== '') {
            $query->where('role', $role);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $users = $query->orderBy('id', 'asc')->get();

        return view('settings.users.index', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'currentUser'    => $currentUser,
            'users'          => $users,
            'searchWord'     => $searchWord,
            'selectedRole'   => $role,
            'selectedStatus' => $status,
        ]);
    }

    /**
     * 新規従業員登録画面
     */
    public function create(): View
    {
        $company = $this->authorizeAdmin();

        return view('settings.users.new', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
        ]);
    }

    /**
     * 新規登録確認（バリデーション実行）
     */
    public function confirmCreate(Request $request): View|RedirectResponse
    {
        $company = $this->authorizeAdmin();

        $rules = [
            'user_name'       => ['required', 'string', 'max:100'],
            'user_name_kana'  => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'email'           => [
                'required',
                'email:filter',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'role'            => ['required', 'in:admin,staff'],
            'status'          => ['required', 'in:active,suspended'],
            'is_all_assigned' => ['required', 'in:0,1'],
        ];

        $messages = [
            'user_name.required'       => '名前は必須項目です。',
            'user_name_kana.required'  => '名前カナは必須項目です。',
            'user_name_kana.regex'     => '名前カナは全角カナで入力してください。',
            'email.required'           => 'ログインID(メールアドレス)は必須項目です。',
            'email.email'              => '有効なメールアドレス形式で入力してください。',
            'email.unique'             => 'このメールアドレスは社内で既に登録されています。',
            'role.required'            => 'アクセス権限を選択してください。',
            'status.required'          => 'アカウントステータスを選択してください。',
            'is_all_assigned.required' => '担当区分を選択してください。',
        ];

        $validated = $request->validate($rules, $messages);

        if ($validated['role'] === 'admin') {
            $validated['is_all_assigned'] = 1;
        }

        return view('settings.users.confirm', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'postData'       => $validated,
            'isModeCreate'   => true,
        ]);
    }

    /**
     * 新規登録実行（仮パスワード自動生成 & DB保存）
     */
    public function store(Request $request): RedirectResponse
    {
        $company = $this->authorizeAdmin();

        $validated = $request->validate([
            'user_name'       => ['required', 'string', 'max:100'],
            'user_name_kana'  => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'email'           => [
                'required',
                'email:filter',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('company_id', $company->id)),
            ],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'role'            => ['required', 'in:admin,staff'],
            'status'          => ['required', 'in:active,suspended'],
            'is_all_assigned' => ['required', 'in:0,1'],
        ]);

        $generatedPassword = bin2hex(random_bytes(6)); // 12桁の仮パスワード生成

        $user = new User();
        $user->company_id      = $company->id;
        $user->email           = $validated['email'];
        $user->user_name       = $validated['user_name'];
        $user->user_name_kana  = $validated['user_name_kana'];
        $user->department      = $validated['department'] ?? null;
        $user->position        = $validated['position'] ?? null;
        $user->role            = $validated['role'];
        $user->status          = $validated['status'];
        $user->is_all_assigned = ($validated['role'] === 'admin') ? 1 : (int)$validated['is_all_assigned'];
        $user->password_hash   = Hash::make($generatedPassword);
        $user->save();

        // 初期パスワード通知メール
        $this->sendNotificationEmail(
            $user->email,
            '【MESHu】アカウント登録と初期パスワードのお知らせ',
            "{$user->user_name} 様\n\nMESHu のアカウント登録が完了いたしました。\n以下の初期仮パスワードにてログインし、マイプロフィールよりパスワード変更を行ってください。\n\n■ ログイン情報\n・ログインID: {$user->email}\n・初期仮パスワード: {$generatedPassword}\n\n----------------------------------------\nMESHu プラットフォーム"
        );

        $flashMessage = "登録完了：{$user->user_name} 様宛（{$user->email}）に初期仮パスワード通知メールを送信いたしました。[ 初期仮パスワード: {$generatedPassword} ]";

        return redirect()->route('tenant.users.complete')
            ->with('success_info', $flashMessage);
    }

    /**
     * 従業員詳細画面
     */
    public function show(int $id): View
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $allApprovedLinks = CompanyLink::where('status', 'approved')
            ->where(function ($q) use ($company) {
                $q->where('buyer_company_id', $company->id)
                  ->orWhere('seller_company_id', $company->id);
            })
            ->with(['buyerCompany', 'sellerCompany'])
            ->get()
            ->map(function ($link) use ($company) {
                $partner = ($link->buyer_company_id === $company->id) ? $link->sellerCompany : $link->buyerCompany;
                return [
                    'link_id'              => $link->id,
                    'my_role'              => ($link->buyer_company_id === $company->id) ? 'buyer' : 'seller',
                    'partner_name'         => $partner->name,
                    'partner_address'      => $partner->address,
                    'partner_full_address' => "{$partner->city} {$partner->address}",
                    'is_authenticated'     => false,
                ];
            })->toArray();

        $assignedLinkIds = UserAssignedLink::where('user_id', $targetUser->id)
            ->pluck('company_link_id')
            ->toArray();

        return view('settings.users.show', [
            'activeMenu'       => 'settings',
            'currentCompany'   => $company,
            'targetUser'       => $targetUser,
            'allApprovedLinks' => $allApprovedLinks,
            'assignedLinkIds'  => $assignedLinkIds,
        ]);
    }

    /**
     * 従業員編集画面
     */
    public function edit(int $id): View
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        return view('settings.users.edit', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
        ]);
    }

    /**
     * 編集確認（バリデーション実行）
     */
    public function confirmUpdate(Request $request, int $id): View|RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $rules = [
            'user_name'       => ['required', 'string', 'max:100'],
            'user_name_kana'  => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'email'           => [
                'required',
                'email:filter',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($targetUser->id),
            ],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'role'            => ['required', 'in:admin,staff'],
            'status'          => ['required', 'in:active,suspended'],
            'is_all_assigned' => ['required', 'in:0,1'],
        ];

        $messages = [
            'user_name.required'       => '名前は必須項目です。',
            'user_name_kana.required'  => '名前カナは必須項目です。',
            'user_name_kana.regex'     => '名前カナは全角カナで入力してください。',
            'email.required'           => 'ログインID(メールアドレス)は必須項目です。',
            'email.email'              => '有効なメールアドレス形式で入力してください。',
            'email.unique'             => 'このメールアドレスは社内で既に登録されています。',
            'role.required'            => 'アクセス権限を選択してください。',
            'status.required'          => 'アカウントステータスを選択してください。',
            'is_all_assigned.required' => '担当区分を選択してください。',
        ];

        $validated = $request->validate($rules, $messages);

        if ($validated['role'] === 'admin') {
            $validated['is_all_assigned'] = 1;
        }

        return view('settings.users.confirm', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
            'postData'       => $validated,
            'isModeCreate'   => false,
        ]);
    }

    /**
     * 編集実行（DB更新）
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $validated = $request->validate([
            'user_name'       => ['required', 'string', 'max:100'],
            'user_name_kana'  => ['required', 'string', 'regex:/^[ァ-ヶー\s]+$/u'],
            'email'           => [
                'required',
                'email:filter',
                Rule::unique('users', 'email')
                    ->where(fn ($query) => $query->where('company_id', $company->id))
                    ->ignore($targetUser->id),
            ],
            'department'      => ['nullable', 'string', 'max:100'],
            'position'        => ['nullable', 'string', 'max:100'],
            'role'            => ['required', 'in:admin,staff'],
            'status'          => ['required', 'in:active,suspended'],
            'is_all_assigned' => ['required', 'in:0,1'],
        ]);

        $targetUser->email           = $validated['email'];
        $targetUser->user_name       = $validated['user_name'];
        $targetUser->user_name_kana  = $validated['user_name_kana'];
        $targetUser->department      = $validated['department'] ?? null;
        $targetUser->position        = $validated['position'] ?? null;
        $targetUser->role            = $validated['role'];
        $targetUser->status          = $validated['status'];
        $targetUser->is_all_assigned = ($validated['role'] === 'admin') ? 1 : (int)$validated['is_all_assigned'];
        $targetUser->save();

        // 自社管理者になった場合は個別担当の不要な割り当てをクリア
        if ($targetUser->role === 'admin') {
            UserAssignedLink::where('user_id', $targetUser->id)->delete();
        }

        return redirect()->route('tenant.users.complete')
            ->with('success_info', "従業員 [ {$targetUser->user_name} ] 様のアカウント情報を更新しました。");
    }

    /**
     * 担当取引先企業の割り当て更新
     */
    public function assignLinks(Request $request, int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $linkIds = $request->input('company_link_ids', []);
        if (!is_array($linkIds)) {
            $linkIds = [];
        }

        // 自社に関連する承認済みリンクのみ許可
        $validLinkIds = CompanyLink::where('status', 'approved')
            ->where(function ($q) use ($company) {
                $q->where('buyer_company_id', $company->id)
                  ->orWhere('seller_company_id', $company->id);
            })
            ->whereIn('id', $linkIds)
            ->pluck('id')
            ->toArray();

        DB::transaction(function () use ($targetUser, $validLinkIds) {
            UserAssignedLink::where('user_id', $targetUser->id)->delete();
            foreach ($validLinkIds as $linkId) {
                UserAssignedLink::create([
                    'user_id'         => $targetUser->id,
                    'company_link_id' => $linkId,
                ]);
            }
        });

        return redirect()->route('tenant.users.show', ['user' => $targetUser->id])
            ->with('success', '担当取引先企業の割り当てを変更・保存しました。');
    }

    /**
     * パスワード再発行画面
     */
    public function passwordEdit(int $id): View
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        return view('settings.users.password_edit', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
        ]);
    }

    /**
     * パスワード再発行確認
     */
    public function confirmPassword(Request $request, int $id): View
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $validated = $request->validate([
            'email'         => ['required', 'email:filter'],
            'email_confirm' => ['required', 'same:email'],
        ], [
            'email.required'         => '送信先メールアドレスは必須項目です。',
            'email.email'            => '有効なメールアドレス形式で入力してください。',
            'email_confirm.required' => '送信先メールアドレス(確認)は必須項目です。',
            'email_confirm.same'     => '送信先メールアドレス(確認)が一致しません。',
        ]);

        return view('settings.users.password_confirm', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
            'sendEmail'      => $validated['email'],
        ]);
    }

    /**
     * パスワード再発行実行
     */
    public function sendPassword(Request $request, int $id): View
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        $validated = $request->validate([
            'email' => ['required', 'email:filter'],
        ]);

        $newTempPassword = bin2hex(random_bytes(6));
        $targetUser->password_hash = Hash::make($newTempPassword);
        $targetUser->save();

        $this->sendNotificationEmail(
            $validated['email'],
            '【MESHu】仮パスワード再発行のお知らせ',
            "{$targetUser->user_name} 様\n\n仮パスワードの再発行手続きが完了いたしました。\n\n■ 仮ログイン情報\n・ログインID: {$targetUser->email}\n・仮パスワード: {$newTempPassword}\n\nログイン後、マイプロフィール画面より新しいパスワードへの変更をお願いいたします。\n\n----------------------------------------\nMESHu プラットフォーム"
        );

        return view('settings.users.password_complete', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
            'sendEmail'      => $validated['email'],
            'tempPassword'   => $newTempPassword,
        ]);
    }

    /**
     * 従業員削除確認画面（ステータスが suspended のみ許可）
     */
    public function confirmDelete(int $id): View|RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        if ($targetUser->status !== 'suspended') {
            return redirect()->route('tenant.users.show', ['user' => $id])
                ->with('error', '停止中のアカウントのみ削除可能です。先にアカウントステータスを停止中に変更してください。');
        }

        return view('settings.users.delete_confirm', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'targetUser'     => $targetUser,
        ]);
    }

    /**
     * 従業員物理削除実行
     */
    public function destroy(int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $targetUser = $this->getTenantUser($company, $id);

        if ($targetUser->status !== 'suspended') {
            return redirect()->route('tenant.users.show', ['user' => $id])
                ->with('error', '停止中のアカウントのみ削除可能です。');
        }

        $userName = $targetUser->user_name;
        $targetUser->delete();

        return redirect()->route('tenant.users.complete')
            ->with('success_info', "従業員 [ {$userName} ] 様のアカウントを完全に削除いたしました。");
    }

    /**
     * 登録・変更・削除完了画面
     */
    public function complete(): View
    {
        $company = $this->authorizeAdmin();

        return view('settings.users.complete', [
            'activeMenu'     => 'settings',
            'currentCompany' => $company,
            'successInfo'    => session('success_info'),
        ]);
    }

    /**
     * メール送信処理
     */
    private function sendNotificationEmail(string $toEmail, string $subject, string $body): bool
    {
        try {
            Mail::raw($body, function ($message) use ($toEmail, $subject) {
                $message->to($toEmail)->subject($subject);
            });
            return true;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('従業員通知メール送信エラー: ' . $e->getMessage());
            return false;
        }
    }
}
