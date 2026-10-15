<?php

namespace Tests\Feature\Tenant;

use App\Mail\ChatMessageSentMail;
use App\Models\ChatMessage;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\User;
use App\Models\UserAssignedLink;
use App\Services\ChatNotificationService;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * チャット新着通知メール配信マトリックステスト
 */
class ChatNotificationTest extends TestCase
{
    use DatabaseTransactions;

    // 送信側企業・ユーザー
    protected Company $companySender;
    protected User $senderUser;
    protected User $senderColleague;

    // 受信側企業A（担当割当なし）
    protected Company $companyReceiverA;
    protected CompanyLink $linkNoAssigned;
    protected User $receiverAAdmin;
    protected User $receiverAStaffUnassigned;
    protected User $receiverANoNotifyChat;
    protected User $receiverAInactive;

    // 受信側企業B（担当割当あり）
    protected Company $companyReceiverB;
    protected CompanyLink $linkWithAssigned;
    protected User $receiverBAdmin;
    protected User $receiverBStaffAssigned;
    protected User $receiverBStaffAssignedNoNotif;
    protected User $receiverBStaffUnassigned;

    protected ChatService $chatService;
    protected ChatNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // 1. 送信側企業
        $this->companySender = Company::create([
            'url_code'         => "chat-snd-{$suffix}",
            'corporate_number' => '5000000000001',
            'name'             => '送信テスト企業株式会社',
            'abbreviated_name' => '送信企業',
            'tel'              => '03-5000-0001',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '丸の内1-1',
            'status'           => 'active',
        ]);

        $this->senderUser = User::create([
            'company_id'      => $this->companySender->id,
            'email'           => "sender-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '送信者 本人',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->senderColleague = User::create([
            'company_id'      => $this->companySender->id,
            'email'           => "sender-colleague-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '送信者 同僚',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        // 2. 受信側企業A（担当者割当なし）
        $this->companyReceiverA = Company::create([
            'url_code'         => "chat-rcva-{$suffix}",
            'corporate_number' => '5000000000002',
            'name'             => '受信テスト企業A株式会社',
            'abbreviated_name' => '受信企業A',
            'tel'              => '06-5000-0002',
            'zip_code'         => '530-0001',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '中之島1-1',
            'status'           => 'active',
        ]);

        $this->linkNoAssigned = CompanyLink::create([
            'buyer_company_id'  => $this->companySender->id,
            'seller_company_id' => $this->companyReceiverA->id,
            'status'            => 'approved',
        ]);

        $this->receiverAAdmin = User::create([
            'company_id'      => $this->companyReceiverA->id,
            'email'           => "receiver-a-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 全担当管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->receiverAStaffUnassigned = User::create([
            'company_id'      => $this->companyReceiverA->id,
            'email'           => "receiver-a-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 担当外スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => true,
        ]);

        $this->receiverANoNotifyChat = User::create([
            'company_id'      => $this->companyReceiverA->id,
            'email'           => "receiver-a-nonotif-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 通知OFF全担当',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => false,
        ]);

        $this->receiverAInactive = User::create([
            'company_id'      => $this->companyReceiverA->id,
            'email'           => "receiver-a-inactive-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 停止中全担当',
            'role'            => 'staff',
            'status'          => 'suspended',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        // 3. 受信側企業B（担当者割当あり）
        $this->companyReceiverB = Company::create([
            'url_code'         => "chat-rcvb-{$suffix}",
            'corporate_number' => '5000000000003',
            'name'             => '受信テスト企業B株式会社',
            'abbreviated_name' => '受信企業B',
            'tel'              => '052-500-0003',
            'zip_code'         => '450-0001',
            'prefecture_code'  => 23,
            'city'             => '名古屋市中村区',
            'address'          => '名駅1-1',
            'status'           => 'active',
        ]);

        $this->linkWithAssigned = CompanyLink::create([
            'buyer_company_id'  => $this->companySender->id,
            'seller_company_id' => $this->companyReceiverB->id,
            'status'            => 'approved',
        ]);

        $this->receiverBAdmin = User::create([
            'company_id'      => $this->companyReceiverB->id,
            'email'           => "receiver-b-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 全担当管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_chat'     => true,
        ]);

        $this->receiverBStaffAssigned = User::create([
            'company_id'      => $this->companyReceiverB->id,
            'email'           => "receiver-b-assigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 個別割当担当者',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => true,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->receiverBStaffAssigned->id,
            'company_link_id' => $this->linkWithAssigned->id,
        ]);

        $this->receiverBStaffAssignedNoNotif = User::create([
            'company_id'      => $this->companyReceiverB->id,
            'email'           => "receiver-b-assigned-nonotif-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 個別割当担当者(通知OFF)',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => false,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->receiverBStaffAssignedNoNotif->id,
            'company_link_id' => $this->linkWithAssigned->id,
        ]);

        $this->receiverBStaffUnassigned = User::create([
            'company_id'      => $this->companyReceiverB->id,
            'email'           => "receiver-b-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業B 担当外スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_chat'     => true,
        ]);

        $this->notificationService = app(ChatNotificationService::class);
        $this->chatService         = app(ChatService::class);
    }

    #[Test]
    public function ケースA_相手先に担当者割当がない場合_相手企業の全担当者にメール送信されること(): void
    {
        Mail::fake();

        // 担当割当のない linkNoAssigned にメッセージを送信
        $this->chatService->sendMessage(
            companyLinkId: $this->linkNoAssigned->id,
            companyId: $this->companySender->id,
            userId: $this->senderUser->id,
            messageBody: '割当なし連携へのメッセージです。'
        );

        // 送信先確認: 全担当管理者(receiverAAdmin)には届く
        Mail::assertSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverAAdmin->email);
        });

        // 担当外スタッフ(is_all_assigned=0)には届かない
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverAStaffUnassigned->email);
        });

        // 通知OFF全担当には届かない
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverANoNotifyChat->email);
        });

        // 停止中ユーザーには届かない
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverAInactive->email);
        });
    }

    #[Test]
    public function ケースB_相手先に特定担当者が割り当てられている場合_割り当てられた担当者のみにメール送信されること(): void
    {
        Mail::fake();

        // 個別担当割当済みの linkWithAssigned にメッセージを送信
        $this->chatService->sendMessage(
            companyLinkId: $this->linkWithAssigned->id,
            companyId: $this->companySender->id,
            userId: $this->senderUser->id,
            messageBody: '個別担当割当済み連携へのメッセージです。'
        );

        // 割り当てられた担当者(receiverBStaffAssigned)にのみ送信される
        Mail::assertSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverBStaffAssigned->email);
        });

        // 割当担当者であっても通知OFFの場合は届かない
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverBStaffAssignedNoNotif->email);
        });

        // 割当のない全担当管理者や一般スタッフには届かない（担当分けの厳格な制御）
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverBAdmin->email);
        });

        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->receiverBStaffUnassigned->email);
        });
    }

    #[Test]
    public function ケースC_除外制御_メッセージ送信者本人および自社ユーザーにはメール送信されないこと(): void
    {
        Mail::fake();

        $this->chatService->sendMessage(
            companyLinkId: $this->linkNoAssigned->id,
            companyId: $this->companySender->id,
            userId: $this->senderUser->id,
            messageBody: '送信者除外テスト'
        );

        // 送信者本人には届かない
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->senderUser->email);
        });

        // 自社の同僚にも届かない（相手先企業のみが配信対象）
        Mail::assertNotSent(ChatMessageSentMail::class, function ($mail) {
            return $mail->hasTo($this->senderColleague->email);
        });
    }
}
