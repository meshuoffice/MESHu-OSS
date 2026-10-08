<?php

namespace Tests\Feature\Admin;

use App\Models\Owner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @test
     * 事務局ログイン画面がシークレットURL経由で表示されること
     */
    public function 事務局ログイン画面がシークレットURLで正常に表示される()
    {
        $adminPath = env('ADMIN_URL_PATH', 'admin');

        $response = $this->get("/{$adminPath}");
        $response->assertStatus(200);
        $response->assertSee('事務局管理システム');

        $response = $this->get("/{$adminPath}/login");
        $response->assertStatus(200);
    }

    /**
     * @test
     * 正しい管理者情報でログインでき、ダッシュボードへ遷移し、ログインアラートが表示されること
     */
    public function 事務局管理者がログインに成功しダッシュボードへ遷移する()
    {
        $adminPath = env('ADMIN_URL_PATH', 'admin');

        $owner = Owner::create([
            'name'          => 'システム管理者',
            'email'         => 'admin-test@meshu.org',
            'password_hash' => Hash::make('AdminSecret123'),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        $response = $this->post("/{$adminPath}/login", [
            'email'    => 'admin-test@meshu.org',
            'password' => 'AdminSecret123',
        ]);

        $response->assertRedirect('/owntop');
        $response->assertSessionHas('success', 'ログインしました。');
        $this->assertAuthenticatedAs($owner, 'admin');

        // ダッシュボード画面へのアクセスとメッセージ確認
        $dashResponse = $this->actingAs($owner, 'admin')->get('/owntop');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('ダッシュボード');
    }

    /**
     * @test
     * 事務局ログアウト処理が正常に完了すること
     */
    public function 事務局ログアウトが正常に完了する()
    {
        $adminPath = env('ADMIN_URL_PATH', 'admin');

        $owner = Owner::create([
            'name'          => '審査管理者',
            'email'         => 'reviewer-test@meshu.org',
            'password_hash' => Hash::make('AdminSecret123'),
            'role'          => 'reviewer',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($owner, 'admin')->get('/ownlogout');
        $response->assertRedirect("/{$adminPath}/login");
        $response->assertSessionHas('success', 'ログアウトしました。');
        $this->assertGuest('admin');
    }
}
