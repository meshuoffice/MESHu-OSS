<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * 事務局ログイン画面の表示
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login', [
            'adminPath' => env('ADMIN_URL_PATH', 'admin'),
        ]);
    }

    /**
     * 事務局ログイン処理
     */
    public function login(Request $request): RedirectResponse
    {
        $email    = $request->input('email');
        $password = $request->input('password');

        if (empty($email) || empty($password)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'メールアドレスとパスワードを入力してください。');
        }

        $owner = Owner::where('email', $email)->first();

        if (!$owner || !Hash::check($password, $owner->password_hash)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'メールアドレスまたはパスワードが正しくありません。');
        }

        if ($owner->status !== 'active') {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'このアカウントは停止中または無効化されています。');
        }

        // ログイン処理
        Auth::guard('admin')->login($owner);
        $request->session()->regenerate();

        // 最終ログイン日時を記録
        $owner->last_login_at = now();
        $owner->save();

        session([
            'is_admin_logged_in' => true,
            'owner_id'           => $owner->id,
            'owner_name'         => $owner->name,
            'owner_role'         => $owner->role,
            'admin_owner_id'     => $owner->id,
            'admin_owner_name'   => $owner->name,
            'admin_owner_email'  => $owner->email,
            'admin_owner_role'   => $owner->role,
        ]);

        return redirect()->intended(route('admin.dashboard'))->with('success', 'ログインしました。');
    }

    /**
     * 事務局ログアウト処理
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('success', 'ログアウトしました。');
    }

    /**
     * 企業代理ログイン機能（Impersonation）
     */
    public function impersonate(int $company_id): RedirectResponse
    {
        $company = Company::findOrFail($company_id);

        // 企業の管理者アカウントを検索（存在しない場合は最上位アクティブユーザー）
        $user = User::where('company_id', $company->id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->first() ?? User::where('company_id', $company->id)
            ->where('status', 'active')
            ->first();

        if (!$user) {
            return back()->with('error', '該当企業に有効なログイン可能ユーザーが存在しません。');
        }

        // 企業ユーザーとしてログイン
        Auth::guard('web')->login($user);

        // 代理ログインフラグと監査用情報をセッションに保持
        session([
            'is_impersonating'      => true,
            'impersonator_owner_id' => Auth::guard('admin')->id(),
            'user_id'               => $user->id,
            'user_name'             => $user->user_name,
            'user_email'            => $user->email,
            'user_role'             => $user->role,
            'role'                  => $user->role,
            'company_id'            => $company->id,
            'company_name'          => $company->name,
            'company_url_code'      => $company->url_code,
            'is_user_logged_in'     => true,
        ]);

        return redirect()->route('dashboard')
            ->with('status', "【事務局代理ログイン中】 {$company->name}（{$user->user_name} 様）として操作しています。");
    }
}
