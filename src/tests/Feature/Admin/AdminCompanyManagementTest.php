<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminCompanyManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Owner $adminOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminOwner = Owner::create([
            'name'          => '最高管理者',
            'email'         => 'super-admin@meshu.org',
            'password_hash' => Hash::make('AdminPass123'),
            'role'          => 'admin',
            'status'        => 'active',
        ]);
    }

    /**
     * @test
     * 企業一覧画面（/owncorp）が正常に表示され検索できること
     */
    public function 企業一覧が正常に表示されキーワード検索できる()
    {
        $company1 = Company::create([
            'url_code'         => 'search-target-corp',
            'name'             => '検索対象商事株式会社',
            'abbreviated_name' => '検索対象',
            'abbreviated_kana' => 'ケンサクタイショウ',
            'corporate_number' => '1111222233334',
            'tel'              => '03-1234-5678',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $company2 = Company::create([
            'url_code'         => 'other-corp',
            'name'             => '別会社株式会社',
            'abbreviated_name' => '別会社',
            'abbreviated_kana' => 'ベツガイシャ',
            'corporate_number' => '5555666677778',
            'tel'              => '06-9999-8888',
            'zip_code'         => '5300001',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '2-2',
            'status'           => 'active',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->get('/owncorp?keyword=検索対象');

        $response->assertStatus(200);
        $response->assertSee('検索対象商事株式会社');
        $response->assertDontSee('別会社株式会社');
    }

    /**
     * @test
     * 企業詳細画面（/owncorp/show/{id}）が正常に表示されること
     */
    public function 企業詳細画面が正常に表示される()
    {
        $company = Company::create([
            'url_code'         => 'detail-corp',
            'name'             => '詳細表示企業株式会社',
            'abbreviated_name' => '詳細企業',
            'abbreviated_kana' => 'ショウサイキギョウ',
            'corporate_number' => '9999888877776',
            'tel'              => '03-3333-4444',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $user = User::create([
            'company_id'      => $company->id,
            'email'           => 'admin@detail-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '詳細担当者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->get("/owncorp/show/{$company->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細表示企業株式会社');
        $response->assertSee('詳細担当者');
    }

    /**
     * @test
     * 事務局管理者が企業の審査承認（suspended → active）を行えること
     */
    public function 事務局管理者が企業ステータスを稼働中に変更できる()
    {
        $company = Company::create([
            'url_code'         => 'approve-target-corp',
            'name'             => '承認待ち企業株式会社',
            'abbreviated_name' => '承認待ち',
            'abbreviated_kana' => 'ショウニンマチ',
            'tel'              => '03-7777-8888',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'suspended',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->post("/owncorp/delete/{$company->id}", [
                             'status' => 'active',
                         ]);

        $response->assertRedirect('/owncorp');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('companies', [
            'id'     => $company->id,
            'status' => 'active',
        ]);
    }

    /**
     * @test
     * 認証バッジの付与と解除のトグル操作が正常に行えること
     */
    public function 認証バッジの付与と解除が正常にトグル動作する()
    {
        $company = Company::create([
            'url_code'         => 'badge-corp',
            'name'             => 'バッジ検証企業株式会社',
            'abbreviated_name' => 'バッジ検証',
            'abbreviated_kana' => 'バッジケンショウ',
            'tel'              => '03-8888-9999',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        // 1. バッジ付与（初回）
        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->post("/owncorp/toggle-badge/{$company->id}");

        $response->assertRedirect("/owncorp/show/{$company->id}");
        $this->assertDatabaseHas('company_badges', [
            'company_id' => $company->id,
            'status'     => 'active',
        ]);

        // 2. バッジ解除（2回目）
        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->post("/owncorp/toggle-badge/{$company->id}");

        $response->assertRedirect("/owncorp/show/{$company->id}");
        $this->assertDatabaseHas('company_badges', [
            'company_id' => $company->id,
            'status'     => 'revoked',
        ]);
    }

    /**
     * @test
     * 事務局からの代理ログイン（impersonate）で企業ダッシュボードへ遷移できること
     */
    public function 代理ログインにより企業ダッシュボードへ直接ログイン遷移できる()
    {
        $company = Company::create([
            'url_code'         => 'impersonate-corp',
            'name'             => '代理ログイン対象企業',
            'abbreviated_name' => '代理対象',
            'abbreviated_kana' => 'ダイリタイショウ',
            'tel'              => '03-0000-1111',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $adminUser = User::create([
            'company_id'      => $company->id,
            'email'           => 'admin@impersonate-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '代理対象管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
                         ->get("/owncorp/impersonate/{$company->id}");

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($adminUser, 'web');
        $this->assertTrue(session('is_impersonating'));
    }
}
