<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\AdminCompanyWithdrawNotificationMail;
use App\Mail\CompanyWithdrawMail;
use App\Models\Company;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CompanyWithdrawController extends Controller
{
    /**
     * 退会確認・同意フォーム画面
     */
    public function showForm(Request $request, string $url_code)
    {
        $user = Auth::guard('web')->user();

        // 管理者のみアクセス可能
        if (!$user || $user->role !== 'admin') {
            abort(403, '退会手続きは企業の管理者（admin）のみ実行可能です。');
        }

        $company = $user->company;

        // 企業URLコードの整合性チェック
        if ($company->url_code !== $url_code) {
            abort(404);
        }

        // 進行中の取引件数を確認（完了・辞退以外の取引）
        $activeTransactionsCount = Transaction::whereHas('companyLink', function ($q) use ($company) {
            $q->where('buyer_company_id', $company->id)
              ->orWhere('seller_company_id', $company->id);
        })
        ->whereNotIn('status', ['order_accepted', 'estimate_declined', 'order_declined'])
        ->count();

        return view('settings.company.withdraw', [
            'activeMenu' => 'settings',
            'company' => $company,
            'user' => $user,
            'activeTransactionsCount' => $activeTransactionsCount,
        ]);
    }

    /**
     * 退会実行処理
     */
    public function withdraw(Request $request, string $url_code)
    {
        $user = Auth::guard('web')->user();

        // 管理者のみ実行可能
        if (!$user || $user->role !== 'admin') {
            abort(403, '退会手続きは企業の管理者（admin）のみ実行可能です。');
        }

        $company = $user->company;

        if ($company->url_code !== $url_code) {
            abort(404);
        }

        // 5つの同意項目およびパスワードのバリデーション
        $request->validate([
            'agreement_partner'      => 'required|accepted',
            'agreement_transactions' => 'required|accepted',
            'agreement_backup'       => 'required|accepted',
            'agreement_users'        => 'required|accepted',
            'agreement_period'       => 'required|accepted',
            'password'               => 'required|string',
        ], [
            'agreement_partner.accepted'      => '取引先への影響に関する事項の確認・同意が必要です。',
            'agreement_transactions.accepted' => '進行中の取引に関する事項の確認・同意が必要です。',
            'agreement_backup.accepted'       => '帳票・取引データの保存義務に関する事項の確認・同意が必要です。',
            'agreement_users.accepted'        => '全従業員アカウントの停止に関する事項の確認・同意が必要です。',
            'agreement_period.accepted'       => '30日間のデータ保持期間および完全削除に関する事項の確認・同意が必要です。',
            'password.required'               => 'ご本人確認のため、現在のログインパスワードを入力してください。',
        ]);

        // パスワード照合
        if (!Hash::check($request->password, $user->password_hash)) {
            return back()
                ->withInput($request->except('password'))
                ->withErrors(['password' => '入力されたパスワードが正しくありません。']);
        }

        $adminEmail = $user->email;
        $companyName = $company->name;

        DB::beginTransaction();
        try {
            // 企業ステータスを「一時停止 (suspended)」に更新
            $company->update(['status' => 'suspended']);

            // 所属する稼働中ユーザーも「一時停止 (suspended)」に同期
            $company->users()->where('status', 'active')->update(['status' => 'suspended']);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', '退会処理中にシステムエラーが発生しました: ' . $e->getMessage());
        }

        // 1. 申請者（管理者）への退会受付完了メール（Reply-To: 事務局返信用アドレス）
        try {
            Mail::to($adminEmail)->send(new CompanyWithdrawMail($company, $user));
        } catch (\Exception $e) {
            \Log::error('Company withdraw notification email failed: ' . $e->getMessage(), [
                'company_id' => $company->id,
                'email' => $adminEmail,
            ]);
        }

        // 2. 事務局への退会申請通知メール（Main宛て、SubをBCC指定）
        try {
            $activeTransactionsCount = Transaction::whereHas('companyLink', function ($q) use ($company) {
                $q->where('buyer_company_id', $company->id)
                  ->orWhere('seller_company_id', $company->id);
            })
            ->whereNotIn('status', ['order_accepted', 'estimate_declined', 'order_declined'])
            ->count();

            $officeMain = config('mail.office.main', 'noreply@meshu.org');
            $officeSub  = config('mail.office.sub');

            $officeMail = Mail::to($officeMain);
            if ($officeSub && $officeSub !== $officeMain) {
                $officeMail->bcc($officeSub);
            }
            $officeMail->send(new AdminCompanyWithdrawNotificationMail(
                company: $company,
                user: $user,
                activeTransactionsCount: $activeTransactionsCount
            ));
        } catch (\Exception $e) {
            \Log::error('Admin company withdraw notification email failed: ' . $e->getMessage(), [
                'company_id' => $company->id,
            ]);
        }

        // ログアウト & セッション破棄
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 退会完了画面へリダイレクト（フラッシュセッションで企業名を一時表示）
        return redirect()->route('withdraw.complete')->with([
            'withdrawn_company_name' => $companyName,
            'withdrawn_admin_email' => $adminEmail,
        ]);
    }

    /**
     * 退会受付完了画面（公開ページ）
     */
    public function complete(Request $request)
    {
        return view('settings.company.withdraw_complete', [
            'companyName' => session('withdrawn_company_name'),
            'adminEmail'  => session('withdrawn_admin_email'),
            'replyEmail'  => config('mail.office.reply', env('MAIL_OFFICE_REPLY', 'officem@meshu.org')),
        ]);
    }
}
