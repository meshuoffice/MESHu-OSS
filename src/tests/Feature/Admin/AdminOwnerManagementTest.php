<?php

namespace Tests\Feature\Admin;

use App\Models\Owner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminOwnerManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Owner $adminOwner;
    protected Owner $reviewerOwner;

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

        $this->reviewerOwner = Owner::firstOrCreate(
            ['email' => 'reviewer-staff@meshu.org'],
            [
                'name'          => '審査担当スタッフ',
                'password_hash' => Hash::make('ReviewerPass123'),
                'role'          => 'reviewer',
                'status'        => 'active',
            ]
        );
    }

    #[Test]
    public function 最高管理者は事務局アカウント一覧を表示・検索できる()
    {
        $unique = uniqid();
        Owner::create([
            'name'          => '検索対象オペレータ_' . $unique,
            'email'         => 'search-target-' . $unique . '@meshu.org',
            'password_hash' => Hash::make('Pass12345'),
            'role'          => 'reviewer',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.owner.index', ['keyword' => '検索対象オペレータ_' . $unique]));

        $response->assertStatus(200);
        $response->assertSee('検索対象オペレータ_' . $unique);
    }

    #[Test]
    public function 審査担当者は事務局アカウント一覧へアクセスできずダッシュボードへリダイレクトされる()
    {
        $response = $this->actingAs($this->reviewerOwner, 'admin')
            ->get(route('admin.owner.index'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error', '他の事務局アカウント情報を閲覧・操作する権限がありません。');
    }

    #[Test]
    public function 最高管理者は新規事務局アカウントを登録できる()
    {
        $unique = uniqid();
        $email = 'new-owner-' . $unique . '@meshu.org';

        // 確認画面
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.confirm'), [
                'name'     => '新規作成管理者_' . $unique,
                'email'    => $email,
                'password' => 'NewOwnerPass123',
                'role'     => 'reviewer',
                'status'   => 'active',
            ]);

        $confirmResponse->assertStatus(200);
        $confirmResponse->assertSee('新規作成管理者_' . $unique);

        // 完了処理
        $completeResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.complete'), [
                'name'     => '新規作成管理者_' . $unique,
                'email'    => $email,
                'password' => 'NewOwnerPass123',
                'role'     => 'reviewer',
                'status'   => 'active',
            ]);

        $completeResponse->assertStatus(200);
        $completeResponse->assertSee('事務局アカウントの登録が完了しました');

        $this->assertDatabaseHas('owners', [
            'email'  => $email,
            'role'   => 'reviewer',
            'status' => 'active',
        ]);
    }

    #[Test]
    public function 最高管理者および本人はアカウント詳細画面を閲覧できる()
    {
        // 最高管理者が審査担当者の詳細を表示
        $response1 = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.owner.show', $this->reviewerOwner->id));
        $response1->assertStatus(200);
        $response1->assertSee($this->reviewerOwner->name);

        // 審査担当者自身が自身の詳細を表示
        $response2 = $this->actingAs($this->reviewerOwner, 'admin')
            ->get(route('admin.owner.show', $this->reviewerOwner->id));
        $response2->assertStatus(200);
        $response2->assertSee($this->reviewerOwner->name);
    }

    #[Test]
    public function 審査担当者は他人のアカウント詳細画面を閲覧できず遮断される()
    {
        $response = $this->actingAs($this->reviewerOwner, 'admin')
            ->get(route('admin.owner.show', $this->adminOwner->id));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error', '他の事務局アカウント情報を閲覧・操作する権限がありません。');
    }

    #[Test]
    public function 最高管理者は他人のアカウントおよび権限ロールを更新できる()
    {
        $unique = uniqid();
        $target = Owner::create([
            'name'          => '更新対象_' . $unique,
            'email'         => 'update-target-' . $unique . '@meshu.org',
            'password_hash' => Hash::make('Pass12345'),
            'role'          => 'viewer',
            'status'        => 'suspended',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.update', $target->id), [
                'id'       => $target->id,
                'name'     => '更新後氏名_' . $unique,
                'email'    => 'updated-' . $unique . '@meshu.org',
                'role'     => 'reviewer',
                'status'   => 'active',
                'password' => 'NewPassword888',
            ]);

        $response->assertStatus(200);
        $response->assertSee('アカウント情報を更新しました');

        $this->assertDatabaseHas('owners', [
            'id'     => $target->id,
            'name'   => '更新後氏名_' . $unique,
            'email'  => 'updated-' . $unique . '@meshu.org',
            'role'   => 'reviewer',
            'status' => 'active',
        ]);

        $target->refresh();
        $this->assertTrue(Hash::check('NewPassword888', $target->password_hash));
    }

    #[Test]
    public function 一般管理者は自身のプロフィールやパスワードを更新できるが自身の権限ロールは変更されない()
    {
        $response = $this->actingAs($this->reviewerOwner, 'admin')
            ->post(route('admin.owner.update', $this->reviewerOwner->id), [
                'id'       => $this->reviewerOwner->id,
                'name'     => '審査担当スタッフ改名',
                'email'    => $this->reviewerOwner->email,
                'role'     => 'admin', // 自身でadminに昇格しようとする
                'status'   => 'suspended', // 自身でsuspendedにしようとする
                'password' => 'ReviewerNewPass99',
            ]);

        $response->assertStatus(200);

        $this->reviewerOwner->refresh();
        $this->assertSame('審査担当スタッフ改名', $this->reviewerOwner->name);
        $this->assertSame('reviewer', $this->reviewerOwner->role); // ロール変更は無効
        $this->assertSame('active', $this->reviewerOwner->status);   // 自身の停止も無効
        $this->assertTrue(Hash::check('ReviewerNewPass99', $this->reviewerOwner->password_hash));
    }

    #[Test]
    public function ログイン中自身のアカウントの停止・削除はガードされる()
    {
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.delete', $this->adminOwner->id), [
                'status' => 'deleted',
            ]);

        $response->assertRedirect(route('admin.owner.index'));
        $response->assertSessionHas('error', '現在ログイン中の自分自身のアカウントを削除・停止することはできません。');
    }

    #[Test]
    public function 稼働中アカウントの直接削除はガードされる()
    {
        $unique = uniqid();
        $target = Owner::create([
            'name'          => '稼働中削除対象_' . $unique,
            'email'         => 'active-del-' . $unique . '@meshu.org',
            'password_hash' => Hash::make('Pass12345'),
            'role'          => 'reviewer',
            'status'        => 'active',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.delete', $target->id), [
                'status' => 'deleted',
            ]);

        $response->assertSessionHas('error', '稼働中のアカウントは直接削除できません。一旦アカウントを「一時停止」にしてから削除を実行してください。');

        $target->refresh();
        $this->assertSame('active', $target->status);
    }

    #[Test]
    public function 一時停止中のアカウントを正常に削除状態に変更できる()
    {
        $unique = uniqid();
        $target = Owner::create([
            'name'          => '停止中削除対象_' . $unique,
            'email'         => 'suspended-del-' . $unique . '@meshu.org',
            'password_hash' => Hash::make('Pass12345'),
            'role'          => 'reviewer',
            'status'        => 'suspended',
        ]);

        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.owner.delete', $target->id), [
                'status' => 'deleted',
            ]);

        $response->assertRedirect(route('admin.owner.index'));
        $response->assertSessionHas('success', '事務局アカウントを削除状態に変更しました。');

        $target->refresh();
        $this->assertSame('deleted', $target->status);
    }
}
