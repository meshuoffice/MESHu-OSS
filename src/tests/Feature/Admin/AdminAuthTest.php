<?php

namespace Tests\Feature\Admin;

use App\Models\Owner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 事務局ログイン画面がシークレットURL経由で表示されること
     */
    #[Test]
    public function 事務局ログイン画面がシークレットURLで正常に表示される()
    {
        $response = $this->get(route('admin.login.root'));
        $response->assertStatus(200);
        $response->assertSee('事務局管理システム');

        $response = $this->get(route('admin.login'));
        $response->assertStatus(200);
    }

    /**
     * 正しい管理者情報でログインでき、ダッシュボードへ遷移すること
     */
    #[Test]
    public function 事務局管理者がログインに成功しダッシュボードへ遷移する()
    {
        $owner = Owner::create([
            'name'          => 'システム管理者',
            'email'         => 'admin-test@meshu.org',
            'password_hash' => Hash::make('AdminSecret123'),
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'email'    => 'admin-test@meshu.org',
            'password' => 'AdminSecret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($owner, 'admin');

        // ダッシュボード画面へのアクセスとメッセージ確認
        $dashResponse = $this->actingAs($owner, 'admin')->get(route('admin.dashboard'));
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('ダッシュボード');
    }

    /**
     * 事務局ログアウト処理が正常に完了すること
     */
    #[Test]
    public function 事務局ログアウトが正常に完了する()
    {
        $owner = Owner::create([
            'name'          => '審査管理者',
            'email'         => 'reviewer-test@meshu.org',
            'password_hash' => Hash::make('AdminSecret123'),
            'role'          => 'reviewer',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($owner, 'admin')->get(route('admin.ownlogout'));
        $response->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
    }
}
