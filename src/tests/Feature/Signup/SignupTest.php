<?php

namespace Tests\Feature\Signup;

use App\Mail\AdminSignupNotificationMail;
use App\Mail\WelcomeSignupMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 新規登録フォーム画面（/signup）が正常に表示されること
     */
    #[Test]
    public function 新規登録フォーム画面が正常に表示される()
    {
        $response = $this->get('/signup');
        $response->assertStatus(200);
        $response->assertSee('アカウント新規登録');
    }

    /**
     * 法人番号やカナのバリデーションが正しく機能すること
     */
    #[Test]
    public function バリデーションエラー時は確認画面に進めずエラーが返る()
    {
        // 1. 企業名カナに漢字を含めた場合
        $response = $this->post('/signup/confirm', [
            'companyName'      => 'テスト企業',
            'companyNameShort' => 'テスト',
            'companyNameKana'  => 'テスト漢字', // 漢字はエラー
            'corporateNumber'  => '1234567890123',
            'phone'            => '03-1234-5678',
            'zipCode'          => '1000001',
            'prefecture'       => '東京都',
            'city'             => '千代田区',
            'address'          => '1-1',
            'adminName'        => '山田太郎',
            'adminNameKana'    => 'ヤマダタロウ',
            'adminEmail'       => 'admin@test.com',
            'agreeTerms'       => '1',
        ]);

        $response->assertSessionHasErrors('companyNameKana');

        // 2. 法人番号が13桁未満の場合
        $response = $this->post('/signup/confirm', [
            'companyName'      => 'テスト企業',
            'companyNameShort' => 'テスト',
            'companyNameKana'  => 'テストカナ',
            'corporateNumber'  => '12345', // 13桁ではないためエラー
            'phone'            => '03-1234-5678',
            'zipCode'          => '1000001',
            'prefecture'       => '東京都',
            'city'             => '千代田区',
            'address'          => '1-1',
            'adminName'        => '山田太郎',
            'adminNameKana'    => 'ヤマダタロウ',
            'adminEmail'       => 'admin@test.com',
            'agreeTerms'       => '1',
        ]);

        $response->assertSessionHasErrors('corporateNumber');

        // 3. 電話番号に英字が含まれる場合（例: 00-0000-aaaa）
        $response = $this->post('/signup/confirm', [
            'companyName'      => 'テスト企業',
            'companyNameShort' => 'テスト',
            'companyNameKana'  => 'テストカナ',
            'corporateNumber'  => '1234567890123',
            'phone'            => '00-0000-aaaa', // 不正な英字混入
            'zipCode'          => '1000001',
            'prefecture'       => '東京都',
            'city'             => '千代田区',
            'address'          => '1-1',
            'adminName'        => '山田太郎',
            'adminNameKana'    => 'ヤマダタロウ',
            'adminEmail'       => 'admin@test.com',
            'agreeTerms'       => '1',
        ]);

        $response->assertSessionHasErrors('phone');

        // 4. 郵便番号に英字・不正文字列が含まれる場合（例: abc-xy）
        $response = $this->post('/signup/confirm', [
            'companyName'      => 'テスト企業',
            'companyNameShort' => 'テスト',
            'companyNameKana'  => 'テストカナ',
            'corporateNumber'  => '1234567890123',
            'phone'            => '03-1234-5678',
            'zipCode'          => 'abc-xy', // 不正な文字列
            'prefecture'       => '東京都',
            'city'             => '千代田区',
            'address'          => '1-1',
            'adminName'        => '山田太郎',
            'adminNameKana'    => 'ヤマダタロウ',
            'adminEmail'       => 'admin@test.com',
            'agreeTerms'       => '1',
        ]);

        $response->assertSessionHasErrors('zipCode');
    }

    /**
     * 正しい入力で新規登録が完了し、企業がsuspendedで作成され、メールが送信されること
     */
    #[Test]
    public function 新規登録が成功し企業が審査中ステータスで作成されメールが送信される()
    {
        Mail::fake();

        $signupData = [
            'companyName'      => '新規申請企業株式会社',
            'companyNameShort' => '新規申請',
            'companyNameKana'  => 'シンケイシンセイ',
            'corporateNumber'  => '9876543210987',
            'phone'            => '03-9999-8888',
            'zipCode'          => '1000001',
            'prefecture'       => '東京都',
            'city'             => '千代田区',
            'address'          => '永田町1-1',
            'adminName'        => '新規 太郎',
            'adminNameKana'    => 'シンキ タロウ',
            'adminEmail'       => 'new-admin@sample-corp.com',
            'agreeTerms'       => '1',
        ];

        // 1. 確認画面へ進む
        $response = $this->post('/signup/confirm', $signupData);
        $response->assertStatus(200);
        $response->assertSee('新規申請企業株式会社');

        // 2. 登録実行（本番仕様 suspended として明示的に検証）
        \Illuminate\Support\Facades\Config::set('meshu.company_initial_status', 'suspended');
        $response = $this->withSession(['signup_data' => $signupData])
                         ->post('/signup/register');

        $response->assertStatus(200);
        $response->assertSee('アカウント登録申請を受け付けました');

        // 3. データベースの検証
        $this->assertDatabaseHas('companies', [
            'name'             => '新規申請企業株式会社',
            'corporate_number' => '9876543210987',
            'status'           => 'suspended', // 初期状態は審査中（suspended）
        ]);

        $this->assertDatabaseHas('users', [
            'email'           => 'new-admin@sample-corp.com',
            'user_name'       => '新規 太郎',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        // 4. メールの配信検証（登録者宛て＆事務局通知）
        Mail::assertSent(WelcomeSignupMail::class, function ($mail) {
            return $mail->hasTo('new-admin@sample-corp.com');
        });

        Mail::assertSent(AdminSignupNotificationMail::class);
    }
}
