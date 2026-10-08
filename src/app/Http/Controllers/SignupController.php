<?php

namespace App\Http\Controllers;

use App\Mail\AdminSignupNotificationMail;
use App\Mail\WelcomeSignupMail;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Prefectures;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SignupController extends Controller
{
    /**
     * 新規登録画面（入力フォーム）の表示
     */
    public function index(Request $request): View
    {
        $invitationCode = $request->query('invitation_code', session('signup_data.invitation_code'));
        $inviterCompanyName = null;
        $input = session('signup_data', []);

        if ($invitationCode) {
            $invitation = Invitation::where('invitation_code', $invitationCode)->first();
            if ($invitation) {
                $inviterCompany = Company::find($invitation->company_id);
                if ($inviterCompany) {
                    $inviterCompanyName = $inviterCompany->name;
                }

                if (empty($input)) {
                    $input = [
                        'companyName'     => $invitation->company_name,
                        'corporateNumber' => $invitation->corporate_number ?? '',
                        'adminName'       => $invitation->user_name ?? '',
                        'adminEmail'      => $invitation->email,
                    ];
                }
            }
        }

        return view('signup.registration', [
            'input'              => $input,
            'invitationCode'     => $invitationCode,
            'inviterCompanyName' => $inviterCompanyName,
        ]);
    }

    /**
     * 入力内容の確認（Confirm）画面の表示
     */
    public function confirm(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'companyName'      => ['required', 'string', 'max:255'],
            'companyNameShort' => ['required', 'string', 'max:100'],
            'companyNameKana'  => ['required', 'string', 'max:100', 'regex:/^[ァ-ヶー\s　]+$/u'],
            'corporateNumber'  => ['nullable', 'string', 'regex:/^[0-9]{13}$/'],
            'phone'            => ['required', 'string', 'max:50'],
            'zipCode'          => ['required', 'string', 'max:20'],
            'prefecture'       => ['required', 'string'],
            'city'             => ['required', 'string', 'max:100'],
            'address'          => ['required', 'string', 'max:255'],
            'adminName'        => ['required', 'string', 'max:100'],
            'adminNameKana'    => ['required', 'string', 'max:100', 'regex:/^[ァ-ヶー\s　]+$/u'],
            'adminEmail'       => ['required', 'string', 'email', 'max:255'],
            'department'       => ['nullable', 'string', 'max:100'],
            'position'         => ['nullable', 'string', 'max:100'],
            'agreeTerms'       => ['required'],
            'invitation_code'  => ['nullable', 'string'],
        ], [
            'companyNameKana.regex' => '企業名カナは全角カタカナで入力してください。',
            'adminNameKana.regex'   => '管理者氏名フリガナは全角カタカナで入力してください。',
            'corporateNumber.regex' => '法人番号は13桁の半角数字で入力してください。',
            'adminEmail.email'      => '有効なメールアドレスを入力してください。',
            'agreeTerms.required'   => '登録を進めるには利用規約への同意が必要です。',
        ]);

        $prefCode = Prefectures::getCodeByName($request->prefecture);
        if ($prefCode === null) {
            return back()->withInput()->withErrors(['prefecture' => '不正な都道府県が選択されました。']);
        }

        $invitationCode = $request->invitation_code;
        $inviterCompanyName = null;
        if ($invitationCode) {
            $invitation = Invitation::where('invitation_code', $invitationCode)->first();
            if ($invitation) {
                $inviterCompany = Company::find($invitation->company_id);
                if ($inviterCompany) {
                    $inviterCompanyName = $inviterCompany->name;
                }
            }
        }

        session(['signup_data' => $validated]);

        return view('signup.confirm', [
            'data'               => $validated,
            'invitationCode'     => $invitationCode,
            'inviterCompanyName' => $inviterCompanyName,
        ]);
    }

    /**
     * DB保存・メール送信・完了（Complete）画面の表示
     */
    public function register(Request $request): View|RedirectResponse
    {
        $signupData = session('signup_data');
        if (!$signupData) {
            return redirect()->route('signup')->withErrors(['system' => 'セッションの有効期限が切れました。最初からやり直してください。']);
        }

        // 1. url_code の自動生成（重複回避）
        do {
            $urlCode = md5(uniqid('', true));
            $exists = Company::where('url_code', $urlCode)->exists();
        } while ($exists);

        // 2. 初期パスワードの自動生成（英数字10文字）
        $rawPassword = Str::random(10);

        // 3. 都道府県コード
        $prefectureCode = Prefectures::getCodeByName($signupData['prefecture']);

        // 招待情報の確認
        $invitationCode = $signupData['invitation_code'] ?? null;
        $invitation = $invitationCode ? Invitation::where('invitation_code', $invitationCode)->first() : null;

        try {
            DB::transaction(function () use ($signupData, $urlCode, $rawPassword, $prefectureCode, $invitation) {
                // 企業登録（初期状態は審査待ち suspended）
                $company = Company::create([
                    'parent_id'             => null,
                    'invitation_company_id' => $invitation ? $invitation->company_id : null,
                    'url_code'              => $urlCode,
                    'corporate_number'      => $signupData['corporateNumber'] ?? null,
                    'name'                  => $signupData['companyName'],
                    'abbreviated_name'      => $signupData['companyNameShort'],
                    'abbreviated_kana'      => $signupData['companyNameKana'],
                    'zip_code'              => $signupData['zipCode'],
                    'prefecture_code'       => $prefectureCode,
                    'city'                  => $signupData['city'],
                    'address'               => $signupData['address'],
                    'tel'                   => $signupData['phone'],
                    'status'                => 'suspended',
                ]);

                // 初期管理者登録
                User::create([
                    'company_id'      => $company->id,
                    'email'           => $signupData['adminEmail'],
                    'password_hash'   => Hash::make($rawPassword),
                    'user_name'       => $signupData['adminName'],
                    'user_name_kana'  => $signupData['adminNameKana'],
                    'department'      => $signupData['department'] ?? null,
                    'position'        => $signupData['position'] ?? null,
                    'role'            => 'admin',
                    'status'          => 'active',
                    'is_all_assigned' => 0,
                ]);

                // 招待経由の場合、自動的に company_links へ登録 ＆ invitations から削除
                if ($invitation) {
                    $inviterCompanyId    = $invitation->company_id;
                    $registeredCompanyId = $company->id;

                    if ($invitation->buysell === 'seller') {
                        $buyerCompanyId  = $registeredCompanyId;
                        $sellerCompanyId = $inviterCompanyId;
                    } else {
                        $buyerCompanyId  = $inviterCompanyId;
                        $sellerCompanyId = $registeredCompanyId;
                    }

                    CompanyLink::firstOrCreate(
                        ['buyer_company_id' => $buyerCompanyId, 'seller_company_id' => $sellerCompanyId],
                        ['status' => 'pending']
                    );

                    $invitation->delete();
                }
            });

            // 4. メール送信
            try {
                // 登録者宛て受付メール
                Mail::to($signupData['adminEmail'])->send(new WelcomeSignupMail(
                    companyName: $signupData['companyName'],
                    userName:    $signupData['adminName'],
                    urlCode:     $urlCode,
                    rawPassword: $rawPassword,
                    email:       $signupData['adminEmail']
                ));

                // 事務局宛て通知メール（Main宛て、SubをCC指定）
                $officeMain = config('mail.office.main', 'noreply@meshu.org');
                $officeSub  = config('mail.office.sub');

                $adminMail = Mail::to($officeMain);
                if ($officeSub && $officeSub !== $officeMain) {
                    $adminMail->cc($officeSub);
                }
                $adminMail->send(new AdminSignupNotificationMail(
                    signupData: $signupData,
                    urlCode:    $urlCode
                ));
            } catch (\Exception $mailEx) {
                // メール送信エラー時もログに記録し登録自体は成功とする
                report($mailEx);
            }

            // セッション一時データのクリア
            session()->forget('signup_data');

            return view('signup.complete', [
                'email'    => $signupData['adminEmail'],
                'url_code' => $urlCode,
            ]);

        } catch (\Exception $e) {
            return redirect()->route('signup', array_filter(['invitation_code' => $invitationCode]))
                ->withInput()
                ->withErrors(['system' => 'アカウント登録処理中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }
}
