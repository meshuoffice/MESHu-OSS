<?php

namespace Tests\Feature;

use App\Mail\AdminSignupNotificationMail;
use App\Mail\WelcomeSignupMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignupFlowTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * デフォルト（本番仕様）: 初期ステータスが suspended で登録され、承認待ちメッセージが表示されること
     */
    #[Test]
    public function 本番仕様では新規登録企業がsuspendedとなり承認待ち通知が行われる()
    {
        Mail::fake();
        Config::set('meshu.company_initial_status', 'suspended');

        $sessionData = [
            'companyName'      => 'テスト本番株式会社',
            'companyNameShort' => 'テスト本番',
            'companyNameKana'  => 'テストホンバン',
            'zipCode'          => '100-0001',
            'prefecture'       => '東京都',
            'city'             => '千代田区千代田',
            'address'          => '1-1',
            'phone'            => '03-1234-5678',
            'adminName'        => '本番 管理者',
            'adminNameKana'    => 'ホンバン カンリシャ',
            'adminEmail'       => 'prod-admin@test.com',
            'agreeTerms'       => '1',
        ];

        $response = $this->withSession(['signup_data' => $sessionData])
            ->post('/signup/register');

        $response->assertStatus(200);
        $response->assertSee('アカウント登録申請を受け付けました');
        $response->assertSee('現在アカウントは「停止中（承認待ち）」です');

        // 企業が suspended で作成されていること
        $company = Company::where('name', 'テスト本番株式会社')->first();
        $this->assertNotNull($company);
        $this->assertEquals('suspended', $company->status);

        // 管理者ユーザーは active で作成されていること
        $user = User::where('email', 'prod-admin@test.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('active', $user->status);

        // Welcomeメール（審査待ち）が送信されていること
        Mail::assertSent(WelcomeSignupMail::class, function (WelcomeSignupMail $mail) {
            return $mail->hasTo('prod-admin@test.com') &&
                   $mail->isImmediatelyActive === false &&
                   $mail->envelope()->subject === '【MESHu】アカウント登録申請の受付完了';
        });

        // 事務局通知メールが送信されていること
        Mail::assertSent(AdminSignupNotificationMail::class);
    }

    /**
     * OSS版・即時稼働仕様: COMPANY_INITIAL_STATUS=active 時は即時 active で作成され、直接ログイン可能となること
     */
    #[Test]
    public function OSS版仕様では新規登録企業が即時activeとなり専用ログイン導線が表示される()
    {
        Mail::fake();
        Config::set('meshu.company_initial_status', 'active');

        $sessionData = [
            'companyName'      => 'テストOSS株式会社',
            'companyNameShort' => 'テストOSS',
            'companyNameKana'  => 'テストオーエスエス',
            'zipCode'          => '100-0001',
            'prefecture'       => '東京都',
            'city'             => '千代田区千代田',
            'address'          => '1-1',
            'phone'            => '03-9876-5432',
            'adminName'        => 'OSS 管理者',
            'adminNameKana'    => 'オーエスエス カンリシャ',
            'adminEmail'       => 'oss-admin@test.com',
            'agreeTerms'       => '1',
        ];

        $response = $this->withSession(['signup_data' => $sessionData])
            ->post('/signup/register');

        $response->assertStatus(200);
        $response->assertSee('アカウント登録が完了しました');
        $response->assertSee('アカウントは<strong>即時有効化（活性化）</strong>されています', false);
        $response->assertSee('専用ログイン画面へ進む');

        // 企業が active で作成されていること
        $company = Company::where('name', 'テストOSS株式会社')->first();
        $this->assertNotNull($company);
        $this->assertEquals('active', $company->status);

        // Welcomeメール（即時有効化）が送信されていること
        Mail::assertSent(WelcomeSignupMail::class, function (WelcomeSignupMail $mail) {
            return $mail->hasTo('oss-admin@test.com') &&
                   $mail->isImmediatelyActive === true &&
                   $mail->envelope()->subject === '【MESHu】アカウント登録が完了しました（即時ご利用いただけます）';
        });
    }
}
