<?php

namespace Tests\Feature\Tenant;

use App\Mail\TransactionCommentAddedMail;
use App\Mail\TransactionStatusChangedMail;
use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\CompanyLink;
use App\Models\Owner;
use App\Models\Transaction;
use App\Models\TransactionAttachment;
use App\Models\TransactionComment;
use App\Models\TransactionHistory;
use App\Models\TransactionItem;
use App\Models\User;
use App\Models\UserAssignedLink;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyBuyer;
    protected Company $companySeller;
    protected Company $companyOther;
    protected Owner $verifierOwner;
    protected User $buyerAdmin;
    protected User $buyerStaffAssigned;
    protected User $buyerStaffUnassigned;
    protected User $sellerAdmin;
    protected CompanyLink $approvedLink;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $suffix = uniqid();

        // 買手企業
        $this->companyBuyer = Company::create([
            'url_code'         => "buyer-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => '株式会社バイヤー商事',
            'abbreviated_name' => 'バイヤー商事',
            'abbreviated_kana' => 'バイヤーショウジ',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
            'status'           => 'active',
        ]);

        // 売手企業
        $this->companySeller = Company::create([
            'url_code'         => "seller-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => '株式会社セラーサプライ',
            'abbreviated_name' => 'セラーサプライ',
            'abbreviated_kana' => 'セラーサプライ',
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

        // 無関係な第三者企業
        $this->companyOther = Company::create([
            'url_code'         => "other-{$suffix}",
            'corporate_number' => '3333333333333',
            'name'             => '株式会社第三者テック',
            'abbreviated_name' => '第三者テック',
            'abbreviated_kana' => 'ダイサンシャテック',
            'tel'              => '045-333-4444',
            'zip_code'         => '220-0001',
            'prefecture_code'  => 14,
            'city'             => '横浜市西区',
            'address'          => 'みなとみらい1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
            'status'           => 'active',
        ]);

        // 連携承認レコード (buyer -> seller)
        $this->approvedLink = CompanyLink::create([
            'buyer_company_id'  => $this->companyBuyer->id,
            'seller_company_id' => $this->companySeller->id,
            'status'            => 'approved',
        ]);

        // 買手管理者 (is_all_assigned = 1)
        $this->buyerAdmin = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 買手スタッフ（担当割り当てあり）
        $this->buyerStaffAssigned = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-staff-assigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 担当スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);
        UserAssignedLink::create([
            'user_id'         => $this->buyerStaffAssigned->id,
            'company_link_id' => $this->approvedLink->id,
        ]);

        // 買手スタッフ（担当割り当てなし）
        $this->buyerStaffUnassigned = User::create([
            'company_id'      => $this->companyBuyer->id,
            'email'           => "buyer-staff-unassigned-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '買手 未担当スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 0,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 売手管理者 (is_all_assigned = 1)
        $this->sellerAdmin = User::create([
            'company_id'      => $this->companySeller->id,
            'email'           => "seller-admin-{$suffix}@example.com",
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '売手 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);
    }

    #[Test]
    public function 未認証ユーザーは取引一覧にアクセスできない(): void
    {
        $this->get('/buy')->assertRedirect('/');
        $this->get('/sell')->assertRedirect('/');
    }

    #[Test]
    public function 買手と売手の一覧画面が正しく表示される(): void
    {
        $this->actingAs($this->buyerAdmin, 'web')
            ->get('/buy')
            ->assertStatus(200)
            ->assertSee('購入取引一覧');

        $this->actingAs($this->sellerAdmin, 'web')
            ->get('/sell')
            ->assertStatus(200)
            ->assertSee('販売取引一覧');
    }

    #[Test]
    public function 買手が見積依頼を作成できる(): void
    {
        // 1. 新規見積入力画面
        $this->actingAs($this->buyerAdmin, 'web')
            ->get('/buy/estimate/new')
            ->assertStatus(200)
            ->assertSee('新規見積依頼の作成')
            ->assertSee($this->companySeller->name);

        // 2. 見積確認画面へPOST
        $confirmResponse = $this->actingAs($this->buyerAdmin, 'web')
            ->post('/buy/estimate/confirm', [
                'link_ids'              => [$this->approvedLink->id],
                'title'                 => 'オフィス備品一括見積テスト',
                'desired_delivery_date' => date('Y-m-d', strtotime('+14 days')),
                'buyer_remarks'         => '14日以内の納品を希望します。',
                'items'                 => [
                    [
                        'item_name'     => 'Cat6A LANケーブル 10m',
                        'specification' => '青 / 難燃',
                        'quantity'      => 5,
                        'unit'          => '本',
                    ],
                    [
                        'item_name'     => 'OAタップ 6個口',
                        'specification' => '雷ガード付 3m',
                        'quantity'      => 3,
                        'unit'          => '個',
                    ],
                ],
            ]);

        $confirmResponse->assertStatus(200)
            ->assertSee('見積依頼内容の確認')
            ->assertSee('Cat6A LANケーブル 10m');

        // 3. 見積依頼作成を実行 (POST /buy/estimate/create)
        $createResponse = $this->actingAs($this->buyerAdmin, 'web')
            ->post('/buy/estimate/create', [
                'link_ids'              => [$this->approvedLink->id],
                'title'                 => 'オフィス備品一括見積テスト',
                'desired_delivery_date' => date('Y-m-d', strtotime('+14 days')),
                'buyer_remarks'         => '14日以内の納品を希望します。',
                'items'                 => [
                    [
                        'item_name'     => 'Cat6A LANケーブル 10m',
                        'specification' => '青 / 難燃',
                        'quantity'      => 5,
                        'unit'          => '本',
                    ],
                    [
                        'item_name'     => 'OAタップ 6個口',
                        'specification' => '雷ガード付 3m',
                        'quantity'      => 3,
                        'unit'          => '個',
                    ],
                ],
            ]);

        $createResponse->assertRedirect('/buy/estimate/complete');

        $this->actingAs($this->buyerAdmin, 'web')
            ->get('/buy/estimate/complete')
            ->assertStatus(200)
            ->assertSee('見積依頼を送信しました');

        // DB検証
        $this->assertDatabaseHas('transactions', [
            'company_link_id' => $this->approvedLink->id,
            'title'           => 'オフィス備品一括見積テスト',
            'status'          => 'estimate_request',
        ]);

        $tx = Transaction::where('company_link_id', $this->approvedLink->id)->first();
        $this->assertNotNull($tx);
        $this->assertEquals(2, TransactionItem::where('transaction_id', $tx->id)->count());
        $this->assertEquals(1, TransactionHistory::where('transaction_id', $tx->id)->count());

        // メール送信検証 (売手管理者に通知が届くこと)
        Mail::assertSent(TransactionStatusChangedMail::class, function ($mail) {
            return $mail->hasTo($this->sellerAdmin->email);
        });
    }

    #[Test]
    public function 見積依頼から取引完了までの一連フローが正常に動作する(): void
    {
        // 1. 買手が見積依頼作成
        $this->actingAs($this->buyerAdmin, 'web')
            ->post('/buy/estimate/create', [
                'link_ids' => [$this->approvedLink->id],
                'title'    => '定期サプライ案件',
                'items'    => [
                    [
                        'item_name'     => 'コピー用紙 A4',
                        'specification' => '500枚×5冊',
                        'quantity'      => 10,
                        'unit'          => '箱',
                    ],
                ],
            ])->assertRedirect('/buy/estimate/complete');

        $tx = Transaction::where('company_link_id', $this->approvedLink->id)->first();
        $this->assertEquals('estimate_request', $tx->status);

        // 2. 売手が編集・下書き保存（単価入力）
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/edit/{$tx->id}", [
                'action'          => 'save',
                'title'           => '定期サプライ案件 (売手確認済)',
                'delivery_date'   => date('Y-m-d', strtotime('+7 days')),
                'shipping_fee'    => 1000,
                'discount_amount' => 500,
                'tax_rate'        => 10,
                'seller_remarks'  => '在庫確認できました。',
                'items'           => [
                    [
                        'item_name'     => 'コピー用紙 A4',
                        'specification' => '500枚×5冊',
                        'quantity'      => 10,
                        'unit'          => '箱',
                        'unit_price'    => 2000, // 単価2000 => 明細計20,000 + 送料1,000 - 値引500 = 小計20,500
                    ],
                ],
            ])->assertRedirect("/sell/edit/{$tx->id}");

        $tx->refresh();
        $this->assertEquals('定期サプライ案件 (売手確認済)', $tx->title);
        $this->assertEquals(20500, (float)$tx->subtotal_amount);
        $this->assertEquals(2050, (float)$tx->tax_amount); // 10%
        $this->assertEquals(22550, (float)$tx->total_amount);
        $this->assertEquals('estimate_request', $tx->status); // 下書き保存時はステータス変化なし

        // 案件名を空にした場合はバリデーションエラーとなること
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/edit/{$tx->id}", [
                'title' => '',
            ])
            ->assertSessionHasErrors(['title']);

        // 添付ファイルを付けて見積回答確認画面へ進む（action = to_reply）
        $fakePdf = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/edit/{$tx->id}", [
                'action'          => 'to_reply',
                'title'           => '定期サプライ案件 (売手確認済)',
                'shipping_fee'    => 1000,
                'discount_amount' => 500,
                'tax_rate'        => 10,
                'items'           => [
                    [
                        'item_name'  => 'コピー用紙 A4',
                        'quantity'   => 10,
                        'unit_price' => 2000,
                    ],
                ],
                'attachments'     => [$fakePdf],
            ])
            ->assertRedirect("/sell/reply/{$tx->id}");

        $this->assertDatabaseHas('transaction_attachments', [
            'transaction_id' => $tx->id,
            'file_name'      => 'spec.pdf',
        ]);

        // 3. 売手が見積回答画面を確認し、正式送信する
        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/reply/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('見積回答の送信確認')
            ->assertSee('¥22,550');

        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/reply/{$tx->id}", [
                'seller_remarks' => '正式な見積回答をお送りします。',
            ])->assertRedirect("/sell/reply-complete/{$tx->id}");

        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/reply-complete/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('見積回答を送信しました');

        $tx->refresh();
        $this->assertEquals('estimate_replied', $tx->status);

        // 4. 買手が見積内容を確認し、発注する
        $this->actingAs($this->buyerAdmin, 'web')
            ->get("/buy/order/estimate/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('発注内容の最終確認')
            ->assertSee('¥22,550');

        $this->actingAs($this->buyerAdmin, 'web')
            ->post("/buy/order/estimate/{$tx->id}", [
                'buyer_remarks' => '上記見積内容にて正式発注いたします。',
            ])->assertRedirect("/buy/order-complete/{$tx->id}");

        $this->actingAs($this->buyerAdmin, 'web')
            ->get("/buy/order-complete/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('発注（注文）を送信しました');

        $tx->refresh();
        $this->assertEquals('order_placed', $tx->status);

        // 5. 売手が受注を承諾する
        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/order-accept/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('注文回答（受注承諾）の確認');

        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/order-accept/{$tx->id}", [
                'accept_remarks' => 'ご注文を承諾いたしました。納品手配を開始します。',
            ])->assertRedirect("/sell/order-accept-complete/{$tx->id}");

        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/order-accept-complete/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('受注を承諾しました');

        $tx->refresh();
        $this->assertEquals('order_accepted', $tx->status); // 取引完了
    }

    #[Test]
    public function 見積辞退フローが正常に動作する(): void
    {
        // 見積依頼作成
        $this->actingAs($this->buyerAdmin, 'web')
            ->post('/buy/estimate/create', [
                'link_ids' => [$this->approvedLink->id],
                'title'    => '短納期試作案件',
                'items'    => [
                    [
                        'item_name' => '特注基板',
                        'quantity'  => 1,
                        'unit'      => '枚',
                    ],
                ],
            ])->assertRedirect('/buy/estimate/complete');

        $tx = Transaction::where('company_link_id', $this->approvedLink->id)->first();

        // 売手が見積辞退画面を表示
        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/estimate-decline/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('見積辞退の確認');

        // 辞退を実行
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/estimate-decline/{$tx->id}", [
                'decline_category'      => '製造キャパシティ（生産枠）の上限超過のため',
                'decline_reason_detail' => '現在ラインが逼迫しており対応できません。',
            ])->assertRedirect("/sell/estimate-decline-complete/{$tx->id}");

        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/estimate-decline-complete/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('見積を辞退しました');

        $tx->refresh();
        $this->assertEquals('estimate_declined', $tx->status);
        $this->assertEquals('製造キャパシティ（生産枠）の上限超過のため', $tx->decline_category);
        $this->assertEquals('現在ラインが逼迫しており対応できません。', $tx->decline_reason);

        // 買手側辞退詳細画面の確認
        $this->actingAs($this->buyerAdmin, 'web')
            ->get("/buy/detail/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('販売企業からの辞退通知')
            ->assertSee('製造キャパシティ（生産枠）の上限超過のため');
    }

    #[Test]
    public function 直接注文および注文辞退フローが正常に動作する(): void
    {
        // 1. 買手が直接注文（/buy/order/create）
        $this->actingAs($this->buyerAdmin, 'web')
            ->post('/buy/order/create', [
                'company_link_id'       => $this->approvedLink->id,
                'title'                 => '直接発注案件',
                'desired_delivery_date' => date('Y-m-d', strtotime('+3 days')),
                'items'                 => [
                    [
                        'item_name' => '在庫部材A',
                        'quantity'  => 100,
                        'unit'      => '個',
                    ],
                ],
            ])->assertRedirect('/buy/order/complete');

        $this->actingAs($this->buyerAdmin, 'web')
            ->get('/buy/order/complete')
            ->assertStatus(200)
            ->assertSee('注文を送信しました');

        $tx = Transaction::where('company_link_id', $this->approvedLink->id)->first();
        $this->assertEquals('order_placed', $tx->status);

        // 2. 売手が注文辞退画面を表示
        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/order-decline/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('注文辞退の確認');

        // 3. 注文辞退を実行
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/order-decline/{$tx->id}", [
                'order_decline_category'      => '急な在庫欠品・製造停止が発生したため',
                'order_decline_reason_detail' => '該当ロットの部材が欠品しております。',
            ])->assertRedirect("/sell/order-decline-complete/{$tx->id}");

        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/order-decline-complete/{$tx->id}")
            ->assertStatus(200)
            ->assertSee('注文を辞退しました');

        $tx->refresh();
        $this->assertEquals('order_declined', $tx->status);
        $this->assertEquals('急な在庫欠品・製造停止が発生したため', $tx->decline_category);
    }

    #[Test]
    public function 取引コメントの投稿と削除が正常に動作する(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'estimate_request',
            'title'           => 'コメント検証案件',
        ]);

        // 買手がコメント投稿
        $this->actingAs($this->buyerAdmin, 'web')
            ->post("/buy/comment/{$tx->id}", [
                'comment' => '仕様書を追記しました。ご確認ください。',
            ])->assertRedirect("/buy/detail/{$tx->id}#comments");

        $comment = TransactionComment::where('transaction_id', $tx->id)->first();
        $this->assertNotNull($comment);
        $this->assertEquals('仕様書を追記しました。ご確認ください。', $comment->comment);
        $this->assertEquals($this->buyerAdmin->id, $comment->user_id);

        // 売手がコメント削除を試みる（他社・他ユーザーなので403）
        $this->actingAs($this->sellerAdmin, 'web')
            ->post("/sell/comment/delete/{$comment->id}")
            ->assertStatus(403);

        // 買手（投稿者本人）がコメント削除
        $this->actingAs($this->buyerAdmin, 'web')
            ->post("/buy/comment/delete/{$comment->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('transaction_comments', ['id' => $comment->id]);
    }

    #[Test]
    public function 帳票印刷画面が正常に出力される(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'order_accepted',
            'title'           => '印刷テスト案件',
            'total_amount'    => 55000,
            'subtotal_amount' => 50000,
            'tax_amount'      => 5000,
            'tax_rate'        => 10,
        ]);

        TransactionItem::create([
            'transaction_id' => $tx->id,
            'line_no'        => 1,
            'item_name'      => 'テスト印刷部材',
            'quantity'       => 10,
            'unit'           => '個',
            'unit_price'     => 5000,
            'amount'         => 50000,
        ]);

        // 買手による発注書印刷プレビュー
        $this->actingAs($this->buyerAdmin, 'web')
            ->get("/buy/print/{$tx->id}/order")
            ->assertStatus(200)
            ->assertSee('御 注 文 書')
            ->assertSee('テスト印刷部材')
            ->assertSee('55,000');

        // 売手による注文請書印刷プレビュー
        $this->actingAs($this->sellerAdmin, 'web')
            ->get("/sell/print/{$tx->id}/order_acceptance")
            ->assertStatus(200)
            ->assertSee('注 文 請 書')
            ->assertSee('テスト印刷部材');
    }

    #[Test]
    public function 担当企業外のスタッフおよび第三者企業はアクセス権限がない(): void
    {
        $tx = Transaction::create([
            'company_link_id' => $this->approvedLink->id,
            'status'          => 'estimate_request',
            'title'           => 'アクセス権限検証案件',
        ]);

        // 担当割り当てされていないスタッフは403
        $this->actingAs($this->buyerStaffUnassigned, 'web')
            ->get("/buy/detail/{$tx->id}")
            ->assertStatus(403);

        // 担当割り当てされているスタッフは200
        $this->actingAs($this->buyerStaffAssigned, 'web')
            ->get("/buy/detail/{$tx->id}")
            ->assertStatus(200);

        // 第三者企業のユーザーは403
        $otherUser = User::create([
            'company_id'      => $this->companyOther->id,
            'email'           => 'other-user@example.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '第三者 太郎',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        $this->actingAs($otherUser, 'web')
            ->get("/buy/detail/{$tx->id}")
            ->assertStatus(403);
    }
}
