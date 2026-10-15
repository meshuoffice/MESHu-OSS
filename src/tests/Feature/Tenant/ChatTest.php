<?php

namespace Tests\Feature\Tenant;

use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\ChatReadStatus;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ChatTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected Company $companyOther;
    protected CompanyLink $approvedLink;
    protected CompanyLink $pendingLink;

    protected User $userA;
    protected User $userB;
    protected User $userOther;
    protected User $userAStaffAssigned;
    protected User $userAStaffUnassigned;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // 企業A
        $this->companyA = Company::create([
            'url_code'         => "chat-corp-a-{$suffix}",
            'corporate_number' => '1234567890101',
            'name'             => 'チャットテスト企業A株式会社',
            'abbreviated_name' => '企業A',
            'tel'              => '03-1111-0001',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町1-1',
            'status'           => 'active',
        ]);

        // 企業B
        $this->companyB = Company::create([
            'url_code'         => "chat-corp-b-{$suffix}",
            'corporate_number' => '1234567890102',
            'name'             => 'チャットテスト企業B株式会社',
            'abbreviated_name' => '企業B',
            'tel'              => '06-2222-0002',
            'zip_code'         => '530-0001',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '梅田1-1',
            'status'           => 'active',
        ]);

        // 第3者企業
        $this->companyOther = Company::create([
            'url_code'         => "chat-corp-other-{$suffix}",
            'corporate_number' => '1234567890103',
            'name'             => '無関係第三者株式会社',
            'abbreviated_name' => '第三者',
            'tel'              => '092-333-0003',
            'zip_code'         => '810-0001',
            'prefecture_code'  => 40,
            'city'             => '福岡市中央区',
            'address'          => '天神1-1',
            'status'           => 'active',
        ]);

        // 承認済みつながり（企業A - 企業B）
        $this->approvedLink = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        // 承認待ちつながり
        $this->pendingLink = CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyOther->id,
            'status'            => 'pending',
        ]);

        // ユーザー作成
        $this->userA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "user-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->userB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => "user-b-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->userOther = User::create([
            'company_id'      => $this->companyOther->id,
            'email'           => "user-other-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '第三者 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->userAStaffAssigned = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-assigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 個別割当スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => true,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->userAStaffAssigned->id,
            'company_link_id' => $this->approvedLink->id,
        ]);

        $this->userAStaffUnassigned = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "staff-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 担当外スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => true,
        ]);
    }

    #[Test]
    public function 未ログイン時はチャット画面にアクセスできずログインへリダイレクトされる(): void
    {
        $response = $this->get('/chat');
        $response->assertRedirect('/');
    }

    #[Test]
    public function ログインユーザーはチャット一覧画面に正常にアクセスできる(): void
    {
        $response = $this->actingAs($this->userA, 'web')->get('/chat');
        $response->assertStatus(200);
        $response->assertSee('チャット');
        $response->assertSee('企業B');
    }

    #[Test]
    public function 参加している承認済み連携のチャット画面にアクセスできる(): void
    {
        $response = $this->actingAs($this->userA, 'web')->get("/chat/{$this->approvedLink->id}");
        $response->assertStatus(200);
        $response->assertSee('企業B');
    }

    #[Test]
    public function 承認待ちの連携チャットにはアクセスできない(): void
    {
        $response = $this->actingAs($this->userA, 'web')->get("/chat/{$this->pendingLink->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function 無関係な第三者企業の連携チャットにはアクセスできない(): void
    {
        $response = $this->actingAs($this->userOther, 'web')->get("/chat/{$this->approvedLink->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function 担当外の一般スタッフは未割当の連携チャットにアクセスできない(): void
    {
        $response = $this->actingAs($this->userAStaffUnassigned, 'web')->get("/chat/{$this->approvedLink->id}");
        $response->assertStatus(403);
    }

    #[Test]
    public function 個別割当された担当スタッフはチャット画面にアクセスできる(): void
    {
        $response = $this->actingAs($this->userAStaffAssigned, 'web')->get("/chat/{$this->approvedLink->id}");
        $response->assertStatus(200);
    }

    #[Test]
    public function テキストメッセージを正常に送信・保存できる(): void
    {
        $response = $this->actingAs($this->userA, 'web')->post("/chat/{$this->approvedLink->id}/message", [
            'message_body' => 'こんにちは、取引の件でお伺いしたいことがあります。',
        ]);

        $response->assertRedirect("/chat/{$this->approvedLink->id}");

        $this->assertDatabaseHas('chat_messages', [
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyA->id,
            'user_id'         => $this->userA->id,
            'message_body'    => 'こんにちは、取引の件でお伺いしたいことがあります。',
        ]);
    }

    #[Test]
    public function 添付ファイル付きメッセージを正常に送信・保存できる(): void
    {
        $fakeFile = UploadedFile::fake()->create('sample_doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->userA, 'web')->post("/chat/{$this->approvedLink->id}/message", [
            'message_body' => '資料を添付いたします。',
            'attachments'  => [$fakeFile],
        ]);

        $response->assertRedirect("/chat/{$this->approvedLink->id}");

        $message = ChatMessage::where('company_link_id', $this->approvedLink->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('資料を添付いたします。', $message->message_body);

        $attachment = ChatMessageAttachment::where('chat_message_id', $message->id)->first();
        $this->assertNotNull($attachment);
        $this->assertEquals('sample_doc.pdf', $attachment->file_name);
        $this->assertEquals('application/pdf', $attachment->mime_type);
        $this->assertNotNull($attachment->file_data);
    }

    #[Test]
    public function 本文と添付ファイルの両方が空の場合はバリデーションエラーとなる(): void
    {
        $response = $this->actingAs($this->userA, 'web')->post("/chat/{$this->approvedLink->id}/message", [
            'message_body' => '',
        ]);

        $response->assertSessionHasErrors(['message_body']);
    }

    #[Test]
    public function 添付ファイルのダウンロードが正常に行える(): void
    {
        $message = ChatMessage::create([
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyA->id,
            'user_id'         => $this->userA->id,
            'message_body'    => 'ダウンロード検証用メッセージ',
        ]);

        $attachment = ChatMessageAttachment::create([
            'chat_message_id' => $message->id,
            'file_name'       => 'test_download.txt',
            'mime_type'       => 'text/plain',
            'file_size'       => 18,
            'file_data'       => 'download test data',
            'created_at'      => now(),
        ]);

        // 参加企業ユーザー(企業B)からのダウンロード
        $response = $this->actingAs($this->userB, 'web')->get("/chat/attachment/{$attachment->id}");
        $response->assertStatus(200);
        $this->assertStringContainsString('text/plain', (string)$response->headers->get('Content-Type'));
        $this->assertEquals('download test data', $response->getContent());

        // 無関係の第三者ユーザーからのダウンロードは403
        $forbiddenResponse = $this->actingAs($this->userOther, 'web')->get("/chat/attachment/{$attachment->id}");
        $forbiddenResponse->assertStatus(403);
    }

    #[Test]
    public function チャット画面閲覧時に既読ステータスが更新される(): void
    {
        // 相手企業Bからメッセージが送信される
        $message = ChatMessage::create([
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyB->id,
            'user_id'         => $this->userB->id,
            'message_body'    => '新着メッセージです',
        ]);

        // 閲覧前は既読ステータスなし（未読）
        $readStatusBefore = ChatReadStatus::where('company_link_id', $this->approvedLink->id)
            ->where('user_id', $this->userA->id)
            ->first();
        $this->assertNull($readStatusBefore);

        // 企業Aユーザーがチャット画面を閲覧
        $response = $this->actingAs($this->userA, 'web')->get("/chat/{$this->approvedLink->id}");
        $response->assertStatus(200);

        // 閲覧後に最新メッセージIDで既読レコードが生成される
        $readStatusAfter = ChatReadStatus::where('company_link_id', $this->approvedLink->id)
            ->where('user_id', $this->userA->id)
            ->first();

        $this->assertNotNull($readStatusAfter);
        $this->assertEquals($message->id, $readStatusAfter->last_read_message_id);
    }

    #[Test]
    public function 企業名キーワード検索で該当する企業のみが絞り込まれる(): void
    {
        // 企業Bで検索（該当あり）
        $responseMatch = $this->actingAs($this->userA, 'web')->get('/chat?keyword=企業B');
        $responseMatch->assertStatus(200);
        $responseMatch->assertSee('企業B');

        // 存在しないキーワードで検索（該当なし）
        $responseNone = $this->actingAs($this->userA, 'web')->get('/chat?keyword=存在しない架空企業XYZ');
        $responseNone->assertStatus(200);
        $responseNone->assertSee('該当する企業が見つかりませんでした');
    }

    #[Test]
    public function 未読ありフィルターで未読メッセージが存在する企業のみが絞り込まれる(): void
    {
        // まだ未読メッセージがない状態
        $responseNoUnread = $this->actingAs($this->userA, 'web')->get('/chat?filter=unread');
        $responseNoUnread->assertStatus(200);
        $responseNoUnread->assertSee('該当する企業が見つかりませんでした');

        // 企業Bから新規メッセージが送信される（未読発生）
        ChatMessage::create([
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyB->id,
            'user_id'         => $this->userB->id,
            'message_body'    => '未読フィルター検証用メッセージ',
        ]);

        // 未読ありフィルターで企業Bが表示される
        $responseHasUnread = $this->actingAs($this->userA, 'web')->get('/chat?filter=unread');
        $responseHasUnread->assertStatus(200);
        $responseHasUnread->assertSee('企業B');
    }

    #[Test]
    public function 投稿者本人は自分のメッセージを削除できる(): void
    {
        $message = ChatMessage::create([
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyA->id,
            'user_id'         => $this->userA->id,
            'message_body'    => '削除テスト用メッセージ',
        ]);

        $response = $this->actingAs($this->userA, 'web')->post("/chat/message/delete/{$message->id}");
        $response->assertSessionHas('success');

        // 論理削除されていること
        $this->assertSoftDeleted('chat_messages', [
            'id' => $message->id,
        ]);

        // 画面に「メッセージは削除されました」と表示されること
        $viewResponse = $this->actingAs($this->userA, 'web')->get("/chat/{$this->approvedLink->id}");
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('メッセージは削除されました');
    }

    #[Test]
    public function 他人のメッセージは削除できない(): void
    {
        // 相手企業Bのユーザーが投稿したメッセージ
        $message = ChatMessage::create([
            'company_link_id' => $this->approvedLink->id,
            'company_id'      => $this->companyB->id,
            'user_id'         => $this->userB->id,
            'message_body'    => '相手企業のメッセージ',
        ]);

        // 企業Aのユーザーが削除を試みると403
        $response = $this->actingAs($this->userA, 'web')->post("/chat/message/delete/{$message->id}");
        $response->assertStatus(403);

        $this->assertNotSoftDeleted('chat_messages', [
            'id' => $message->id,
        ]);
    }
}
