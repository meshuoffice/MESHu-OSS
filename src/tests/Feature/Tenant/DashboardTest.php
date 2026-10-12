<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\CompanyLink;
use App\Models\Owner;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected Company $companyC;
    protected User $adminUserA;
    protected User $staffUserA;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = uniqid();

        // 自社 (企業A)
        $this->companyA = Company::create([
            'url_code'         => "dash-a-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => '株式会社ダッシュボード企業A',
            'abbreviated_name' => '企業A',
            'status'           => 'active',
        ]);

        // 取引先 (企業B: 認証バッジあり)
        $this->companyB = Company::create([
            'url_code'         => "dash-b-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => '株式会社取引先企業B',
            'abbreviated_name' => '企業B',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町1-1',
            'status'           => 'active',
        ]);

        $owner = Owner::create([
            'name'          => '審査官',
            'email'         => "verifier-{$suffix}@example.com",
            'password_hash' => Hash::make('Password123'),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        CompanyBadge::create([
            'company_id'        => $this->companyB->id,
            'verifier_owner_id' => $owner->id,
            'status'            => 'active',
            'signature'         => 'dummy_badge_signature',
        ]);

        // 取引先 (企業C: 通常企業)
        $this->companyC = Company::create([
            'url_code'         => "dash-c-{$suffix}",
            'corporate_number' => '3333333333333',
            'name'             => '株式会社取引先企業C',
            'abbreviated_name' => '企業C',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '梅田1-1',
            'status'           => 'active',
        ]);

        // 企業Aの管理者
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        // 企業Aの担当限定スタッフ (is_all_assigned = 0)
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);
    }

    #[Test]
    public function 未認証ユーザーはダッシュボードにアクセスできない(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/');
    }

    #[Test]
    public function 管理者はダッシュボードの最近のつながり企業ボックスとサマリーを確認できる(): void
    {
        // リンク1: A(購入) - B(販売) [承認済み]
        $link1 = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        // リンク2: C(購入) - A(販売) [回答待ち]
        $link2 = CompanyLink::create([
            'buyer_company_id'  => $this->companyC->id,
            'seller_company_id' => $this->companyA->id,
            'status'            => 'pending',
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('最近のつながり企業 (MESH)');
        $response->assertSee('すべて表示');
        $response->assertSee('企業とつながる');

        // つながり企業サマリーカウント（承認済みは1件）
        $response->assertViewHas('approvedLinkCount', 1);

        // テーブル表示確認
        $response->assertSee('株式会社取引先企業B');
        $response->assertSee('認証企業');
        $response->assertSee('自社：購入側(買)');
        $response->assertSee('承認済み');
        $response->assertSee(route('tenant.link.detail', ['link_id' => $link1->id]));

        $response->assertSee('株式会社取引先企業C');
        $response->assertSee('自社：販売側(売)');
        $response->assertSee('未承認');
    }

    #[Test]
    public function 担当限定スタッフには割り当てられた企業のみが表示されアクションは非表示となる(): void
    {
        // リンク1: A - B (スタッフ担当)
        $link1 = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);
        UserAssignedLink::create([
            'company_link_id' => $link1->id,
            'user_id'         => $this->staffUserA->id,
        ]);

        // リンク2: A - C (スタッフ未担当)
        $link2 = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyC->id,
            'status'            => 'approved',
        ]);

        $response = $this->actingAs($this->staffUserA, 'web')->get('/dashboard');

        $response->assertStatus(200);

        // 担当している B は表示され、未担当の C は表示されない
        $response->assertSee('株式会社取引先企業B');
        $response->assertDontSee('株式会社取引先企業C');

        // スタッフには詳細リンクがなく「-」が表示される
        $response->assertDontSee(route('tenant.link.detail', ['link_id' => $link1->id]));
        $response->assertDontSee('企業とつながる');
    }

    #[Test]
    public function ダッシュボードにお知らせ最新5件が表示される(): void
    {
        \App\Models\Notice::create([
            'sender_type'  => 'admin',
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => 'ダッシュボード用お知らせテスト',
            'content'      => 'ダッシュボードにお知らせが正常に表示されるか検証します。',
            'is_important' => true,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ダッシュボード用お知らせテスト');
        $response->assertSee('重要');
        $response->assertSee(route('tenant.notice.index'));
    }
}
