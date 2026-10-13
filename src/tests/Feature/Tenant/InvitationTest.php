<?php

namespace Tests\Feature\Tenant;

use App\Mail\CompanyInvitationMail;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminUserA;
    protected User $staffUserA;
    protected User $adminUserB;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // 自社企業A
        $this->companyA = Company::create([
            'url_code'         => "inv-a-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => '自社企業A株式会社',
            'abbreviated_name' => '企業A',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        // 他社企業B
        $this->companyB = Company::create([
            'url_code'         => "inv-b-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => '他社企業B株式会社',
            'abbreviated_name' => '企業B',
            'tel'              => '03-3333-4444',
            'zip_code'         => '100-0002',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        // 企業A 管理者
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "admin-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業A管理者',
            'role'            => 'admin',
            'is_all_assigned' => true,
            'status'          => 'active',
        ]);

        // 企業A 一般スタッフ
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業Aスタッフ',
            'role'            => 'staff',
            'is_all_assigned' => false,
            'status'          => 'active',
        ]);

        // 企業B 管理者
        $this->adminUserB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => "admin-b-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業B管理者',
            'role'            => 'admin',
            'is_all_assigned' => true,
            'status'          => 'active',
        ]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_invitation(): void
    {
        $response = $this->get('/invitation');
        $response->assertRedirect();
    }

    #[Test]
    public function test_staff_user_cannot_access_invitation(): void
    {
        $response = $this->actingAs($this->staffUserA, 'web')->get('/invitation');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_admin_user_can_view_invitation_list(): void
    {
        // 企業Aの招待
        $invA = Invitation::create([
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'invitation_code'  => 'code-a-12345',
            'buysell'          => 'buyer',
            'email'            => 'target-a@example.com',
            'mail_subject'     => '企業Aからの招待件名',
            'mail_detail'      => '企業Aからの招待本文',
            'company_name'     => '招待先ターゲットA社',
            'corporate_number' => '3333333333333',
        ]);

        // 企業Bの招待（他社データ）
        $invB = Invitation::create([
            'company_id'       => $this->companyB->id,
            'user_id'          => $this->adminUserB->id,
            'invitation_code'  => 'code-b-12345',
            'buysell'          => 'seller',
            'email'            => 'target-b@example.com',
            'mail_subject'     => '企業Bからの招待件名',
            'mail_detail'      => '企業Bからの招待本文',
            'company_name'     => '招待先ターゲットB社',
            'corporate_number' => '4444444444444',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/invitation');

        $response->assertStatus(200);
        $response->assertSee('招待先ターゲットA社');
        $response->assertSee('target-a@example.com');
        $response->assertDontSee('招待先ターゲットB社');
    }

    #[Test]
    public function test_invitation_list_search_and_filter(): void
    {
        Invitation::create([
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'invitation_code'  => 'code-filter-1',
            'buysell'          => 'buyer',
            'email'            => 'filter-one@example.com',
            'mail_subject'     => '件名1',
            'mail_detail'      => '本文1',
            'company_name'     => 'アルファ交易株式会社',
            'corporate_number' => '5555555555555',
        ]);

        Invitation::create([
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'invitation_code'  => 'code-filter-2',
            'buysell'          => 'seller',
            'email'            => 'filter-two@example.com',
            'mail_subject'     => '件名2',
            'mail_detail'      => '本文2',
            'company_name'     => 'ベータ商事株式会社',
            'corporate_number' => '6666666666666',
        ]);

        // キーワード検索
        $resKeyword = $this->actingAs($this->adminUserA, 'web')->get('/invitation?keyword=アルファ');
        $resKeyword->assertStatus(200);
        $resKeyword->assertSee('アルファ交易株式会社');
        $resKeyword->assertDontSee('ベータ商事株式会社');

        // 連携属性絞り込み (seller)
        $resBuysell = $this->actingAs($this->adminUserA, 'web')->get('/invitation?buysell=seller');
        $resBuysell->assertStatus(200);
        $resBuysell->assertSee('ベータ商事株式会社');
        $resBuysell->assertDontSee('アルファ交易株式会社');
    }

    #[Test]
    public function test_admin_user_can_view_new_invitation_form(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->get('/invitation/new');

        $response->assertStatus(200);
        $response->assertSee('未登録企業の招待メール作成');
        $response->assertSee("【MESHu】{$this->companyA->name}より取引連携のご招待");
    }

    #[Test]
    public function test_confirm_validation_fails_with_invalid_data(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->post('/invitation/confirm', [
            'company_name'     => '',
            'corporate_number' => '12345', // 13桁未満
            'user_name'        => '',
            'email'            => 'invalid-email',
            'buysell'          => 'invalid',
            'mail_subject'     => '',
            'mail_detail'      => '',
        ]);

        $response->assertSessionHasErrors([
            'company_name',
            'corporate_number',
            'user_name',
            'email',
            'buysell',
            'mail_subject',
            'mail_detail',
        ]);
    }

    #[Test]
    public function test_confirm_fails_when_corporate_number_already_registered(): void
    {
        // 既存企業Bの法人番号を指定
        $response = $this->actingAs($this->adminUserA, 'web')->post('/invitation/confirm', [
            'company_name'     => '企業B別名社',
            'corporate_number' => $this->companyB->corporate_number,
            'user_name'        => 'テスト管理者名',
            'email'            => 'partner-b@example.com',
            'buysell'          => 'buyer',
            'mail_subject'     => 'テスト件名',
            'mail_detail'      => 'テスト本文',
        ]);

        $response->assertSessionHasErrors(['corporate_number']);
    }

    #[Test]
    public function test_confirm_succeeds_with_valid_data(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->post('/invitation/confirm', [
            'company_name'     => '新規パートナー株式会社',
            'corporate_number' => '9999999999999',
            'user_name'        => '山田 太郎',
            'email'            => 'yamada@partner-new.co.jp',
            'buysell'          => 'buyer',
            'mail_subject'     => '連携のご案内',
            'mail_detail'      => 'よろしくお願いします。',
        ]);

        $response->assertStatus(200);
        $response->assertSee('招待送信内容の確認');
        $response->assertSee('新規パートナー株式会社');
        $response->assertSee('yamada@partner-new.co.jp');
    }

    #[Test]
    public function test_send_creates_invitation_and_sends_email(): void
    {
        $postData = [
            'company_name'     => '送信用パートナー株式会社',
            'corporate_number' => '8888888888888',
            'user_name'        => '佐藤 次郎',
            'email'            => 'sato@partner-send.co.jp',
            'buysell'          => 'buyer',
            'mail_subject'     => '送信用招待メール件名',
            'mail_detail'      => '送信用招待メール本文',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')->post('/invitation/send', $postData);

        $response->assertRedirect(route('tenant.invitation.complete'));

        // DBに作成されていることを確認
        $this->assertDatabaseHas('invitations', [
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'email'            => 'sato@partner-send.co.jp',
            'company_name'     => '送信用パートナー株式会社',
            'corporate_number' => '8888888888888',
            'buysell'          => 'buyer',
        ]);

        // メールが送信されたことを確認
        Mail::assertSent(CompanyInvitationMail::class, function ($mail) {
            return $mail->hasTo('sato@partner-send.co.jp');
        });

        // 完了画面の閲覧
        $completeRes = $this->actingAs($this->adminUserA, 'web')->get('/invitation/complete');
        $completeRes->assertStatus(200);
        $completeRes->assertSee('招待メールを送信しました');
    }

    #[Test]
    public function test_admin_can_view_invitation_detail(): void
    {
        $inv = Invitation::create([
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'invitation_code'  => 'code-detail-12345',
            'buysell'          => 'buyer',
            'email'            => 'detail-test@example.com',
            'mail_subject'     => '詳細テスト件名',
            'mail_detail'      => '詳細テスト本文',
            'company_name'     => '詳細確認先企業',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get("/invitation/show/{$inv->id}");

        $response->assertStatus(200);
        $response->assertSee('招待詳細の閲覧');
        $response->assertSee('詳細確認先企業');
        $response->assertSee('code-detail-12345');
        $response->assertSee($this->adminUserA->user_name);
    }

    #[Test]
    public function test_admin_cannot_view_other_company_invitation(): void
    {
        // 企業Bの招待データ
        $invB = Invitation::create([
            'company_id'       => $this->companyB->id,
            'user_id'          => $this->adminUserB->id,
            'invitation_code'  => 'code-other-12345',
            'buysell'          => 'buyer',
            'email'            => 'other-company@example.com',
            'mail_subject'     => '他社件名',
            'mail_detail'      => '他社本文',
            'company_name'     => '他社招待対象',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get("/invitation/show/{$invB->id}");
        $response->assertStatus(404);
    }

    #[Test]
    public function test_admin_can_delete_invitation(): void
    {
        $inv = Invitation::create([
            'company_id'       => $this->companyA->id,
            'user_id'          => $this->adminUserA->id,
            'invitation_code'  => 'code-delete-12345',
            'buysell'          => 'buyer',
            'email'            => 'delete-test@example.com',
            'mail_subject'     => '削除件名',
            'mail_detail'      => '削除本文',
            'company_name'     => '削除対象企業',
        ]);

        // 削除確認画面
        $confirmRes = $this->actingAs($this->adminUserA, 'web')->get("/invitation/delete-confirm/{$inv->id}");
        $confirmRes->assertStatus(200);
        $confirmRes->assertSee('招待レコードの削除確認');
        $confirmRes->assertSee('削除対象企業');

        // 削除実行
        $deleteRes = $this->actingAs($this->adminUserA, 'web')->post("/invitation/delete/{$inv->id}");
        $deleteRes->assertRedirect(route('tenant.invitation.index'));

        $this->assertDatabaseMissing('invitations', [
            'id' => $inv->id,
        ]);
    }
}
