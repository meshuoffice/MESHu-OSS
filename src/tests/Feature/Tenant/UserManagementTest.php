<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserManagementTest extends TestCase
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

        // テスト企業A
        $this->companyA = Company::create([
            'url_code'         => 'company-a',
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

        // テスト企業B (他社)
        $this->companyB = Company::create([
            'url_code'         => 'company-b',
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

        // 企業Aの管理者 (role=admin)
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => 'admin-a@example.com',
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

        // 企業Aの一般スタッフ (role=staff)
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => 'staff-a@example.com',
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

        // 企業Bの管理者 (他社管理者)
        $this->adminUserB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => 'admin-b@example.com',
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
    }

    #[Test]
    public function 一般社員_staff_は従業員管理画面にアクセスできない_403遮断(): void
    {
        $response = $this->actingAs($this->staffUserA, 'web')
            ->get('/users');

        $response->assertStatus(403);
    }

    #[Test]
    public function 管理者は自社の従業員一覧画面を表示できる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get('/users');

        $response->assertStatus(200);
        $response->assertSee('従業員管理');
        $response->assertSee('企業A スタッフ');
        $response->assertSee('staff-a@example.com');
        $response->assertSee('最終ログイン');
    }

    #[Test]
    public function 管理者は新規従業員登録画面を表示できる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get('/users/new');

        $response->assertStatus(200);
        $response->assertSee('新規従業員の登録');
    }

    #[Test]
    public function 新規登録確認画面でバリデーションが正しく機能する(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->post('/users/confirm-create', [
                'user_name'       => '',
                'user_name_kana'  => 'invalid-kana',
                'email'           => 'not-an-email',
                'role'            => 'unknown',
                'status'          => 'unknown',
                'is_all_assigned' => '9',
            ]);

        $response->assertSessionHasErrors([
            'user_name',
            'user_name_kana',
            'email',
            'role',
            'status',
            'is_all_assigned',
        ]);
    }

    #[Test]
    public function ドメイン形式不備のメールアドレス_aaa_at_bb_はバリデーションで弾かれる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->post('/users/confirm-create', [
                'user_name'       => '山田 太郎',
                'user_name_kana'  => 'ヤマダ タロウ',
                'email'           => 'aaa@bb',
                'role'            => 'staff',
                'status'          => 'active',
                'is_all_assigned' => '1',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    #[Test]
    public function 管理者は新規従業員を登録できる(): void
    {
        $postData = [
            'user_name'       => '新規 太郎',
            'user_name_kana'  => 'シンキ タロウ',
            'email'           => 'new-user@example.com',
            'department'      => '営業部',
            'position'        => '課長',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => '1',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post('/users/create', $postData);

        $response->assertRedirect('/users/complete');

        $this->assertDatabaseHas('users', [
            'company_id'     => $this->companyA->id,
            'email'          => 'new-user@example.com',
            'user_name'      => '新規 太郎',
            'user_name_kana' => 'シンキ タロウ',
            'department'     => '営業部',
            'position'       => '課長',
            'role'           => 'staff',
            'status'         => 'active',
            'is_all_assigned'=> 1,
        ]);

        // 初期パスワード通知メールが送信されること
        Mail::assertSent(\App\Mail\UserInitialPasswordMail::class, function ($mail) {
            return $mail->hasTo('new-user@example.com')
                && $mail->user->user_name === '新規 太郎';
        });
    }

    #[Test]
    public function 管理者権限を選択した場合はis_all_assignedが自動的に1となる(): void
    {
        $postData = [
            'user_name'       => 'サブ 管理者',
            'user_name_kana'  => 'サブ カンリシャ',
            'email'           => 'sub-admin@example.com',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => '0',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post('/users/create', $postData);

        $response->assertRedirect('/users/complete');

        $this->assertDatabaseHas('users', [
            'company_id'     => $this->companyA->id,
            'email'          => 'sub-admin@example.com',
            'role'           => 'admin',
            'is_all_assigned'=> 1,
        ]);
    }

    #[Test]
    public function 自社従業員の詳細画面を表示できる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/users/show/{$this->staffUserA->id}");

        $response->assertStatus(200);
        $response->assertSee('従業員詳細');
        $response->assertSee($this->staffUserA->user_name);
        $response->assertSee('最終ログイン日時');
        $response->assertSee('未ログイン');
    }

    #[Test]
    public function ログイン済みの従業員詳細画面で最終ログイン日時が表示される(): void
    {
        $this->staffUserA->update(['last_login_at' => now()->subDay()]);

        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/users/show/{$this->staffUserA->id}");

        $response->assertStatus(200);
        $response->assertSee('最終ログイン日時');
        $response->assertSee($this->staffUserA->fresh()->last_login_at->format('Y-m-d'));
    }

    #[Test]
    public function 他社従業員の詳細閲覧は404遮断される(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/users/show/{$this->adminUserB->id}");

        $response->assertStatus(404);
    }

    #[Test]
    public function 自社従業員情報を更新できる(): void
    {
        $updateData = [
            'user_name'       => 'スタッフ 変更後',
            'user_name_kana'  => 'スタッフ ヘンコウゴ',
            'email'           => 'staff-updated@example.com',
            'department'      => '総務部',
            'position'        => '主任',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => '0',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/users/update/{$this->staffUserA->id}", $updateData);

        $response->assertRedirect('/users/complete');

        $this->assertDatabaseHas('users', [
            'id'             => $this->staffUserA->id,
            'user_name'      => 'スタッフ 変更後',
            'email'          => 'staff-updated@example.com',
            'department'     => '総務部',
        ]);
    }

    #[Test]
    public function パスワード再発行を実行すると仮パスワードが更新される(): void
    {
        $oldHash = $this->staffUserA->password_hash;

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/users/send-password/{$this->staffUserA->id}", [
                'email' => 'staff-a@example.com',
            ]);

        $response->assertStatus(200);
        $response->assertSee('パスワード再発行完了');

        $this->staffUserA->refresh();
        $this->assertNotEquals($oldHash, $this->staffUserA->password_hash);

        // 仮パスワード再発行通知メールが送信されること
        Mail::assertSent(\App\Mail\UserResetPasswordMail::class, function ($mail) {
            return $mail->hasTo('staff-a@example.com');
        });
    }

    #[Test]
    public function 停止中_suspended_のアカウントは物理削除できる(): void
    {
        $suspendedUser = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => 'suspended@example.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '停止 ユーザー',
            'user_name_kana'  => 'テイシ ユーザー',
            'role'            => 'staff',
            'status'          => 'suspended',
            'is_all_assigned' => 1,
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/users/delete/{$suspendedUser->id}");

        $response->assertRedirect('/users/complete');

        $this->assertDatabaseMissing('users', [
            'id' => $suspendedUser->id,
        ]);
    }

    #[Test]
    public function 有効_active_のアカウントは削除できない(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/users/delete/{$this->staffUserA->id}");

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'id' => $this->staffUserA->id,
        ]);
    }

    #[Test]
    public function 存在しない旧URLは404となる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/{$this->companyA->url_code}/settings/users");

        $response->assertStatus(404);
    }
}
