<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'url_code'         => 'settings-test-corp',
            'name'             => '設定検証企業',
            'abbreviated_name' => '設定検証',
            'abbreviated_kana' => 'セッテイケンショウ',
            'tel'              => '03-5555-6666',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);
    }

    /**
     * 管理者（role=admin）は各種設定ポータル（/settings）にアクセスできること
     */
    #[Test]
    public function 管理者は設定ポータルにアクセスできる()
    {
        $adminUser = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'admin@settings-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '設定 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        $response = $this->actingAs($adminUser, 'web')->get('/settings');

        $response->assertStatus(200);
        $response->assertSee('各種設定');
        $response->assertSee('企業情報メンテナンス');
        $response->assertSee('従業員管理');
    }

    /**
     * 一般社員（role=staff）が各種設定ポータル（/settings）にアクセスした場合はダッシュボードにリダイレクトされ、エラーメッセージが表示されること
     */
    #[Test]
    public function 一般社員が設定ポータルにアクセスした場合はダッシュボードへリダイレクトされる()
    {
        $staffUser = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'staff@settings-corp.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '一般 社員',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        $response = $this->actingAs($staffUser, 'web')->get('/settings');

        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error', 'このページへアクセスする権限がありません。（管理者専用機能）');
    }
}
