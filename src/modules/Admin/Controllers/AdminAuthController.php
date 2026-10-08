<?php

namespace Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * 事務局ログイン画面表示
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }

        $adminUrlPath = env('ADMIN_URL_PATH', 'admin');

        return view('admin::auth.login', [
            'adminUrlPath' => $adminUrlPath,
        ]);
    }

    /**
     * 事務局ログイン処理
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required'    => 'メールアドレスを入力してください。',
            'email.email'       => '有効なメールアドレス形式で入力してください。',
            'password.required' => 'パスワードを入力してください。',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            $owner = Auth::guard('admin')->user();

            if ($owner->status !== 'active') {
                Auth::guard('admin')->logout();
                return back()->withInput($request->only('email'))->withErrors([
                    'email' => 'このアカウントは現在利用停止中です。',
                ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withInput($request->only('email'))->withErrors([
            'email' => 'メールアドレスまたはパスワードが正しくありません。',
        ]);
    }

    /**
     * 事務局ログアウト処理
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        // 企業側のログインセッションがあるか確認
        if (!Auth::guard('web')->check()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->session()->regenerateToken();
        }

        return redirect()->route('admin.login');
    }
}
