<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCompanyManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Owner $adminOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminOwner = Owner::firstOrCreate(
            ['email' => 'super-admin@meshu.org'],
            [
                'name'          => '最高管理者',
                'password_hash' => Hash::make('AdminPass123'),
                'role'          => 'admin',
                'status'        => 'active',
            ]
        );
    }

    #[Test]
    public function 企業一覧が正常に表示されキーワード検索できる()
    {
        $unique = uniqid();
        Company::create([
            'url_code'         => 'search-target-' . $unique,
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

        Company::create([
            'url_code'         => 'other-corp-' . $unique,
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

        $url = route('admin.corp.index', ['keyword' => '検索対象']);
        $response = $this->actingAs($this->adminOwner, 'admin')->get($url);

        $response->assertStatus(200);
        $response->assertSee('検索対象商事株式会社');
        $response->assertDontSee('別会社株式会社');
    }

    #[Test]
    public function 企業詳細画面が正常に表示される()
    {
        $unique = uniqid();
        $company = Company::create([
            'url_code'         => 'detail-corp-' . $unique,
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

        User::create([
            'company_id'      => $company->id,
            'email'           => 'admin-' . $unique . '@detail-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '詳細担当者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $url = route('admin.corp.show', $company->id);
        $response = $this->actingAs($this->adminOwner, 'admin')->get($url);

        $response->assertStatus(200);
        $response->assertSee('詳細表示企業株式会社');
        $response->assertSee('詳細担当者');
    }

    #[Test]
    public function 認証バッジの付与と解除が正常にトグル動作する()
    {
        $unique = uniqid();
        $company = Company::create([
            'url_code'         => 'badge-corp-' . $unique,
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

        $toggleUrl = route('admin.corp.toggle_badge', $company->id);
        $showUrl = route('admin.corp.show', $company->id);

        // 1. バッジ付与
        $response1 = $this->actingAs($this->adminOwner, 'admin')->post($toggleUrl);
        $response1->assertRedirect($showUrl);
        $this->assertDatabaseHas('company_badges', [
            'company_id' => $company->id,
            'status'     => 'active',
        ]);

        // 2. バッジ解除
        $response2 = $this->actingAs($this->adminOwner, 'admin')->post($toggleUrl);
        $response2->assertRedirect($showUrl);
        $this->assertDatabaseMissing('company_badges', [
            'company_id' => $company->id,
        ]);
    }

    #[Test]
    public function 代理ログインにより企業ダッシュボードへ直接ログイン遷移できる()
    {
        $unique = uniqid();
        $company = Company::create([
            'url_code'         => 'impersonate-corp-' . $unique,
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
            'email'           => 'admin-' . $unique . '@impersonate-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '代理対象管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $impersonateUrl = route('admin.corp.impersonate', $company->id);
        $response = $this->actingAs($this->adminOwner, 'admin')->get($impersonateUrl);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($adminUser, 'web');
        $this->assertTrue(session('impersonated_by_admin'));

        // 代理ログイン画面（別タブ）からのログアウト
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/' . $company->url_code . '/login');
        $this->assertGuest('web');
        $this->assertAuthenticatedAs($this->adminOwner, 'admin');
    }

    /**
     * 企業のステータス変更（稼働化・一時停止・削除状態）が物理削除されずに正しく更新されること
     */
    #[Test]
    public function 企業ステータス変更が物理削除されず正常に更新される()
    {
        $unique = uniqid();
        // 新規登録直後の審査待ち企業（suspended）
        $company = Company::create([
            'url_code'         => 'status-test-' . $unique,
            'name'             => 'ステータス検証株式会社',
            'abbreviated_name' => 'ステータス検証',
            'abbreviated_kana' => 'ステータスケンショウ',
            'tel'              => '03-5555-4444',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'suspended',
        ]);

        $user = User::create([
            'company_id'      => $company->id,
            'email'           => 'admin-' . $unique . '@status-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '審査担当者',
            'role'            => 'admin',
            'status'          => 'suspended',
            'is_all_assigned' => 0,
        ]);

        $deleteUrl = route('admin.corp.delete', $company->id);
        $showUrl = route('admin.corp.show', $company->id);

        // 1. 稼働（active）に更新（承認・有効化）
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post($deleteUrl, ['status' => 'active']);

        $response->assertRedirect($showUrl);
        $this->assertDatabaseHas('companies', [
            'id'     => $company->id,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'active',
        ]);

        // 2. 一時停止（suspended）に更新
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post($deleteUrl, ['status' => 'suspended']);

        $response->assertRedirect($showUrl);
        $this->assertDatabaseHas('companies', [
            'id'     => $company->id,
            'status' => 'suspended',
        ]);
        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'suspended',
        ]);

        // 3. 削除状態（deleted）に更新（論理削除）
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post($deleteUrl, ['status' => 'deleted']);

        $response->assertRedirect($showUrl);
        $this->assertDatabaseHas('companies', [
            'id'     => $company->id,
            'status' => 'deleted',
        ]);
        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'status' => 'deleted',
        ]);
    }

    /**
     * 企業編集画面の表示、確認画面、および更新保存が正常に行えること
     */
    #[Test]
    public function 企業情報の編集確認および更新保存が正常に行える()
    {
        $unique = uniqid();
        $company = Company::create([
            'url_code'         => 'edit-test-' . $unique,
            'name'             => '変更前株式会社',
            'abbreviated_name' => '変更前',
            'abbreviated_kana' => 'ヘンコウマエ',
            'tel'              => '03-9999-0000',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        // 1. 編集画面の表示
        $editUrl = route('admin.corp.edit', $company->id);
        $editResponse = $this->actingAs($this->adminOwner, 'admin')->get($editUrl);
        $editResponse->assertStatus(200);
        $editResponse->assertSee('変更前株式会社');

        // 2. 確認画面へのPOST
        $confirmUrl = route('admin.corp.confirm');
        $confirmData = [
            'id'               => $company->id,
            'name'             => '変更後株式会社',
            'abbreviated_name' => '変更後',
            'abbreviated_kana' => 'ヘンコウゴ',
            'url_code'         => 'edit-test-' . $unique,
            'tel'              => '03-8888-7777',
            'zip_code'         => '1000002',
            'prefecture_code'  => 13,
            'city'             => '千代田区皇居外苑',
            'address'          => '2-2',
            'grant_badge'      => '1',
            'status'           => 'active',
        ];
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')->post($confirmUrl, $confirmData);
        $confirmResponse->assertStatus(200);
        $confirmResponse->assertSee('変更後株式会社');

        // 3. 完了処理（DB保存）
        $completeUrl = route('admin.corp.complete');
        $completeResponse = $this->actingAs($this->adminOwner, 'admin')->post($completeUrl);
        $completeResponse->assertStatus(200);

        // DBに更新内容が反映されていること
        $this->assertDatabaseHas('companies', [
            'id'               => $company->id,
            'name'             => '変更後株式会社',
            'abbreviated_name' => '変更後',
            'tel'              => '03-8888-7777',
        ]);
        // バッジが付与されていること
        $this->assertDatabaseHas('company_badges', [
            'company_id' => $company->id,
            'status'     => 'active',
        ]);
    }
}
