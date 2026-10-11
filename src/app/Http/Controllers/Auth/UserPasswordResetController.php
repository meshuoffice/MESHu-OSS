<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserPasswordResetController extends Controller
{
    /**
     * パスワード再設定申請画面の表示
     */
    public function showForgotForm(string $url_code): View
    {
        return view('auth.forgot_password', [
            'url_code' => $url_code,
        ]);
    }

    /**
     * パスワード再設定メール送信処理（ユーザー列挙攻撃防止）
     */
    public function sendResetLink(Request $request, string $url_code): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email:filter'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email'    => '有効なメールアドレス形式で入力してください。',
        ]);

        $company = $request->attributes->get('current_company');

        // 同一企業内のアクティブユーザーを検索
        $user = User::where('company_id', $company->id)
            ->where('email', $request->email)
            ->where('status', 'active')
            ->first();

        if ($user) {
            // 既存の未消化トークンを削除
            DB::table('password_reset_tokens')->where('user_id', $user->id)->delete();

            // 生トークン（64文字）を生成し、DBにはSHA-256ハッシュを保存
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);

            DB::table('password_reset_tokens')->insert([
                'user_id'    => $user->id,
                'token_hash' => $tokenHash,
                'expires_at' => now()->addMinutes(30), // 有効期限30分
                'created_at' => now(),
            ]);

            // 案内メール送信
            Mail::to($user->email)->send(new PasswordResetMail($user, $company, $rawToken));
        }

        // セキュリティ対策: アカウントの存在有無に関わらず同じ完了画面へ遷移
        return redirect()->route('tenant.password.sent', ['url_code' => $url_code]);
    }

    /**
     * 送信完了画面の表示
     */
    public function showSentView(string $url_code): View
    {
        return view('auth.forgot_password_sent', [
            'url_code' => $url_code,
        ]);
    }

    /**
     * 新パスワード設定フォームの表示
     */
    public function showResetForm(Request $request, string $url_code): View|RedirectResponse
    {
        $rawToken = $request->query('token');

        if (!$rawToken) {
            return redirect()->route('tenant.login', ['url_code' => $url_code])
                ->withErrors(['email' => '無効な再設定リンクです。']);
        }

        $tokenHash = hash('sha256', $rawToken);

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return redirect()->route('tenant.password.forgot', ['url_code' => $url_code])
                ->withErrors(['email' => 'パスワード再設定リンクの有効期限が切れているか、無効です。再度申請してください。']);
        }

        return view('auth.reset_password', [
            'url_code' => $url_code,
            'token'    => $rawToken,
        ]);
    }

    /**
     * 新パスワード更新処理
     */
    public function resetPassword(Request $request, string $url_code): RedirectResponse
    {
        $request->validate([
            'token'    => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required'  => '新しいパスワードを入力してください。',
            'password.min'       => 'パスワードは8文字以上で設定してください。',
            'password.confirmed' => '確認用パスワードと一致しません。',
        ]);

        $tokenHash = hash('sha256', $request->token);

        $tokenRecord = DB::table('password_reset_tokens')
            ->where('token_hash', $tokenHash)
            ->where('expires_at', '>', now())
            ->first();

        if (!$tokenRecord) {
            return redirect()->route('tenant.password.forgot', ['url_code' => $url_code])
                ->withErrors(['email' => 'パスワード再設定リンクの有効期限が切れているか、無効です。再度申請してください。']);
        }

        $company = $request->attributes->get('current_company');
        $user = User::where('id', $tokenRecord->user_id)
            ->where('company_id', $company->id)
            ->first();

        if (!$user) {
            return redirect()->route('tenant.login', ['url_code' => $url_code])
                ->withErrors(['email' => '該当ユーザーが見つかりません。']);
        }

        // パスワード更新
        $user->password_hash = Hash::make($request->password);
        $user->save();

        // ワンタイム消費: トークンを即時削除
        DB::table('password_reset_tokens')->where('id', $tokenRecord->id)->delete();

        return redirect()->route('tenant.login', ['url_code' => $url_code])
            ->with('status', 'パスワードの再設定が完了しました。新しいパスワードでログインしてください。');
    }
}
