<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\CompanyLink;
use App\Models\Invitation;
use App\Models\Owner;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyLinkTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected Company $companyC;
    protected Owner $verifierOwner;
    protected User $adminUserA;
    protected User $staffUserA;
    protected User $adminUserB;
    protected User $adminUserC;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // テスト企業A (自社: 購入側)
        $this->companyA = Company::create([
            'url_code'         => "company-a-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '千代田1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
            'status'           => 'active',
        ]);

        // テスト企業B (取引先: 販売側)
        $this->companyB = Company::create([
            'url_code'         => "company-b-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => '株式会社企業B',
            'abbreviated_name' => '企業B',
            'abbreviated_kana' => 'キギョウビー',
            'tel'              => '06-9999-8888',
            'zip_code'         => '530-0001',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '梅田1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'floor',
            'tax_rounding'     => 'floor',
            'status'           => 'active',
        ]);

        // テスト企業C (第三者企業 / 未連携企業)
        $this->companyC = Company::create([
            'url_code'         => "company-c-{$suffix}",
            'corporate_number' => '3333333333333',
            'name'             => '株式会社企業Cロジスティクス',
            'abbreviated_name' => '企業C',
            'abbreviated_kana' => 'キギョウシー',
            'tel'              => '045-333-4444',
            'zip_code'         => '220-0001',
            'prefecture_code'  => 14,
            'city'             => '横浜市西区',
            'address'          => 'みなとみらい1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
            'status'           => 'active',
        ]);

        // 事務局管理者 (認証バッジ付与者)
        $this->verifierOwner = Owner::create([
            'name'          => '認証審査員',
            'email'         => "verifier-{$suffix}@example.com",
            'password_hash' => Hash::make('Password123'),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        // 企業Bに認証バッジを付与
        CompanyBadge::create([
            'company_id'        => $this->companyB->id,
            'verifier_owner_id' => $this->verifierOwner->id,
            'status'            => 'active',
            'signature'         => 'dummy_test_signature_hash',
        ]);

        // 企業Aの管理者 (role=admin)
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "admin-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 管理者',
            'user_name_kana'  => 'キギョウエー カンリシャ',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 企業Aの一般スタッフ (role=staff, 個別担当)
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A スタッフ',
            'user_name_kana'  => 'キギョウエー スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 企業Bの管理者
        $this->adminUserB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => "admin-b-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 管理者',
            'user_name_kana'  => 'キギョウビー カンリシャ',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 企業Cの管理者
        $this->adminUserC = User::create([
            'company_id'      => $this->companyC->id,
            'email'           => "admin-c-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業C 管理者',
            'user_name_kana'  => 'キギョウシー カンリシャ',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは一覧にアクセスできずリダイレクトされる(): void
    {
        $response = $this->get('/link');
        $response->assertRedirect('/');
    }

    #[Test]
    public function 一般スタッフはアクセス権限がなく403となる(): void
    {
        $response = $this->actingAs($this->staffUserA, 'web')->get('/link');
        $response->assertStatus(403);
    }

    #[Test]
    public function 管理者はつながり一覧にアクセスでき自社連携企業が表示される(): void
    {
        // A(buyer) と B(seller) の連携を作成
        CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/link');

        $response->assertStatus(200);
        $response->assertSee('つながり管理 (MESH)');
        $response->assertSee($this->companyB->name);
        $response->assertSee('自社：購入側(買)');
        $response->assertSee('承認済み');
    }

    #[Test]
    public function つながり一覧で自社属性やステータスでフィルタできる(): void
    {
        // A(buyer) と B(seller) approved
        CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        // A(seller) と C(buyer) pending
        CompanyLink::create([
            'buyer_company_id'  => $this->companyC->id,
            'seller_company_id' => $this->companyA->id,
            'status'            => 'pending',
        ]);

        // seller フィルタ
        $response = $this->actingAs($this->adminUserA, 'web')->get('/link?my_role=seller');
        $response->assertStatus(200);
        $response->assertSee($this->companyC->name);
        $response->assertDontSee($this->companyB->name);

        // pending フィルタ
        $responsePending = $this->actingAs($this->adminUserA, 'web')->get('/link?status=pending');
        $responsePending->assertStatus(200);
        $responsePending->assertSee($this->companyC->name);
        $responsePending->assertDontSee($this->companyB->name);
    }

    #[Test]
    public function 新規つながり検索画面で未連携企業が検索できる(): void
    {
        // AとBは連携済み
        CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/link/search');

        $response->assertStatus(200);
        $response->assertSee('企業検索（新しいつながり）');

        // 検索結果データ（searchResults）から検証（ヘッダーに自社名が表示されるためviewDataで安全に検証）
        $response->assertViewHas('searchResults', function ($results) {
            $ids = collect($results)->pluck('id');
            // 未連携の C は含まれ、連携済みの B および 自社 A は除外されること
            return $ids->contains($this->companyC->id)
                && !$ids->contains($this->companyB->id)
                && !$ids->contains($this->companyA->id);
        });

        // 検索結果テーブル表示
        $response->assertSee($this->companyC->name);
        $response->assertDontSee($this->companyB->name);
    }

    #[Test]
    public function 新規つながり検索でキーワードや法人番号で絞り込みができる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->get('/link/search?keyword=ロジスティクス');
        $response->assertStatus(200);
        $response->assertSee($this->companyC->name);

        $responseNotFound = $this->actingAs($this->adminUserA, 'web')->get('/link/search?keyword=存在しない企業名999');
        $responseNotFound->assertStatus(200);
        $responseNotFound->assertSee('該当する企業が見つかりませんでした。');
    }

    #[Test]
    public function 企業詳細画面が表示でき自社宛はアクセスが拒否される(): void
    {
        // 相手企業 C の確認画面
        $response = $this->actingAs($this->adminUserA, 'web')->get("/link/show/{$this->companyC->id}");
        $response->assertStatus(200);
        $response->assertSee($this->companyC->name);
        $response->assertSee('連携申請の確認');

        // 自社宛はエラーリダイレクト
        $responseSelf = $this->actingAs($this->adminUserA, 'web')->get("/link/show/{$this->companyA->id}");
        $responseSelf->assertRedirect(route('tenant.link.search'));
        $responseSelf->assertSessionHas('error', '自社宛には連携申請を行えません。');
    }

    #[Test]
    public function 連携申請が正常に送信され相手管理者に通知される(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->adminUserA, 'web')->post('/link/request', [
            'target_company_id' => $this->companyC->id,
        ]);

        $response->assertRedirect(route('tenant.link.request_complete'));

        // DBに pending レコードが作成されていること
        $this->assertDatabaseHas('company_links', [
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyC->id,
            'status'            => 'pending',
        ]);

        // 相手企業（C）の管理者宛てに連携依頼通知メールが送信されること
        Mail::assertSent(\App\Mail\CompanyLinkRequestedMail::class, function ($mail) {
            return $mail->hasTo($this->adminUserC->email);
        });
    }
    #[Test]
    public function 既に連携中または申請中の企業への重複申請はエラーとなる(): void
    {
        CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->post('/link/request', [
            'target_company_id' => $this->companyB->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'すでに連携中（承認済み）の企業です。');
    }

    #[Test]
    public function 連携詳細画面が表示され他社の連携にはアクセスできない(): void
    {
        $link = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        // BとCの連携（Aは無関係）
        $linkBC = CompanyLink::create([
            'buyer_company_id'  => $this->companyB->id,
            'seller_company_id' => $this->companyC->id,
            'status'            => 'approved',
        ]);

        // 自社連携は200
        $response = $this->actingAs($this->adminUserA, 'web')->get("/link/detail/{$link->id}");
        $response->assertStatus(200);
        $response->assertSee($this->companyB->name);
        $response->assertSee('取引先企業詳細');

        // 他社連携は閲覧権限エラーリダイレクト
        $responseBC = $this->actingAs($this->adminUserA, 'web')->get("/link/detail/{$linkBC->id}");
        $responseBC->assertRedirect(route('tenant.link.index'));
        $responseBC->assertSessionHas('error');
    }
    #[Test]
    public function 連携ステータスを承認および否認に変更でき承認時のみ買手管理者にメールが送信される(): void
    {
        Mail::fake();

        $link = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'pending',
        ]);

        // 販売側 (B) の管理者が承認
        $responseApprove = $this->actingAs($this->adminUserB, 'web')->post("/link/status/{$link->id}", [
            'status' => 'approved',
        ]);
        $responseApprove->assertRedirect();
        $responseApprove->assertSessionHas('success');

        $this->assertDatabaseHas('company_links', [
            'id'     => $link->id,
            'status' => 'approved',
        ]);

        // 承認時は買手側管理者（A）にメール送信される
        Mail::assertSent(\App\Mail\CompanyLinkApprovedMail::class, function ($mail) {
            return $mail->hasTo($this->adminUserA->email);
        });

        // 否認テスト用に Mail::fake() をリセット
        Mail::fake();

        // 否認（取引停止）に変更
        $responseDeny = $this->actingAs($this->adminUserB, 'web')->post("/link/status/{$link->id}", [
            'status' => 'denied',
        ]);
        $responseDeny->assertRedirect();

        $this->assertDatabaseHas('company_links', [
            'id'     => $link->id,
            'status' => 'denied',
        ]);

        // 否認時はメール送信されない
        Mail::assertNothingSent();
    }

    #[Test]
    public function 担当従業員の割り当てを更新できる(): void
    {
        $link = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->post("/link/assign-users/{$link->id}", [
            'user_ids' => [$this->staffUserA->id],
        ]);

        $response->assertRedirect(route('tenant.link.detail', ['link_id' => $link->id]));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_assigned_links', [
            'company_link_id' => $link->id,
            'user_id'         => $this->staffUserA->id,
        ]);
    }

    #[Test]
    public function 未登録企業への招待メールを送信できる(): void
    {
        Mail::fake();

        $responsePage = $this->actingAs($this->adminUserA, 'web')->get('/link/invite');
        $responsePage->assertStatus(200);
        $responsePage->assertSee('未登録企業の招待送信');

        $responseSend = $this->actingAs($this->adminUserA, 'web')->post('/link/send-invite', [
            'invitee_name'        => '新規取引先株式会社',
            'invitee_email'       => 'invitee@example.com',
            'invitee_person_name' => '取引 太郎',
            'inviter_role'        => 'buyer',
            'message'             => '連携のご案内です。',
        ]);

        $responseSend->assertRedirect(route('tenant.link.invite'));
        $responseSend->assertSessionHas('success');

        $this->assertDatabaseHas('invitations', [
            'company_id'   => $this->companyA->id,
            'user_id'      => $this->adminUserA->id,
            'company_name' => '新規取引先株式会社',
            'email'        => 'invitee@example.com',
            'buysell'      => 'buyer',
        ]);

        // 招待先宛てに招待メールが送信されること
        Mail::assertSent(\App\Mail\CompanyInvitationMail::class, function ($mail) {
            return $mail->hasTo('invitee@example.com');
        });
    }
}
