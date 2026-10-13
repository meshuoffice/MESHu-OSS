<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Mail\CompanyInvitationMail;
use App\Models\Company;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvitationController extends Controller
{
    /**
     * 管理者権限のチェックと所属企業の取得
     */
    protected function authorizeAdmin(): Company
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            abort(401, 'ログインしてください。');
        }

        if ($user->role !== 'admin') {
            abort(403, 'この機能を利用するには管理者権限が必要です。');
        }

        $company = $user->company;
        if (!$company) {
            abort(403, '所属企業情報が見つかりません。');
        }

        return $company;
    }

    /**
     * 1. 招待一覧画面
     */
    public function index(Request $request): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $keyword = $request->input('keyword');
        $buysell = $request->input('buysell');

        $query = Invitation::where('company_id', $company->id)
            ->with('user')
            ->orderBy('id', 'desc');

        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('company_name', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%")
                  ->orWhere('corporate_number', 'like', "%{$keyword}%");
            });
        }

        if (!empty($buysell) && in_array($buysell, ['buyer', 'seller'], true)) {
            $query->where('buysell', $buysell);
        }

        $invitations = $query->paginate(20)->withQueryString();

        return view('tenant.invitation.index', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'invitations'    => $invitations,
            'keyword'        => $keyword,
            'buysell'        => $buysell,
            'total'          => $invitations->total(),
            'perPage'        => $invitations->perPage(),
            'page'           => $invitations->currentPage(),
            'totalPages'     => $invitations->lastPage(),
        ]);
    }

    /**
     * 2. 新規招待メール作成画面
     */
    public function new(): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $defaultSubject = "【MESHu】{$company->name}より取引連携のご招待";
        $defaultDetail  = "いつも大変お世話になっております。{$company->name}でございます。\n\n"
                        . "このたび、弊社との受発注・取引連携を円滑に行うため、B2Bプラットフォーム「MESHu」へご招待申し上げます。\n"
                        . "以下の専用登録URLより、アカウントの初期登録手続きをお願い申し上げます。\n\n"
                        . "ご不明な点がございましたら、弊社担当までお気軽にお問い合わせください。";

        return view('tenant.invitation.new', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'defaultSubject' => $defaultSubject,
            'defaultDetail'  => $defaultDetail,
        ]);
    }

    /**
     * 3. 招待内容確認画面
     */
    public function confirm(Request $request)
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'company_name'     => 'required|string|max:255',
            'corporate_number' => ['nullable', 'string', 'regex:/^[0-9]{13}$/'],
            'user_name'        => 'required|string|max:100',
            'email'            => 'required|email|max:255',
            'buysell'          => 'required|in:buyer,seller',
            'mail_subject'     => 'required|string|max:255',
            'mail_detail'      => 'required|string',
        ], [
            'company_name.required'     => '招待先 企業名は必須です。',
            'corporate_number.regex'   => '法人番号は半角数字13桁で入力してください。',
            'user_name.required'        => '招待先 管理者名は必須です。',
            'email.required'            => '送信先メールアドレスは必須です。',
            'email.email'               => '有効なメールアドレス形式で入力してください。',
            'buysell.required'          => '登録時の連携属性は必須です。',
            'mail_subject.required'     => '招待メール件名は必須です。',
            'mail_detail.required'      => '招待メッセージ本文は必須です。',
        ]);

        // 法人番号が入力されている場合の存在チェック
        if (!empty($validated['corporate_number'])) {
            $existingCompany = Company::where('corporate_number', $validated['corporate_number'])->first();
            if ($existingCompany) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'corporate_number' => '指定された法人番号の企業（' . e($existingCompany->name) . '）は既にMESHuに登録されています。未登録企業への招待ではなく、取引先検索より接続申請を行ってください。',
                    ]);
            }
        }

        return view('tenant.invitation.confirm', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'postData'       => $validated,
        ]);
    }

    /**
     * 4. 招待メール送信＆DB保存処理
     */
    public function send(Request $request): RedirectResponse
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $validated = $request->validate([
            'company_name'     => 'required|string|max:255',
            'corporate_number' => ['nullable', 'string', 'regex:/^[0-9]{13}$/'],
            'user_name'        => 'required|string|max:100',
            'email'            => 'required|email|max:255',
            'buysell'          => 'required|in:buyer,seller',
            'mail_subject'     => 'required|string|max:255',
            'mail_detail'      => 'required|string',
        ]);

        if (!empty($validated['corporate_number'])) {
            $existingCompany = Company::where('corporate_number', $validated['corporate_number'])->first();
            if ($existingCompany) {
                return redirect()->route('tenant.invitation.new')
                    ->withInput()
                    ->withErrors([
                        'corporate_number' => '指定された法人番号の企業は既にMESHuに登録されています。',
                    ]);
            }
        }

        // 一意の招待コード生成
        do {
            $code = md5(uniqid('', true));
            $exists = Invitation::where('invitation_code', $code)->exists();
        } while ($exists);

        $invitation = Invitation::create([
            'company_id'       => $company->id,
            'user_id'          => $user->id,
            'invitation_code'  => $code,
            'buysell'          => $validated['buysell'],
            'email'            => $validated['email'],
            'mail_subject'     => $validated['mail_subject'],
            'mail_detail'      => $validated['mail_detail'],
            'company_name'     => $validated['company_name'],
            'corporate_number' => $validated['corporate_number'] ?: null,
            'user_name'        => $validated['user_name'],
        ]);

        // メール送信
        try {
            Mail::to($invitation->email)->send(
                new CompanyInvitationMail($company, $invitation)
            );
        } catch (\Throwable $e) {
            Log::error('招待メール送信エラー: ' . $e->getMessage());
        }

        // 完了画面用のセッションフラッシュ
        session()->flash('sentInvitation', [
            'company_name' => $invitation->company_name,
            'email'        => $invitation->email,
            'buysell'      => $invitation->buysell,
        ]);

        return redirect()->route('tenant.invitation.complete');
    }

    /**
     * 5. 送信完了画面
     */
    public function complete(): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $sentInfo = session('sentInvitation');

        return view('tenant.invitation.complete', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'sentInfo'       => $sentInfo,
        ]);
    }

    /**
     * 6. 招待詳細閲覧画面
     */
    public function show(int $id): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $invitation = Invitation::where('company_id', $company->id)
            ->with('user')
            ->find($id);

        if (!$invitation) {
            abort(404, '指定された招待情報が見つかりません。');
        }

        $senderName = $invitation->user ? $invitation->user->user_name : '不明';

        return view('tenant.invitation.show', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'invitation'     => $invitation,
            'senderName'     => $senderName,
        ]);
    }

    /**
     * 7. 招待削除確認画面
     */
    public function deleteConfirm(int $id): View
    {
        $company = $this->authorizeAdmin();
        $user = Auth::guard('web')->user();

        $invitation = Invitation::where('company_id', $company->id)
            ->find($id);

        if (!$invitation) {
            abort(404, '指定された招待情報が見つかりません。');
        }

        return view('tenant.invitation.delete_confirm', [
            'currentCompany' => $company,
            'companyName'    => $company->name,
            'user'           => $user,
            'userName'       => $user->user_name,
            'activeMenu'     => 'settings',
            'invitation'     => $invitation,
        ]);
    }

    /**
     * 8. 招待物理削除実行
     */
    public function destroy(int $id): RedirectResponse
    {
        $company = $this->authorizeAdmin();

        $invitation = Invitation::where('company_id', $company->id)
            ->find($id);

        if (!$invitation) {
            return redirect()->route('tenant.invitation.index')
                ->with('error', '指定された招待情報が見つかりません。');
        }

        $companyName = $invitation->company_name;
        $invitation->delete();

        return redirect()->route('tenant.invitation.index')
            ->with('success', "「{$companyName}」宛の招待レコードを完全に削除いたしました。");
    }
}
