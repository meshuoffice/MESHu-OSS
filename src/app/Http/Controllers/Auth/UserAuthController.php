<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserAuthController extends Controller
{
    /**
     * 企業別ログイン画面の表示 (/{url_code} または /{url_code}/login)
     */
    public function showLoginForm(string $url_code): View|RedirectResponse
    {
        // 既にログイン済みの場合はダッシュボードへ
        if (Auth::guard('web')->check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login', [
            'url_code' => $url_code,
        ]);
    }

    /**
     * 企業別ログイン処理
     */
    public function login(Request $request, string $url_code): RedirectResponse
    {
        $request->validate([
            'email'    => ['required', 'string', 'email:filter'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'メールアドレスを入力してください。',
            'email.email'       => '有効なメールアドレス形式で入力してください。',
            'password.required' => 'パスワードを入力してください。',
        ]);

        $company = $request->attributes->get('current_company');

        // 同一企業内のアクティブユーザーを検索
        $user = User::where('company_id', $company->id)
            ->where('email', $request->email)
            ->first();

        // 認証チェック
        if (!$user || !Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'メールアドレスまたはパスワードが正しくありません。']);
        }

        // アカウント状態チェック
        if ($user->status !== 'active') {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'このアカウントは現在利用停止中または削除されています。']);
        }

        // ログインセッション確立
        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // 最終ログイン日時を記録
        $user->update(['last_login_at' => now()]);

        // セッション補助情報を保持 (CI4互換)
        session([
            'user_id'          => $user->id,
            'user_name'        => $user->user_name,
            'user_email'       => $user->email,
            'user_role'        => $user->role,
            'role'             => $user->role,
            'company_id'       => $company->id,
            'company_name'     => $company->name,
            'company_url_code' => $company->url_code,
            'is_user_logged_in'=> true,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();
        $companyUrlCode = session('company_url_code') ?: session('current_url_code') ?: $user?->company?->url_code;
        $isImpersonatedByAdmin = session('impersonated_by_admin', false);
        $isAdminLoggedIn     = Auth::guard('admin')->check();

        // 企業ユーザーセッションのみログアウト
        Auth::guard('web')->logout();

        // 事務局管理者がログイン中の場合（代理ログイン中含む）は、事務局セッション（adminガード）を維持し、企業側のセッション情報のみ破棄
        if ($isAdminLoggedIn || $isImpersonatedByAdmin) {
            $request->session()->forget([
                'impersonated_by_admin',
                'is_impersonating',
                'user_id',
                'user_name',
                'user_email',
                'user_role',
                'role',
                'company_id',
                'company_name',
                'company_url_code',
                'is_user_logged_in',
            ]);
            $request->session()->regenerateToken();
        } else {
            // 通常の一般ユーザーログアウト時はセッション全体を無効化
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($companyUrlCode) {
            return redirect('/' . $companyUrlCode . '/login')->with('status', 'ログアウトしました。');
        }

        return redirect('/')->with('status', 'ログアウトしました。');
    }
}
