<?php

namespace Tests\Feature\Admin;

use App\Models\Notice;
use App\Models\NoticeAttachment;
use App\Models\Owner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminNoticeManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Owner $adminOwner;
    protected Owner $reviewerOwner;
    protected Owner $viewerOwner;

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

        $this->viewerOwner = Owner::firstOrCreate(
            ['email' => 'viewer-staff@meshu.org'],
            [
                'name'          => '閲覧スタッフ',
                'password_hash' => Hash::make('ViewerPass123'),
                'role'          => 'viewer',
                'status'        => 'active',
            ]
        );
    }

    #[Test]
    public function お知らせ一覧が表示されキーワードやステータスで絞り込みできる()
    {
        $unique = uniqid();
        $notice1 = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'important',
            'target_scope'        => 'all',
            'title'               => '重要メンテ告知_' . $unique,
            'content'             => 'システムメンテを実施します。',
            'is_important'        => true,
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        $notice2 = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'verified',
            'title'               => '下書き一般告知_' . $unique,
            'content'             => '認証企業向けの下書きです。',
            'is_important'        => false,
            'is_published'        => false,
            'published_at'        => now(),
        ]);

        // キーワード検索
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.index', ['keyword' => '重要メンテ告知_' . $unique]));
        $response->assertStatus(200);
        $response->assertSee('重要メンテ告知_' . $unique);
        $response->assertDontSee('下書き一般告知_' . $unique);

        // カテゴリ・ステータス絞り込み
        $responseDraft = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.index', ['status' => 'draft']));
        $responseDraft->assertStatus(200);
        $responseDraft->assertSee('下書き一般告知_' . $unique);
    }

    #[Test]
    public function 事務局管理者は新規お知らせを添付ファイル付きで登録できる()
    {
        $unique = uniqid();
        $file = UploadedFile::fake()->create('manual_' . $unique . '.pdf', 100, 'application/pdf');

        // 1. 確認画面へのPOST
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.notice.confirm'), [
                'title'           => '新機能リリースのお知らせ_' . $unique,
                'category'        => 'info',
                'target_scope'    => 'all',
                'content'         => '新しい機能が追加されました。詳細は添付マニュアルをご覧ください。',
                'published_at'    => now()->format('Y-m-d\TH:i'),
                'is_important'    => '1',
                'status'          => 'published',
                'attachment_file' => $file,
            ]);

        $confirmResponse->assertStatus(200);
        $confirmResponse->assertSee('新機能リリースのお知らせ_' . $unique);
        $confirmResponse->assertSee('manual_' . $unique . '.pdf');

        $sessionData = [
            'title'        => '新機能リリースのお知らせ_' . $unique,
            'category'     => 'info',
            'target_scope' => 'all',
            'content'      => '新しい機能が追加されました。詳細は添付マニュアルをご覧ください。',
            'is_important' => true,
            'is_published' => true,
            'published_at' => now()->format('Y-m-d H:i:s'),
            'attachment'   => [
                'file_name' => 'manual_' . $unique . '.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => 100,
                'data_b64'  => base64_encode('dummy pdf content'),
            ],
        ];

        // 2. 完了処理へのPOST
        $completeResponse = $this->actingAs($this->adminOwner, 'admin')
            ->withSession(['ownnotice_confirm_data' => $sessionData])
            ->post(route('admin.notice.complete'));

        $savedNotice = Notice::where('title', '新機能リリースのお知らせ_' . $unique)->first();
        $this->assertNotNull($savedNotice);
        $completeResponse->assertRedirect(route('admin.notice.complete.show', ['id' => $savedNotice->id]));
        
        // DB登録確認
        $this->assertDatabaseHas('notices', [
            'id'           => $savedNotice->id,
            'title'        => '新機能リリースのお知らせ_' . $unique,
            'category'     => 'info',
            'is_important' => true,
            'is_published' => true,
        ]);

        $this->assertDatabaseHas('notice_attachments', [
            'notice_id' => $savedNotice->id,
            'file_name' => 'manual_' . $unique . '.pdf',
        ]);
    }

    #[Test]
    public function お知らせ作成時のバリデーションが機能する()
    {
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.notice.confirm'), [
                'title'        => '', // 必須違反
                'category'     => 'invalid_category',
                'target_scope' => 'invalid_scope',
                'content'      => '',
                'published_at' => '',
                'status'       => '',
            ]);

        $response->assertSessionHasErrors(['title', 'category', 'target_scope', 'content', 'published_at', 'status']);
    }

    #[Test]
    public function お知らせ詳細画面が表示され添付ファイルをダウンロードできる()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '詳細確認テスト_' . $unique,
            'content'             => '本文内容テスト',
            'is_important'        => false,
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        $attachment = NoticeAttachment::create([
            'notice_id'  => $notice->id,
            'file_name'  => 'test_' . $unique . '.txt',
            'mime_type'  => 'text/plain',
            'file_size'  => 12,
            'file_data'  => 'Hello Notice',
            'created_at' => now(),
        ]);

        // 詳細画面
        $response = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.show', $notice->id));
        $response->assertStatus(200);
        $response->assertSee('詳細確認テスト_' . $unique);
        $response->assertSee('test_' . $unique . '.txt');

        // ダウンロード
        $downloadResponse = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.download_attachment', $attachment->id));
        $downloadResponse->assertStatus(200);
        $downloadResponse->assertHeader('Content-Disposition', 'attachment; filename="test_' . $unique . '.txt"');
        $this->assertSame('Hello Notice', $downloadResponse->getContent());
    }

    #[Test]
    public function お知らせの編集および添付ファイルの削除・差し替えができる()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '編集前タイトル_' . $unique,
            'content'             => '編集前本文',
            'is_important'        => false,
            'is_published'        => false,
            'published_at'        => now(),
        ]);

        NoticeAttachment::create([
            'notice_id'  => $notice->id,
            'file_name'  => 'old_' . $unique . '.txt',
            'mime_type'  => 'text/plain',
            'file_size'  => 10,
            'file_data'  => 'Old Data',
            'created_at' => now(),
        ]);

        // 1. 編集確認（添付ファイル削除指定）
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.notice.confirm_update', $notice->id), [
                'title'             => '編集後タイトル_' . $unique,
                'category'          => 'important',
                'target_scope'      => 'verified',
                'content'           => '更新された本文です。',
                'published_at'      => now()->format('Y-m-d\TH:i'),
                'is_important'      => '1',
                'status'            => 'published',
                'delete_attachment' => '1',
            ]);
        $confirmResponse->assertStatus(200);
        $confirmResponse->assertSee('編集後タイトル_' . $unique);
        $confirmResponse->assertSee('添付ファイルを削除');

        $sessionEditData = [
            'title'             => '編集後タイトル_' . $unique,
            'category'          => 'important',
            'target_scope'      => 'verified',
            'content'           => '更新された本文です。',
            'is_important'      => true,
            'is_published'      => true,
            'published_at'      => now()->format('Y-m-d H:i:s'),
            'attachment'        => null,
            'delete_attachment' => true,
            'is_new_attachment' => false,
        ];

        // 2. 更新確定
        $updateResponse = $this->actingAs($this->adminOwner, 'admin')
            ->withSession(['ownnotice_edit_confirm_data_' . $notice->id => $sessionEditData])
            ->post(route('admin.notice.update', $notice->id));
        $updateResponse->assertRedirect(route('admin.notice.complete.show', ['id' => $notice->id, 'mode' => 'edit']));

        // DB更新確認
        $notice->refresh();
        $this->assertSame('編集後タイトル_' . $unique, $notice->title);
        $this->assertSame('important', $notice->category);
        $this->assertTrue($notice->is_important);
        $this->assertTrue($notice->is_published);
        $this->assertDatabaseMissing('notice_attachments', ['notice_id' => $notice->id]);
    }

    #[Test]
    public function 公開中のお知らせの削除は安全仕様によりガードされる()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '公開中削除ガードテスト_' . $unique,
            'content'             => '本文',
            'is_important'        => false,
            'is_published'        => true, // 公開中
            'published_at'        => now(),
        ]);

        // 削除確認画面へのアクセス拒否
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.delete.confirm', $notice->id));
        $confirmResponse->assertRedirect(route('admin.notice.show', $notice->id));
        $confirmResponse->assertSessionHas('error', '公開中のお知らせは削除できません。先にステータスを「下書き（非公開）」に変更してください。');

        // 削除実行へのアクセス拒否
        $deleteResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.notice.delete', $notice->id));
        $deleteResponse->assertRedirect(route('admin.notice.show', $notice->id));
        $deleteResponse->assertSessionHas('error');

        // レコードが削除されていないこと
        $this->assertDatabaseHas('notices', ['id' => $notice->id]);
    }

    #[Test]
    public function 下書きのお知らせを正常に削除できる()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '下書き削除可能テスト_' . $unique,
            'content'             => '本文',
            'is_important'        => false,
            'is_published'        => false, // 下書き
            'published_at'        => now(),
        ]);

        // 削除確認画面へのアクセス成功
        $confirmResponse = $this->actingAs($this->adminOwner, 'admin')
            ->get(route('admin.notice.delete.confirm', $notice->id));
        $confirmResponse->assertStatus(200);
        $confirmResponse->assertSee('下書き削除可能テスト_' . $unique);

        // 削除実行
        $deleteResponse = $this->actingAs($this->adminOwner, 'admin')
            ->post(route('admin.notice.delete', $notice->id));
        $deleteResponse->assertRedirect(route('admin.notice.index'));
        $deleteResponse->assertSessionHas('success', 'お知らせを削除しました。');

        // レコードが削除されたこと
        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
    }

    #[Test]
    public function 閲覧専用ユーザーは新規作成や編集や削除を実行できず拒否される()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '閲覧専用アクセス制御テスト_' . $unique,
            'content'             => '本文',
            'is_important'        => false,
            'is_published'        => false,
            'published_at'        => now(),
        ]);

        // 新規作成画面へのアクセス拒否
        $createResponse = $this->actingAs($this->viewerOwner, 'admin')
            ->get(route('admin.notice.registration'));
        $createResponse->assertRedirect(route('admin.notice.index'));
        $createResponse->assertSessionHas('error');

        // 編集画面へのアクセス拒否
        $editResponse = $this->actingAs($this->viewerOwner, 'admin')
            ->get(route('admin.notice.edit', $notice->id));
        $editResponse->assertRedirect(route('admin.notice.index'));
        $editResponse->assertSessionHas('error');

        // 削除実行へのアクセス拒否
        $deleteResponse = $this->actingAs($this->viewerOwner, 'admin')
            ->post(route('admin.notice.delete', $notice->id));
        $deleteResponse->assertRedirect(route('admin.notice.index'));
        $deleteResponse->assertSessionHas('error');
    }

    #[Test]
    public function 審査担当者は新規作成や編集や削除を実行できる()
    {
        $unique = uniqid();
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->reviewerOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '審査担当者作成可能テスト_' . $unique,
            'content'             => '本文',
            'is_important'        => false,
            'is_published'        => false,
            'published_at'        => now(),
        ]);

        // 新規作成画面アクセス可能
        $createResponse = $this->actingAs($this->reviewerOwner, 'admin')
            ->get(route('admin.notice.registration'));
        $createResponse->assertStatus(200);

        // 編集画面アクセス可能
        $editResponse = $this->actingAs($this->reviewerOwner, 'admin')
            ->get(route('admin.notice.edit', $notice->id));
        $editResponse->assertStatus(200);

        // 下書き削除可能
        $deleteResponse = $this->actingAs($this->reviewerOwner, 'admin')
            ->post(route('admin.notice.delete', $notice->id));
        $deleteResponse->assertRedirect(route('admin.notice.index'));
        $deleteResponse->assertSessionHas('success');
    }
}
