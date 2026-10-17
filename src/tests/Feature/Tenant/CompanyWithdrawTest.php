<?php

namespace Tests\Feature\Tenant;

use App\Mail\AdminCompanyWithdrawNotificationMail;
use App\Mail\CompanyWithdrawMail;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyWithdrawTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $adminUser;
    protected User $staffUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'url_code'         => 'withdraw-test-corp',
            'name'             => '退会テスト株式会社',
            'abbreviated_name' => '退会テスト',
            'abbreviated_kana' => 'タイカイテスト',
            'tel'              => '03-5555-0000',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町1-1',
            'status'           => 'active',
        ]);

        $this->adminUser = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'withdraw-admin@test.com',
            'password_hash'   => Hash::make('AdminPass123!'),
            'user_name'       => '退会 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        $this->staffUser = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'withdraw-staff@test.com',
            'password_hash'   => Hash::make('StaffPass123!'),
            'user_name'       => '退会 一般社員',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);
    }

    /**
     * 未ログインユーザーは退会画面にアクセスできずリダイレクトされること
     */
    #[Test]
    public function 未ログインユーザーは退会手続き画面にアクセスできない()
    {
        $response = $this->get("/{$this->company->url_code}/settings/company/withdraw");
        $response->assertRedirect();
    }

    /**
     * 一般社員（member）は退会画面へのアクセス権限がなく403エラーとなること
     */
    #[Test]
    public function 一般社員は退会手続き画面へのアクセスが拒否される()
    {
        $response = $this->actingAs($this->staffUser, 'web')
            ->get("/{$this->company->url_code}/settings/company/withdraw");

        $response->assertStatus(403);
    }

    /**
     * 管理者は退会手続き画面を正常に表示でき、重要同意項目および危険ゾーンが存在すること
     */
    #[Test]
    public function 管理者は退会手続き画面を表示でき同意項目が網羅されている()
    {
        // 企業情報メンテナンス画面に危険ゾーン（退会手続き）が表示されること
        $editResponse = $this->actingAs($this->adminUser, 'web')
            ->get("/{$this->company->url_code}/settings/company");

        $editResponse->assertStatus(200);
        $editResponse->assertSee('MESHuの退会手続き');
        $editResponse->assertSee('退会手続きへ進む');

        // 退会画面
        $response = $this->actingAs($this->adminUser, 'web')
            ->get("/{$this->company->url_code}/settings/company/withdraw");

        $response->assertStatus(200);
        $response->assertSee('MESHuの退会手続き');
        $response->assertSee('1. 取引先への影響について');
        $response->assertSee('2. 進行中取引について');
        $response->assertSee('3. 法令関係書類の保管について');
        $response->assertSee('4. 自社全従業員アカウントの即時停止について');
        $response->assertSee('5. 30日間のデータ保持期間（一時停止）および完全削除について');
        $response->assertSee('現在のログインパスワード');
    }

    /**
     * 進行中の取引が存在する場合は警告バナーと取引リンクが表示されること
     */
    #[Test]
    public function 進行中の取引が存在する場合は警告バナーと取引リンクが表示される()
    {
        // パートナー企業とリンク作成
        $partner = Company::create([
            'url_code'         => 'partner-corp-active',
            'name'             => 'パートナー企業',
            'abbreviated_name' => 'パートナー',
            'abbreviated_kana' => 'パートナー',
            'tel'              => '03-9999-0000',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '大手町2-2',
            'status'           => 'active',
        ]);

        $link = \App\Models\CompanyLink::create([
            'buyer_company_id'  => $this->company->id,
            'seller_company_id' => $partner->id,
            'status'            => 'approved',
        ]);

        // 進行中取引（見積回答待ち）を作成
        \App\Models\Transaction::create([
            'company_link_id' => $link->id,
            'status'          => 'estimate_request',
            'title'           => '退会前未完了テスト案件',
            'is_tax_exempt'   => 0,
            'tax_rate'        => 10,
        ]);

        $response = $this->actingAs($this->adminUser, 'web')
            ->get("/{$this->company->url_code}/settings/company/withdraw");

        $response->assertStatus(200);
        $response->assertSee('進行中の取引が 1 件あります');
        $response->assertSee(route('tenant.buy.index'));
        $response->assertSee('取引一覧を確認する');
    }

    /**
     * 同意チェックボックスやパスワードが未入力の場合はバリデーションエラーとなること
     */
    #[Test]
    public function 同意項目またはパスワードが不足している場合はバリデーションエラー()
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->post("/{$this->company->url_code}/settings/company/withdraw", [
                'agreement_partner' => '1',
                // agreement_transactions 欠落
                'agreement_backup' => '1',
                'password' => '',
            ]);

        $response->assertSessionHasErrors(['agreement_transactions', 'agreement_users', 'agreement_period', 'password']);

        // 企業ステータスは変化しない
        $this->assertEquals('active', $this->company->fresh()->status);
        $this->assertEquals('active', $this->adminUser->fresh()->status);
    }

    /**
     * パスワードが一致しない場合はエラーとなり退会処理は実行されないこと
     */
    #[Test]
    public function パスワードが一致しない場合はエラーとなり退会は実行されない()
    {
        $response = $this->actingAs($this->adminUser, 'web')
            ->post("/{$this->company->url_code}/settings/company/withdraw", [
                'agreement_partner'      => '1',
                'agreement_transactions' => '1',
                'agreement_backup'       => '1',
                'agreement_users'        => '1',
                'agreement_period'       => '1',
                'password'               => 'WrongPassword999',
            ]);

        $response->assertSessionHasErrors(['password']);
        $this->assertEquals('active', $this->company->fresh()->status);
    }

    /**
     * 正常系：全項目同意とパスワード認証により退会が実行され、suspended更新・メール送信・ログアウトが行われること
     */
    #[Test]
    public function 全項目同意と正しいパスワードで退会が実行され一時停止となる()
    {
        Mail::fake();

        $response = $this->actingAs($this->adminUser, 'web')
            ->post("/{$this->company->url_code}/settings/company/withdraw", [
                'agreement_partner'      => '1',
                'agreement_transactions' => '1',
                'agreement_backup'       => '1',
                'agreement_users'        => '1',
                'agreement_period'       => '1',
                'password'               => 'AdminPass123!',
            ]);

        // 退会完了画面へリダイレクト
        $response->assertRedirect(route('withdraw.complete'));

        // 企業および全従業員が「suspended」に更新されていること
        $this->assertEquals('suspended', $this->company->fresh()->status);
        $this->assertEquals('suspended', $this->adminUser->fresh()->status);
        $this->assertEquals('suspended', $this->staffUser->fresh()->status);

        // ログアウトされていること
        $this->assertFalse(Auth::guard('web')->check());

        // 管理者へ退会通知メールが送信され、Reply-ToがMAIL_OFFICE_REPLYであること
        Mail::assertSent(CompanyWithdrawMail::class, function (CompanyWithdrawMail $mail) {
            $expectedReply = config('mail.office.reply', env('MAIL_OFFICE_REPLY', 'officem@meshu.org'));
            $hasTo = $mail->hasTo('withdraw-admin@test.com');
            $hasReply = $mail->hasReplyTo($expectedReply) ||
                collect($mail->envelope()->replyTo)->contains(fn($a) => $a->address === $expectedReply);
            return $hasTo && $hasReply;
        });

        // 事務局へ退会通知メールが送信されていること（Main宛て、SubはBCC）
        Mail::assertSent(AdminCompanyWithdrawNotificationMail::class, function (AdminCompanyWithdrawNotificationMail $mail) {
            $expectedOfficeMain = config('mail.office.main', 'noreply@meshu.org');
            $expectedOfficeSub  = config('mail.office.sub');

            $matchesTo = $mail->hasTo($expectedOfficeMain);
            $matchesBcc = true;
            if ($expectedOfficeSub && $expectedOfficeSub !== $expectedOfficeMain) {
                $matchesBcc = $mail->hasBcc($expectedOfficeSub);
            }

            return $matchesTo && $matchesBcc && $mail->company->id === $this->company->id;
        });
    }

    /**
     * 退会完了画面（公開ページ）が正常に表示されること
     */
    #[Test]
    public function 退会完了画面が正常に表示される()
    {
        $response = $this->withSession([
            'withdrawn_company_name' => '退会テスト株式会社',
            'withdrawn_admin_email' => 'withdraw-admin@test.com',
        ])->get('/withdraw/complete');

        $response->assertStatus(200);
        $response->assertSee('退会申請を受け付けました');
        $response->assertSee('退会テスト株式会社');
        $response->assertSee('30日間はデータ保持期間');
        $response->assertSee('officem@meshu.org');
    }

    /**
     * 自社が退会（suspended）した場合、取引先企業の購入・販売画面から該当取引が非表示となること
     */
    #[Test]
    public function 自社退会後は取引先画面から該当取引データが非表示となる()
    {
        // 相手先企業（販売側）およびユーザー
        $partnerCompany = Company::create([
            'url_code'         => 'partner-seller-corp',
            'name'             => 'パートナー販売企業',
            'abbreviated_name' => 'パートナー販売',
            'abbreviated_kana' => 'パートナーハンバイ',
            'tel'              => '03-1234-5678',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '丸の内1-1',
            'status'           => 'active',
        ]);

        $partnerUser = User::create([
            'company_id'      => $partnerCompany->id,
            'email'           => 'partner-seller@test.com',
            'password_hash'   => Hash::make('Pass123!'),
            'user_name'       => 'パートナー 担当者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        $link = \App\Models\CompanyLink::create([
            'buyer_company_id'  => $this->company->id,
            'seller_company_id' => $partnerCompany->id,
            'status'            => 'approved',
        ]);

        $tx = \App\Models\Transaction::create([
            'company_link_id' => $link->id,
            'status'          => 'estimate_request',
            'title'           => '非表示検証用案件ABC',
            'is_tax_exempt'   => 0,
            'tax_rate'        => 10,
        ]);

        // 1. 退会前：相手先企業（販売側）は一覧および詳細で閲覧可能
        $beforeListResponse = $this->actingAs($partnerUser, 'web')->get('/sell');
        $beforeListResponse->assertStatus(200);
        $beforeListResponse->assertSee('非表示検証用案件ABC');

        $beforeDetailResponse = $this->actingAs($partnerUser, 'web')->get("/sell/detail/{$tx->id}");
        $beforeDetailResponse->assertStatus(200);

        // 2. 自社が退会申請（status = suspended に変更）
        $this->company->update(['status' => 'suspended']);

        // 3. 退会後：相手先企業の販売一覧から非表示になること
        $afterListResponse = $this->actingAs($partnerUser, 'web')->get('/sell');
        $afterListResponse->assertStatus(200);
        $afterListResponse->assertDontSee('非表示検証用案件ABC');

        // 4. 取引詳細にアクセスした場合、403（権限なし/データ非存在）となること
        $afterDetailResponse = $this->actingAs($partnerUser, 'web')->get("/sell/detail/{$tx->id}");
        $afterDetailResponse->assertStatus(403);
    }
}
