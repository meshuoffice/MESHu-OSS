<?php

namespace Tests\Feature\Tenant;

use App\Mail\TransactionCommentAddedMail;
use App\Mail\TransactionStatusChangedMail;
use App\Models\Company;
use App\Models\CompanyLink;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserAssignedLink;
use App\Services\TransactionNotificationService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * 取引・コメント通知メール送信マトリックステスト
 *
 * 【検証項目】
 * 1. 取引ステータス変更時（買手→売手 / 売手→買手）
 *    - 全担当（is_all_assigned = 1）に送信されること
 *    - つながり個別割当担当者（user_assigned_links）に送信されること
 *    - 担当外（is_all_assigned = 0 かつ 割当なし）に送信されないこと
 *    - 取引通知OFF（notify_trade = false）に送信されないこと
 *    - アカウント停止中（status != active）に送信されないこと
 *    - アクション実行側（自社）には送信されないこと
 * 2. 取引コメント投稿時
 *    - 相手先企業の担当者かつコメント通知ON（notify_comment = true）に送信されること
 *    - コメント通知OFF（notify_comment = false）に送信されないこと
 *    - 投稿者本人には送信されないこと
 */
class TransactionNotificationTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyBuyer;
    protected Company $companySeller;
    protected CompanyLink $approvedLink;

    // 買手側ユーザー
    protected User $buyerAdmin;              // 全担当・通知ON
    protected User $buyerStaffAssigned;       // 個別割当・通知ON
    protected User $buyerStaffUnassigned;     // 割当なし（担当外）
    protected User $buyerNoNotifyTrade;       // 全担当・取引通知OFF
    protected User $buyerNoNotifyComment;     // 全担当・コメント通知OFF
    protected User $buyerInactive;            // 全担当・停止中

    // 売手側ユーザー
    protected User $sellerAdmin;             // 全担当・通知ON
    protected User $sellerStaffAssigned;      // 個別割当・通知ON
    protected User $sellerStaffUnassigned;    // 割当なし（担当外）
    protected User $sellerNoNotifyTrade;      // 全担当・取引通知OFF
    protected User $sellerNoNotifyComment;    // 全担当・コメント通知OFF
    protected User $sellerInactive;           // 全担当・停止中

    protected TransactionService $transactionService;
    protected TransactionNotificationService $notificationService;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // 1. 企業作成
        $this->companyBuyer = Company::create([
            'url_code'         => "buyer-notif-{$suffix}",
            'corporate_number' => '1000000000001',
            'name'             => '買手通知テスト株式会社',
            'abbreviated_name' => '買手通知',
            'tel'              => '03-1000-0001',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町1-1',
            'status'           => 'active',
        ]);

        $this->companySeller = Company::create([
            'url_code'         => "seller-notif-{$suffix}",
            'corporate_number' => '2000000000002',
            'name'             => '売手通知テスト株式会社',
            'abbreviated_name' => '売手通知',
            'tel'              => '06-2000-0002',
            'zip_code'         => '530-0001',
            'prefecture_code'  => 27,
            'city'             => '大阪市北区',
            'address'          => '梅田1-1',
            'status'           => 'active',
        ]);

        // 2. つながり（承認済）
        $this->approvedLink = CompanyLink::create([
            'buyer_company_id'  => $this->companyBuyer->id,
            'seller_company_id' => $this->companySeller->id,
            'status'            => 'approved',
        ]);

        // 3. 買手ユーザー群作成
        $this->buyerAdmin = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 全担当管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        $this->buyerStaffAssigned = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-assigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 個別割当担当者',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->buyerStaffAssigned->id,
            'company_link_id' => $this->approvedLink->id,
        ]);

        $this->buyerStaffUnassigned = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 担当外スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        $this->buyerNoNotifyTrade = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-no-trade-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 取引通知OFF',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => false,
            'notify_comment'  => true,
        ]);

        $this->buyerNoNotifyComment = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-no-comment-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 コメント通知OFF',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => false,
        ]);

        $this->buyerInactive = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-inactive-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 停止中ユーザー',
            'role'            => 'staff',
            'status'          => 'suspended',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        // 4. 売手ユーザー群作成
        $this->sellerAdmin = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 全担当管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        $this->sellerStaffAssigned = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-assigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 個別割当担当者',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->sellerStaffAssigned->id,
            'company_link_id' => $this->approvedLink->id,
        ]);

        $this->sellerStaffUnassigned = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 担当外スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        $this->sellerNoNotifyTrade = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-no-trade-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 取引通知OFF',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => false,
            'notify_comment'  => true,
        ]);

        $this->sellerNoNotifyComment = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-no-comment-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 コメント通知OFF',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => false,
        ]);

        $this->sellerInactive = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-inactive-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 停止中ユーザー',
            'role'            => 'staff',
            'status'          => 'suspended',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
        ]);

        $this->transactionService   = app(TransactionService::class);
        $this->notificationService = app(TransactionNotificationService::class);
    }

    #[Test]
    public function 買手から見積依頼送信時に売手の条件合致ユーザーのみにメールが送信される(): void
    {
        Mail::fake();

        // 買手が新規見積依頼を作成
        $txIds = $this->transactionService->createEstimateRequests(
            $this->companyBuyer->id,
            $this->buyerAdmin->id,
            [$this->approvedLink->id],
            ['title' => '見積依頼通知マトリックステスト'],
            [['item_name' => '部材A', 'quantity' => 1]]
        );
        $txId = $txIds[0];

        // 売手側：全担当管理者（○ 送信される）
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerAdmin->email);
        });

        // 売手側：個別割当担当スタッフ（○ 送信される）
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerStaffAssigned->email);
        });

        // 売手側：担当外スタッフ（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerStaffUnassigned->email);
        });

        // 売手側：取引通知OFFユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerNoNotifyTrade->email);
        });

        // 売手側：停止中ユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerInactive->email);
        });

        // 買手側：送信元企業ユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerAdmin->email);
        });
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffAssigned->email);
        });
    }

    #[Test]
    public function 売手から見積回答送信時に買手の条件合致ユーザーのみにメールが送信される(): void
    {
        // 事前に取引レコードを作成
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'estimate_request',
            'title'           => '見積回答通知テスト',
        ]);

        Mail::fake();

        // 売手が見積回答を送信
        $this->transactionService->submitEstimateReply(
            $tx->id,
            $this->companySeller->id,
            $this->sellerAdmin->id,
            '見積回答をお送りします。'
        );

        // 買手側：全担当管理者（○ 送信される）
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerAdmin->email);
        });

        // 買手側：個別割当担当スタッフ（○ 送信される）
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffAssigned->email);
        });

        // 買手側：担当外スタッフ（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffUnassigned->email);
        });

        // 買手側：取引通知OFFユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerNoNotifyTrade->email);
        });

        // 買手側：停止中ユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerInactive->email);
        });

        // 売手側：自社ユーザー（× 送信されない）
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerAdmin->email);
        });
    }

    #[Test]
    public function 売手から受注承諾送信時に買手の条件合致ユーザーのみにメールが送信される(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'order_placed',
            'title'           => '受注承諾通知テスト',
        ]);

        Mail::fake();

        // 売手が受注を承諾
        $this->transactionService->acceptOrder(
            $tx->id,
            $this->companySeller->id,
            $this->sellerAdmin->id,
            'ご注文を承諾しました。'
        );

        // 買手側：全担当および担当者に送信されること
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerAdmin->email);
        });
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffAssigned->email);
        });

        // 買手側：担当外・通知OFFには送信されないこと
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffUnassigned->email);
        });
        Mail::assertNotSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerNoNotifyTrade->email);
        });
    }

    #[Test]
    public function 買手からコメント投稿時に売手の通知ON担当者のみに送信され本人には送信されない(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'estimate_request',
            'title'           => 'コメント通知テスト',
        ]);

        Mail::fake();

        // 買手管理者がコメントを投稿
        $this->notificationService->notifyNewComment(
            $tx->id,
            $this->buyerAdmin->id,
            '納期の相談をさせてください。'
        );

        // 売手側：全担当（コメント通知ON）に送信される（○）
        Mail::assertSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerAdmin->email);
        });

        // 売手側：個別割当担当（コメント通知ON）に送信される（○）
        Mail::assertSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerStaffAssigned->email);
        });

        // 売手側：コメント通知OFFユーザーには送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerNoNotifyComment->email);
        });

        // 売手側：担当外ユーザーには送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerStaffUnassigned->email);
        });

        // 買手側：投稿者本人および自社ユーザーには送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerAdmin->email);
        });
    }

    #[Test]
    public function 売手からコメント投稿時に買手の通知ON担当者のみに送信され本人には送信されない(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'estimate_request',
            'title'           => '売手コメント通知テスト',
        ]);

        Mail::fake();

        // 売手スタッフがコメントを投稿
        $this->notificationService->notifyNewComment(
            $tx->id,
            $this->sellerStaffAssigned->id,
            '承知いたしました。確認いたします。'
        );

        // 買手側：全担当（コメント通知ON）に送信される（○）
        Mail::assertSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerAdmin->email);
        });

        // 買手側：個別割当担当（コメント通知ON）に送信される（○）
        Mail::assertSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffAssigned->email);
        });

        // 買手側：コメント通知OFFユーザーには送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerNoNotifyComment->email);
        });

        // 買手側：担当外スタッフには送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->buyerStaffUnassigned->email);
        });

        // 売手側：投稿者本人には送信されない（×）
        Mail::assertNotSent(TransactionCommentAddedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerStaffAssigned->email);
        });
    }
}