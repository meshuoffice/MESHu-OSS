<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantAuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 審査中（suspended）の企業ログイン画面にアクセスすると404エラーになること
     */
    #[Test]
    public function 審査中の企業ログイン画面にアクセスすると404エラーになる()
    {
        $company = Company::create([
            'url_code'         => 'suspended-corp',
            'name'             => '審査待ち企業株式会社',
            'abbreviated_name' => '審査待ち',
            'abbreviated_kana' => 'シンサマチ',
            'tel'              => '03-1111-2222',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'suspended',
        ]);

        $response = $this->get('/suspended-corp/login');
        $response->assertStatus(404);
    }

    /**
     * 稼働中（active）の企業ログイン画面が正常に表示されること
     */
    #[Test]
    public function 稼働中の企業ログイン画面が正常に表示される()
    {
        $company = Company::create([
            'url_code'         => 'active-corp',
            'name'             => '稼働中企業株式会社',
            'abbreviated_name' => '稼働中',
            'abbreviated_kana' => 'カドウチュウ',
            'tel'              => '03-1111-3333',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-2',
            'status'           => 'active',
        ]);

        $response = $this->get('/active-corp/login');
        $response->assertStatus(200);
        $response->assertSee('稼働中企業株式会社');
    }

    /**
     * 正しいメールアドレスとパスワードでログインでき、ダッシュボードへ遷移すること
     */
    #[Test]
    public function 正しい認証情報でログインに成功しダッシュボードへリダイレクトされる()
    {
        $company = Company::create([
            'url_code'         => 'login-test-corp',
            'name'             => 'テスト株式会社',
            'abbreviated_name' => 'テスト',
            'abbreviated_kana' => 'テスト',
            'tel'              => '03-1111-4444',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-3',
            'status'           => 'active',
        ]);

        $user = User::create([
            'company_id'      => $company->id,
            'email'           => 'user@test-corp.com',
            'password_hash'   => Hash::make('CorrectPassword123'),
            'user_name'       => '山田 太郎',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $response = $this->post('/login-test-corp/login', [
            'email'    => 'user@test-corp.com',
            'password' => 'CorrectPassword123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user, 'web');
    }

    /**
     * パスワードが誤っている場合はログインに失敗しエラーメッセージが返ること
     */
    #[Test]
    public function 誤ったパスワードの場合はログインに失敗する()
    {
        $company = Company::create([
            'url_code'         => 'fail-test-corp',
            'name'             => '失敗テスト株式会社',
            'abbreviated_name' => '失敗テスト',
            'abbreviated_kana' => 'シッパイテスト',
            'tel'              => '03-1111-5555',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-4',
            'status'           => 'active',
        ]);

        User::create([
            'company_id'      => $company->id,
            'email'           => 'user@fail-corp.com',
            'password_hash'   => Hash::make('CorrectPassword123'),
            'user_name'       => '山田 次郎',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $response = $this->from('/fail-test-corp/login')->post('/fail-test-corp/login', [
            'email'    => 'user@fail-corp.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertRedirect('/fail-test-corp/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    /**
     * ログアウト処理が正常に完了し、企業ログイン画面またはトップへ遷移すること
     */
    #[Test]
    public function ログアウト処理が正常に動作する()
    {
        $company = Company::create([
            'url_code'         => 'logout-test-corp',
            'name'             => 'ログアウトテスト株式会社',
            'abbreviated_name' => 'ログアウト',
            'abbreviated_kana' => 'ログアウト',
            'tel'              => '03-1111-6666',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-5',
            'status'           => 'active',
        ]);

        $user = User::create([
            'company_id'      => $company->id,
            'email'           => 'logout@test-corp.com',
            'password_hash'   => Hash::make('CorrectPassword123'),
            'user_name'       => '山田 三郎',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 0,
        ]);

        $response = $this->actingAs($user, 'web')->post('/logout');
        $response->assertRedirect('/logout-test-corp/login');
        $this->assertGuest('web');
    }
}
