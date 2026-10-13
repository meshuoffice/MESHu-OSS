<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeManageTest extends TestCase
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

        $suffix = uniqid();

        // 自社企業A
        $this->companyA = Company::create([
            'url_code'         => "noticemng-a-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => '自社企業A株式会社',
            'abbreviated_name' => '企業A',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        // 他社企業B
        $this->companyB = Company::create([
            'url_code'         => "noticemng-b-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => '他社企業B株式会社',
            'abbreviated_name' => '企業B',
            'tel'              => '03-3333-4444',
            'zip_code'         => '100-0002',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        // 企業A 管理者
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "admin-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業A管理者',
            'role'            => 'admin',
            'is_all_assigned' => true,
            'status'          => 'active',
        ]);

        // 企業A 一般スタッフ
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業Aスタッフ',
            'role'            => 'staff',
            'is_all_assigned' => false,
            'status'          => 'active',
        ]);

        // 企業B 管理者
        $this->adminUserB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => "admin-b-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '企業B管理者',
            'role'            => 'admin',
            'is_all_assigned' => true,
            'status'          => 'active',
        ]);
    }

    #[Test]
    public function test_unauthenticated_user_cannot_access_notice_manage(): void
    {
        $response = $this->get('/notice/manage');
        $response->assertRedirect();
    }

    #[Test]
    public function test_staff_user_cannot_access_notice_manage(): void
    {
        $response = $this->actingAs($this->staffUserA, 'web')->get('/notice/manage');
        $response->assertStatus(403);
    }

    #[Test]
    public function test_admin_user_can_view_notice_manage_list(): void
    {
        // 企業Aのお知らせ
        $noticeA = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '自社発信のお知らせA',
            'content'             => 'お知らせA本文',
            'is_important'        => false,
            'is_published'        => true,
            'view_count'          => 12,
            'published_at'        => now(),
        ]);

        // 企業Bのお知らせ（他社データ）
        $noticeB = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyB->id,
            'created_by_user_id'  => $this->adminUserB->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '他社企業Bのお知らせ',
            'content'             => 'お知らせB本文',
            'is_important'        => false,
            'is_published'        => true,
            'view_count'          => 5,
            'published_at'        => now(),
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get('/notice/manage');

        $response->assertStatus(200);
        $response->assertSee('お知らせ配信管理');
        $response->assertSee('自社発信のお知らせA');
        $response->assertDontSee('他社企業Bのお知らせ');
    }

    #[Test]
    public function test_notice_manage_filter_by_status(): void
    {
        // 公開中お知らせ
        Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '公開中のお知らせタイトル',
            'content'             => '公開中本文',
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        // 下書きお知らせ
        Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '下書きのお知らせタイトル',
            'content'             => '下書き本文',
            'is_published'        => false,
            'published_at'        => null,
        ]);

        // status=published
        $pubRes = $this->actingAs($this->adminUserA, 'web')->get('/notice/manage?status=published');
        $pubRes->assertStatus(200);
        $pubRes->assertSee('公開中のお知らせタイトル');
        $pubRes->assertDontSee('下書きのお知らせタイトル');

        // status=draft
        $draftRes = $this->actingAs($this->adminUserA, 'web')->get('/notice/manage?status=draft');
        $draftRes->assertStatus(200);
        $draftRes->assertSee('下書きのお知らせタイトル');
        $draftRes->assertDontSee('公開中のお知らせタイトル');
    }

    #[Test]
    public function test_admin_user_can_view_new_notice_form(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->get('/notice/manage/new');

        $response->assertStatus(200);
        $response->assertSee('新規お知らせ作成');
        $response->assertSee('配信お知らせ情報入力');
    }

    #[Test]
    public function test_confirm_creates_temp_attachment_in_session(): void
    {
        $fakeFile = UploadedFile::fake()->create('sample.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->adminUserA, 'web')->post('/notice/manage/confirm', [
            'title'        => '確認画面テストタイトル',
            'content'      => '確認画面テスト本文',
            'is_published' => '1',
            'attachment'   => $fakeFile,
        ]);

        $response->assertStatus(200);
        $response->assertSee('お知らせ内容確認');
        $response->assertSee('確認画面テストタイトル');
        $response->assertSee('sample.pdf');
        $response->assertSessionHas('temp_notice_manage_attachment');
    }

    #[Test]
    public function test_create_notice_with_attachment_and_publish(): void
    {
        // 事前にセッションに添付ファイルをセット
        session([
            'temp_notice_manage_attachment' => [
                'name' => 'test_doc.pdf',
                'mime' => 'application/pdf',
                'size' => 1024,
                'data' => base64_encode('fake binary data'),
            ],
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->post('/notice/manage/create', [
            'title'        => '新規公開お知らせ',
            'content'      => '新規公開お知らせの本文内容です。',
            'is_published' => '1',
        ]);

        $notice = Notice::where('company_id', $this->companyA->id)
            ->where('title', '新規公開お知らせ')
            ->first();

        $this->assertNotNull($notice);
        $this->assertTrue($notice->is_published);
        $this->assertNotNull($notice->published_at);
        $this->assertEquals($this->adminUserA->id, $notice->created_by_user_id);

        // 添付ファイルが保存されていること
        $this->assertDatabaseHas('notice_attachments', [
            'notice_id' => $notice->id,
            'file_name' => 'test_doc.pdf',
        ]);

        // 一時セッションが消去されていること
        $this->assertNull(session('temp_notice_manage_attachment'));

        $response->assertRedirect(route('tenant.notice.manage.complete', [
            'id'   => $notice->id,
            'mode' => 'create',
        ]));
    }

    #[Test]
    public function test_create_notice_as_draft(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')->post('/notice/manage/create', [
            'title'        => '下書き作成テスト',
            'content'      => '下書きの本文です。',
            'is_published' => '0',
        ]);

        $notice = Notice::where('company_id', $this->companyA->id)
            ->where('title', '下書き作成テスト')
            ->first();

        $this->assertNotNull($notice);
        $this->assertFalse($notice->is_published);
        $this->assertNull($notice->published_at);
    }

    #[Test]
    public function test_admin_can_view_notice_detail(): void
    {
        $notice = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '詳細確認お知らせ',
            'content'             => '詳細確認用の本文テキスト',
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get("/notice/manage/show/{$notice->id}");

        $response->assertStatus(200);
        $response->assertSee('お知らせ詳細');
        $response->assertSee('詳細確認お知らせ');
        $response->assertSee('詳細確認用の本文テキスト');
    }

    #[Test]
    public function test_admin_cannot_view_other_company_notice_manage(): void
    {
        // 企業Bのお知らせ
        $noticeB = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyB->id,
            'created_by_user_id'  => $this->adminUserB->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '他社企業Bのお知らせ',
            'content'             => '他社本文',
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        $response = $this->actingAs($this->adminUserA, 'web')->get("/notice/manage/show/{$noticeB->id}");
        $response->assertStatus(404);
    }

    #[Test]
    public function test_admin_can_edit_and_update_notice(): void
    {
        $notice = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '編集前タイトル',
            'content'             => '編集前本文',
            'is_published'        => false,
            'published_at'        => null,
        ]);

        // 編集フォーム表示
        $editRes = $this->actingAs($this->adminUserA, 'web')->get("/notice/manage/edit/{$notice->id}");
        $editRes->assertStatus(200);
        $editRes->assertSee('お知らせ編集');
        $editRes->assertSee('編集前タイトル');

        // 更新実行（公開に変更）
        $updateRes = $this->actingAs($this->adminUserA, 'web')->post("/notice/manage/update/{$notice->id}", [
            'title'        => '編集後更新タイトル',
            'content'      => '編集後更新本文',
            'is_published' => '1',
        ]);

        $updateRes->assertRedirect(route('tenant.notice.manage.complete', [
            'id'   => $notice->id,
            'mode' => 'edit',
        ]));

        $notice->refresh();
        $this->assertEquals('編集後更新タイトル', $notice->title);
        $this->assertEquals('編集後更新本文', $notice->content);
        $this->assertTrue($notice->is_published);
        $this->assertNotNull($notice->published_at);
    }

    #[Test]
    public function test_published_notice_cannot_be_deleted(): void
    {
        // 公開中のお知らせ
        $notice = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '公開中お知らせ（削除禁止）',
            'content'             => '本文',
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        // 削除確認画面へ行こうとすると詳細へリダイレクト
        $confirmRes = $this->actingAs($this->adminUserA, 'web')->get("/notice/manage/delete/{$notice->id}");
        $confirmRes->assertRedirect(route('tenant.notice.manage.show', $notice->id));
        $confirmRes->assertSessionHas('error');

        // 削除POSTを実行しても拒否され詳細へリダイレクト
        $deleteRes = $this->actingAs($this->adminUserA, 'web')->post("/notice/manage/delete/{$notice->id}");
        $deleteRes->assertRedirect(route('tenant.notice.manage.show', $notice->id));
        $deleteRes->assertSessionHas('error');

        // DBに残っていること
        $this->assertDatabaseHas('notices', ['id' => $notice->id]);
    }

    #[Test]
    public function test_draft_notice_can_be_deleted(): void
    {
        // 下書きお知らせ
        $notice = Notice::create([
            'sender_type'         => 'company',
            'company_id'          => $this->companyA->id,
            'created_by_user_id'  => $this->adminUserA->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '下書きお知らせ（削除可能）',
            'content'             => '本文',
            'is_published'        => false,
            'published_at'        => null,
        ]);

        NoticeAttachment::create([
            'notice_id'  => $notice->id,
            'file_name'  => 'draft_attachment.pdf',
            'mime_type'  => 'application/pdf',
            'file_size'  => 512,
            'file_data'  => 'dummy',
            'created_at' => now(),
        ]);

        // 削除確認画面
        $confirmRes = $this->actingAs($this->adminUserA, 'web')->get("/notice/manage/delete/{$notice->id}");
        $confirmRes->assertStatus(200);
        $confirmRes->assertSee('お知らせ削除確認');
        $confirmRes->assertSee('下書きお知らせ（削除可能）');

        // 削除実行
        $deleteRes = $this->actingAs($this->adminUserA, 'web')->post("/notice/manage/delete/{$notice->id}");
        $deleteRes->assertRedirect(route('tenant.notice.manage.complete', ['mode' => 'delete']));
        $deleteRes->assertSessionHas('success_info');

        // 削除完了画面が表示できること
        $completeRes = $this->actingAs($this->adminUserA, 'web')
            ->withSession(['success_info' => '下書きお知らせを完全に削除いたしました。'])
            ->get(route('tenant.notice.manage.complete', ['mode' => 'delete']));
        $completeRes->assertStatus(200);
        $completeRes->assertSee('お知らせを削除しました');
        $completeRes->assertSee('下書きお知らせを完全に削除いたしました。');

        // DBから削除されていること（添付ファイル含む）
        $this->assertDatabaseMissing('notices', ['id' => $notice->id]);
        $this->assertDatabaseMissing('notice_attachments', ['notice_id' => $notice->id]);
    }
}
