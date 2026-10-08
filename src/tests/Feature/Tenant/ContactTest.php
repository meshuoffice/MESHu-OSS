<?php

namespace Tests\Feature\Tenant;

use App\Mail\ContactAutoReplyMail;
use App\Mail\ContactInquiryMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
     * お問い合わせフォーム画面（/contact）が正常に表示されること
     */
    #[Test]
    public function お問い合わせ入力画面が正常に表示される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('事務局へのお問い合わせ');
    }

    /**
     * お問い合わせの確認および送信が正常に行われメールが配信されること
     */
    #[Test]
    public function お問い合わせが送信され事務局宛通知とユーザー自動返信が配信される()
    {
        Mail::fake();

        $formData = [
            'contact_type'  => 'system',
            'subject'       => '機能についての質問',
            'message'       => 'MESHuの受発注機能について質問があります。',
            'agree_privacy' => '1',
        ];

        // 1. 確認画面へ進む
        $response = $this->actingAs($this->user, 'web')
                         ->post('/contact/confirm', $formData);

        $response->assertStatus(200);
        $response->assertSee('お問い合わせ内容の確認');
        $response->assertSee('機能についての質問');

        // 2. 送信実行
        $response = $this->actingAs($this->user, 'web')
                         ->withSession(['contact_form_data' => $formData])
                         ->post('/contact/send');

        $response->assertRedirect('/contact/complete');

        // 3. メールの配信検証
        Mail::assertSent(ContactInquiryMail::class);
        Mail::assertSent(ContactAutoReplyMail::class, function ($mail) {
            return $mail->hasTo('contact-user@test.com');
        });
    }
}
