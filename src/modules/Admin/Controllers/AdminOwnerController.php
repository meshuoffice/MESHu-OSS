<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminOwnerController extends Controller
{
    /**
     * アクセス権限チェック（最高管理者以外は自分自身のアカウント情報のみアクセス可）
     */
    protected function checkOwnerAccess(?int $targetId = null): ?RedirectResponse
    {
        $currentOwner = Auth::guard('admin')->user();
        if (!$currentOwner) {
            return redirect()->route('admin.login');
        }

        if ($currentOwner->role === 'admin') {
            return null;
        }

        if ($targetId === null || (int)$targetId !== (int)$currentOwner->id) {
            return redirect()->route('admin.dashboard')
                ->with('error', '他の事務局アカウント情報を閲覧・操作する権限がありません。');
        }

        return null;
    }

    /**
     * 事務局アカウント一覧（最高管理者のみ）
     */
    public function index(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkOwnerAccess(null)) {
            return $redirect;
        }

        $keyword = trim((string)$request->input('keyword', ''));
        $role    = $request->input('role');
        $status  = $request->input('status');

        $query = Owner::query()->where('status', '!=', 'deleted');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if (!empty($role)) {
            $query->where('role', $role);
        }

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $query->orderBy('id', 'asc');

        $perPage = 10;
        $owners = $query->paginate($perPage)->withQueryString();

        return view('admin::owner.index', [
            'activeMenu' => 'owner',
            'title'      => '事務局アカウント管理 | MESHu 事務局管理',
            'owners'     => $owners,
            'keyword'    => $keyword,
            'role'       => $role,
            'status'     => $status,
        ]);
    }

    /**
     * 新規事務局アカウント登録フォーム（最高管理者のみ）
     */
    public function registration(): View|RedirectResponse
    {
        if ($redirect = $this->checkOwnerAccess(null)) {
            return $redirect;
        }

        return view('admin::owner.registration', [
            'activeMenu' => 'owner',
            'title'      => '新規事務局アカウント作成 | MESHu 事務局管理',
            'formData'   => session()->getOldInput(),
        ]);
    }

    /**
     * アカウント登録・編集の確認画面
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        $postData = $request->all();
        $isEdit   = !empty($postData['id']);
        $ownerId  = $isEdit ? (int)$postData['id'] : null;

        if ($redirect = $this->checkOwnerAccess($ownerId)) {
            return $redirect;
        }

        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $isEdit ? Rule::unique('owners', 'email')->ignore($ownerId) : Rule::unique('owners', 'email'),
            ],
            'role' => ['required', Rule::in(['admin', 'reviewer', 'viewer'])],
        ];

        $messages = [
            'name.required'     => '管理者氏名は必須項目です。',
            'name.min'          => '管理者氏名は2文字以上で入力してください。',
            'email.required'    => 'メールアドレス (ログインID) は必須項目です。',
            'email.email'       => '有効なメールアドレス形式で入力してください。',
            'email.unique'      => '入力されたメールアドレスは既に使用されています。',
            'role.required'     => '権限ロールは必須項目です。',
            'role.in'           => '権限ロールを正しく選択してください。',
            'password.required' => 'パスワードは必須項目です。',
            'password.min'      => 'パスワードは8文字以上で入力してください。',
        ];

        if ($isEdit) {
            if ($request->filled('password')) {
                $rules['password'] = ['min:8'];
            }
        } else {
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validator = Validator::make($postData, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        return view('admin::owner.confirm', [
            'activeMenu' => 'owner',
            'title'      => ($isEdit ? 'アカウント情報更新確認' : 'アカウント登録確認') . ' | MESHu 事務局管理',
            'postData'   => $postData,
            'isEdit'     => $isEdit,
            'ownerId'    => $ownerId,
        ]);
    }

    /**
     * 新規登録完了処理（最高管理者のみ）
     */
    public function complete(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->checkOwnerAccess(null)) {
            return $redirect;
        }

        $postData = $request->all();

        $rules = [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', Rule::unique('owners', 'email')],
            'role'     => ['required', Rule::in(['admin', 'reviewer', 'viewer'])],
            'password' => ['required', 'string', 'min:8'],
        ];

        $validator = Validator::make($postData, $rules);
        if ($validator->fails()) {
            return redirect()->route('admin.owner.registration')
                ->withErrors($validator)
                ->withInput()
                ->with('error', '事務局アカウントの登録に失敗しました。');
        }

        $currentAdminRole = Auth::guard('admin')->user()->role ?? 'admin';
        $targetRole = ($currentAdminRole === 'admin') ? ($postData['role'] ?? 'reviewer') : 'reviewer';

        $owner = Owner::create([
            'name'          => $postData['name'],
            'email'         => $postData['email'],
            'password_hash' => Hash::make($postData['password']),
            'role'          => $targetRole,
            'status'        => $postData['status'] ?? 'active',
        ]);

        return view('admin::owner.complete', [
            'activeMenu' => 'owner',
            'title'      => '登録完了 | MESHu 事務局管理',
            'ownerName'  => $owner->name,
            'isEdit'     => false,
        ]);
    }

    /**
     * アカウント詳細（自分自身または最高管理者）
     */
    public function show(int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        if ($redirect = $this->checkOwnerAccess($id)) {
            return $redirect;
        }

        $owner = Owner::where('status', '!=', 'deleted')->find($id);

        if (!$owner) {
            return redirect()->route('admin.dashboard')
                ->with('error', '指定されたアカウントが見つかりません。');
        }

        return view('admin::owner.show', [
            'activeMenu' => 'owner',
            'title'      => $owner->name . ' 詳細 | MESHu 事務局管理',
            'owner'      => $owner,
        ]);
    }

    /**
     * アカウント編集フォーム（自分自身または最高管理者）
     */
    public function edit(int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        if ($redirect = $this->checkOwnerAccess($id)) {
            return $redirect;
        }

        $owner = Owner::where('status', '!=', 'deleted')->find($id);

        if (!$owner) {
            return redirect()->route('admin.dashboard')
                ->with('error', '指定されたアカウントが見つかりません。');
        }

        return view('admin::owner.edit', [
            'activeMenu' => 'owner',
            'title'      => $owner->name . ' 編集 | MESHu 事務局管理',
            'owner'      => $owner,
        ]);
    }

    /**
     * アカウント更新実行（自分自身または最高管理者）
     */
    public function update(Request $request, int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        if ($redirect = $this->checkOwnerAccess($id)) {
            return $redirect;
        }

        $owner = Owner::where('status', '!=', 'deleted')->find($id);

        if (!$owner) {
            return redirect()->route('admin.dashboard')
                ->with('error', '指定されたアカウントが見つかりません。');
        }

        $postData = $request->all();

        $rules = [
            'name'  => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('owners', 'email')->ignore($id)],
            'role'  => ['required', Rule::in(['admin', 'reviewer', 'viewer'])],
        ];

        $messages = [
            'name.required'  => '管理者氏名は必須項目です。',
            'name.min'       => '管理者氏名は2文字以上で入力してください。',
            'email.required' => 'メールアドレス (ログインID) は必須項目です。',
            'email.email'    => '有効なメールアドレス形式で入力してください。',
            'email.unique'   => '入力されたメールアドレスは既に使用されています。',
            'role.required'  => '権限ロールは必須項目です。',
            'role.in'        => '権限ロールを正しく選択してください。',
            'password.min'   => 'パスワードは8文字以上で入力してください。',
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['min:8'];
        }

        $validator = Validator::make($postData, $rules, $messages);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $currentOwner = Auth::guard('admin')->user();
        $isSelf = ((int)$id === (int)$currentOwner->id);

        // 権限ロールの変更は最高管理者が「自分以外の他人のアカウント」を編集する場合のみ許可
        $targetRole = ($currentOwner->role === 'admin' && !$isSelf) ? $postData['role'] : $owner->role;
        $targetStatus = $isSelf ? $owner->status : ($postData['status'] ?? 'suspended');

        $owner->name   = $postData['name'];
        $owner->email  = $postData['email'];
        $owner->role   = $targetRole;
        $owner->status = $targetStatus;

        if ($request->filled('password')) {
            $owner->password_hash = Hash::make($postData['password']);
        }

        $owner->save();

        return view('admin::owner.complete', [
            'activeMenu' => 'owner',
            'title'      => '更新完了 | MESHu 事務局管理',
            'ownerName'  => $owner->name,
            'isEdit'     => true,
            'ownerId'    => $id,
        ]);
    }

    /**
     * アカウント削除・ステータス変更確認（最高管理者のみ）
     */
    public function deleteConfirm(int|string $id): View|RedirectResponse
    {
        $id = (int)$id;
        if ($redirect = $this->checkOwnerAccess(null)) {
            return $redirect;
        }

        $currentOwnerId = Auth::guard('admin')->id();
        if ($id === (int)$currentOwnerId) {
            return redirect()->route('admin.owner.index')
                ->with('error', '現在ログイン中の自分自身のアカウントを削除・停止することはできません。');
        }

        $owner = Owner::where('status', '!=', 'deleted')->find($id);

        if (!$owner) {
            return redirect()->route('admin.owner.index')
                ->with('error', '指定されたアカウントが見つかりません。');
        }

        return view('admin::owner.delete', [
            'activeMenu' => 'owner',
            'title'      => $owner->name . ' ステータス変更・削除確認 | MESHu 事務局管理',
            'owner'      => $owner,
        ]);
    }

    /**
     * アカウント削除・ステータス変更実行（最高管理者のみ）
     */
    public function delete(Request $request, int|string $id): RedirectResponse
    {
        $id = (int)$id;
        if ($redirect = $this->checkOwnerAccess(null)) {
            return $redirect;
        }

        $currentOwnerId = Auth::guard('admin')->id();
        if ($id === (int)$currentOwnerId) {
            return redirect()->route('admin.owner.index')
                ->with('error', '現在ログイン中の自分自身のアカウントを削除・停止することはできません。');
        }

        $owner = Owner::find($id);

        if (!$owner) {
            return redirect()->route('admin.owner.index')
                ->with('error', '指定されたアカウントが見つかりません。');
        }

        $newStatus = $request->input('status', 'deleted');

        // 稼働中アカウントの直接削除を防御
        if ($owner->status === 'active' && $newStatus === 'deleted') {
            return redirect()->back()
                ->with('error', '稼働中のアカウントは直接削除できません。一旦アカウントを「一時停止」にしてから削除を実行してください。');
        }

        // 最後の最高管理者の削除・無効化を防御
        if ($owner->role === 'admin' && $newStatus !== 'active') {
            $activeAdminCount = Owner::where('role', 'admin')->where('status', 'active')->count();
            if ($activeAdminCount <= 1) {
                return redirect()->back()
                    ->with('error', 'システムの運用を維持するため、最後の最高管理者アカウントを停止・削除することはできません。');
            }
        }

        $owner->status = $newStatus;
        $owner->save();

        $statusMsg = [
            'active'    => '事務局アカウントを有効化しました。',
            'suspended' => '事務局アカウントを停止状態に変更しました。',
            'deleted'   => '事務局アカウントを削除状態に変更しました。',
        ][$newStatus] ?? 'ステータスを変更しました。';

        return redirect()->route('admin.owner.index')->with('success', $statusMsg);
    }
}
