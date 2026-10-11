<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'url_code'         => 'profile-test-corp',
            'name'             => 'プロフィール検証企業',
            'abbreviated_name' => 'プロフ検証',
            'abbreviated_kana' => 'プロフケンショウ',
            'tel'              => '03-1234-5678',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $this->user = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'profile-user@test.com',
            'password_hash'   => Hash::make('CurrentPassword123'),
            'user_name'       => '山田 太郎',
            'user_name_kana'  => 'ヤマダ タロウ',
            'department'      => '開発部',
            'position'        => 'マネージャー',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);
    }

    /**
     * プロフィール編集画面（/profile）が正常に表示されること
     */
    #[Test]
    public function プロフィール編集画面が正常に表示される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('マイプロフィール');
        $response->assertSee('山田 太郎');
        $response->assertSee('profile-user@test.com');
    }

    /**
     * プロフィール確認画面へのバリデーションが正常に機能すること
     */
    #[Test]
    public function プロフィール確認画面の入力バリデーションが正常に動作する()
    {
        // 1. 名前が未入力の場合
        $response = $this->actingAs($this->user, 'web')->post('/profile/confirm', [
            'user_name'      => '',
            'user_name_kana' => 'ヤマダ タロウ',
            'email'          => 'test@example.com',
        ]);
        $response->assertSessionHasErrors('user_name');

        // 1-2. 名前カナが未入力の場合
        $response = $this->actingAs($this->user, 'web')->post('/profile/confirm', [
            'user_name'      => '山田 太郎',
            'user_name_kana' => '',
            'email'          => 'test@example.com',
        ]);
        $response->assertSessionHasErrors('user_name_kana');

        // 2. カナに漢字が含まれる場合
        $response = $this->actingAs($this->user, 'web')->post('/profile/confirm', [
            'user_name'      => '山田 太郎',
            'user_name_kana' => '山田タロウ', // 漢字混入
            'email'          => 'test@example.com',
        ]);
        $response->assertSessionHasErrors('user_name_kana');

        // 3. 現在のパスワードが間違っている場合
        $response = $this->actingAs($this->user, 'web')->post('/profile/confirm', [
            'user_name'        => '山田 太郎',
            'user_name_kana'   => 'ヤマダ タロウ',
            'email'            => 'test@example.com',
            'current_password' => 'WrongPassword',
            'password'         => 'NewPassword123',
            'password_confirm' => 'NewPassword123',
        ]);
        $response->assertSessionHasErrors('current_password');
    }

    /**
     * プロフィール更新処理が正常に実行されDBが更新されること
     */
    #[Test]
    public function プロフィール情報が正常に更新される()
    {
        $updateData = [
            'user_name'      => '山田 次郎',
            'user_name_kana' => 'ヤマダ ジロウ',
            'email'          => 'updated-email@test.com',
            'department'     => '営業部',
            'position'       => '部長',
            'notify_trade'   => '1',
            'notify_comment' => '1',
            'notify_chat'    => '1',
        ];

        $response = $this->actingAs($this->user, 'web')
                         ->post('/profile/update', $updateData);

        $response->assertRedirect('/profile/complete');

        $this->assertDatabaseHas('users', [
            'id'             => $this->user->id,
            'user_name'      => '山田 次郎',
            'email'          => 'updated-email@test.com',
            'department'     => '営業部',
            'position'       => '部長',
            'notify_trade'   => true,
            'notify_comment' => true,
            'notify_chat'    => true,
        ]);
    }
}
