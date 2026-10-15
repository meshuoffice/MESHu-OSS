<?php

namespace Tests\Feature\Tenant;

use App\Mail\ContactAutoReplyMail;
use App\Mail\ContactInquiryMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'url_code'         => 'contact-test-corp',
            'name'             => '問い合わせ検証企業',
            'abbreviated_name' => '問合せ検証',
            'abbreviated_kana' => 'トイアワセケンショウ',
            'tel'              => '03-9999-0000',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $this->user = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'contact-user@test.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '問合せ 太郎',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面へリダイレクトされること
     */
    #[Test]
    public function 未ログインユーザーがお問い合わせ画面群にアクセスした場合はリダイレクトされる()
    {
        $this->get('/contact')->assertRedirect();
        $this->post('/contact/confirm', [])->assertRedirect();
        $this->post('/contact/send', [])->assertRedirect();
        $this->get('/contact/complete')->assertRedirect();
    }

    /**
     * お問い合わせフォーム画面（/contact）が正常に表示されること
     */
    #[Test]
    public function お問い合わせ入力画面が正常に表示される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('事務局へのお問い合わせ');
        $response->assertSee('問い合わせ検証企業');
        $response->assertSee('問合せ 太郎');
        $response->assertSee('contact-user@test.com');
        $response->assertSee('よくあるご質問 (FAQ)');
    }

    /**
     * 必須項目が不足している場合バリデーションエラーとなること
     */
    #[Test]
    public function 必須項目が不足している場合はバリデーションエラーとなる()
    {
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => '',
                'subject'       => '',
                'message'       => '',
                'agree_privacy' => '',
            ]);

        $response->assertSessionHasErrors([
            'contact_type',
            'subject',
            'message',
            'agree_privacy',
        ]);
    }

    /**
     * 不正なお問い合わせ種別が指定された場合バリデーションエラーとなること
     */
    #[Test]
    public function 不正なお問い合わせ種別はバリデーションエラーとなる()
    {
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'unknown_type',
                'subject'       => 'テスト件名',
                'message'       => 'テスト本文',
                'agree_privacy' => '1',
            ]);

        $response->assertSessionHasErrors(['contact_type']);
    }

    /**
     * 文字数制限を超過した場合バリデーションエラーとなること
     */
    #[Test]
    public function 文字数制限を超過した場合はバリデーションエラーとなる()
    {
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'trouble',
                'subject'       => str_repeat('a', 256),
                'message'       => str_repeat('b', 5001),
                'agree_privacy' => '1',
            ]);

        $response->assertSessionHasErrors(['subject', 'message']);
    }

    /**
     * 不正な拡張子または容量オーバーのファイル添付はバリデーションエラーとなること
     */
    #[Test]
    public function 不正なファイル形式または容量オーバーはバリデーションエラーとなる()
    {
        Storage::fake('local');

        // 1. 不正な拡張子（exe）
        $invalidExtFile = UploadedFile::fake()->create('malicious.exe', 100);
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'trouble',
                'subject'       => '不具合の報告',
                'message'       => '画面が動きません',
                'agree_privacy' => '1',
                'attachment'    => $invalidExtFile,
            ]);

        $response->assertSessionHasErrors(['attachment']);

        // 2. 容量オーバー（10MB = 10240KB 超過）
        $largeFile = UploadedFile::fake()->create('large_image.png', 11000);
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'trouble',
                'subject'       => '不具合の報告',
                'message'       => '画面が動きません',
                'agree_privacy' => '1',
                'attachment'    => $largeFile,
            ]);

        $response->assertSessionHasErrors(['attachment']);
    }

    /**
     * 添付ファイルありの場合の確認、送信、メール配信、および一時ファイル削除の検証
     */
    #[Test]
    public function 添付ファイルありでお問い合わせが送信され一時ファイルが削除される()
    {
        Storage::fake('local');
        Mail::fake();

        $attachment = UploadedFile::fake()->create('screenshot.png', 500, 'image/png');

        $formData = [
            'contact_type'  => 'operation',
            'subject'       => '操作手順の確認',
            'message'       => '画面の操作方法について教えてください。',
            'agree_privacy' => '1',
            'attachment'    => $attachment,
        ];

        // 1. 確認画面へ進む
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', $formData);

        $response->assertStatus(200);
        $response->assertSee('お問い合わせ内容の確認');
        $response->assertSee('screenshot.png');
        $response->assertSee('操作手順の確認');

        // セッションから保存された一時ファイルパスを取得
        $sessionData = session('contact_form_data');
        $this->assertNotEmpty($sessionData);
        $this->assertEquals('screenshot.png', $sessionData['original_file_name']);
        $tempPath = $sessionData['temp_attachment_path'];
        $this->assertNotEmpty($tempPath);

        // 一時ファイルが存在することを確認
        Storage::disk('local')->assertExists($tempPath);

        // 2. 送信実行
        $sendResponse = $this->actingAs($this->user, 'web')
            ->withSession(['contact_form_data' => $sessionData])
            ->post('/contact/send');

        $sendResponse->assertRedirect(route('tenant.contact.complete'));

        // 3. メール配信の検証
        $expectedAdminEmail = config('mail.office.main', env('MAIL_OFFICE_MAIN', env('FIRST_OWNER_EMAIL', 'support@meshu.org')));
        $expectedOfficeSub  = config('mail.office.sub', env('MAIL_OFFICE_SUB', null));

        Mail::assertSent(ContactInquiryMail::class, function ($mail) use ($expectedAdminEmail, $expectedOfficeSub) {
            $isToCorrect  = $mail->hasTo($expectedAdminEmail);
            $isBccCorrect = empty($expectedOfficeSub) || $mail->hasBcc($expectedOfficeSub);

            return $isToCorrect
                && $isBccCorrect
                && $mail->hasSubject('【MESHuお問い合わせ】[システムの操作方法・使い方の相談] 操作手順の確認 (問い合わせ検証企業)')
                && $mail->hasReplyTo('contact-user@test.com', '問合せ 太郎');
        });

        Mail::assertSent(ContactAutoReplyMail::class, function ($mail) {
            return $mail->hasTo('contact-user@test.com')
                && $mail->hasSubject('【MESHu】お問い合わせを受領いたしました');
        });

        // 4. 送信完了後のクリーンアップ検証（一時ファイル削除＆セッション破棄）
        Storage::disk('local')->assertMissing($tempPath);
        $this->assertNull(session('contact_form_data'));
    }

    /**
     * 確認画面から戻ってファイルを再選択しない場合でも前回の添付ファイルが保持されること
     */
    #[Test]
    public function 修正画面から再確認へ進む際も添付ファイル情報が保持される()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('initial_doc.pdf', 300, 'application/pdf');

        // 初回確認
        $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'link',
                'subject'       => '連携について',
                'message'       => '連携の申請方法です',
                'agree_privacy' => '1',
                'attachment'    => $file,
            ]);

        $firstSessionData = session('contact_form_data');
        $initialTempPath  = $firstSessionData['temp_attachment_path'];
        Storage::disk('local')->assertExists($initialTempPath);

        // ファイルを空にして再送信（修正から戻ってそのまま確認へ進んだ場合）
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/confirm', [
                'contact_type'  => 'link',
                'subject'       => '連携について（修正後件名）',
                'message'       => '連携の申請方法です（修正後本文）',
                'agree_privacy' => '1',
            ]);

        $response->assertStatus(200);
        $response->assertSee('initial_doc.pdf');
        $response->assertSee('連携について（修正後件名）');

        $secondSessionData = session('contact_form_data');
        $this->assertEquals($initialTempPath, $secondSessionData['temp_attachment_path']);
        $this->assertEquals('initial_doc.pdf', $secondSessionData['original_file_name']);
        Storage::disk('local')->assertExists($initialTempPath);
    }

    /**
     * セッション切れ（直接アクセス）時のハンドリング検証
     */
    #[Test]
    public function セッションが存在しない状態で送信した場合は入力画面へリダイレクトされる()
    {
        $response = $this->actingAs($this->user, 'web')
            ->post('/contact/send');

        $response->assertRedirect(route('tenant.contact'));
        $response->assertSessionHas('error', 'セッションの有効期限が切れました。もう一度ご入力ください。');
    }

    /**
     * 送信完了画面が正常に表示されること
     */
    #[Test]
    public function 送信完了画面が正常に表示される()
    {
        $response = $this->actingAs($this->user, 'web')
            ->get('/contact/complete');

        $response->assertStatus(200);
        $response->assertSee('お問い合わせを送信いたしました');
        $response->assertSee('ダッシュボードへ戻る');
        $response->assertSee('ヘルプ・FAQを見る');
    }
}
