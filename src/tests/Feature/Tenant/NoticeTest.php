<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\CompanyLink;
use App\Models\Notice;
use App\Models\NoticeAttachment;
use App\Models\Owner;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected Company $companyC;
    protected User $userA;
    protected Owner $adminOwner;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = uniqid();

        // 事務局管理者
        $this->adminOwner = Owner::create([
            'email'         => "admin-{$suffix}@example.com",
            'password_hash' => Hash::make('password123'),
            'name'          => '事務局管理者',
            'role'          => 'admin',
            'status'        => 'active',
        ]);

        // 自社企業A (非認証)
        $this->companyA = Company::create([
            'url_code'         => "notice-a-{$suffix}",
            'corporate_number' => '1111111111111',
            'name'             => 'テスト企業A',
            'abbreviated_name' => '企業A',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        $this->userA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => "user-a-{$suffix}@example.com",
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '担当者A',
            'user_name_kana'  => 'タントウシャエー',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        // 取引先企業B (つながり承認済み)
        $this->companyB = Company::create([
            'url_code'         => "notice-b-{$suffix}",
            'corporate_number' => '2222222222222',
            'name'             => 'つながり企業B',
            'abbreviated_name' => '企業B',
            'tel'              => '03-3333-4444',
            'zip_code'         => '100-0002',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);

        CompanyLink::create([
            'buyer_company_id'  => $this->companyA->id,
            'seller_company_id' => $this->companyB->id,
            'status'            => 'approved',
        ]);

        // 外部企業C (つながり未承認・関係なし)
        $this->companyC = Company::create([
            'url_code'         => "notice-c-{$suffix}",
            'corporate_number' => '3333333333333',
            'name'             => '無関係企業C',
            'abbreviated_name' => '企業C',
            'tel'              => '03-5555-6666',
            'zip_code'         => '100-0003',
            'prefecture_code'  => 13,
            'status'           => 'active',
        ]);
    }

    #[Test]
    public function 未ログイン時は一覧画面にアクセスできずリダイレクトされる(): void
    {
        $response = $this->get(route('tenant.notice.index'));
        $response->assertRedirect();
    }

    #[Test]
    public function ダッシュボードおよび一覧画面に事務局全体お知らせが表示される(): void
    {
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => 'システムメンテナンスのお知らせ',
            'content'             => '今週末に定期メンテナンスを実施します。',
            'is_important'        => true,
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        // ダッシュボード確認
        $dashResponse = $this->actingAs($this->userA, 'web')->get(route('dashboard'));
        $dashResponse->assertOk();
        $dashResponse->assertSee('システムメンテナンスのお知らせ');
        $dashResponse->assertSee('重要');

        // 一覧画面確認
        $listResponse = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index'));
        $listResponse->assertOk();
        $listResponse->assertSee('システムメンテナンスのお知らせ');
        $listResponse->assertSee('事務局');
    }

    #[Test]
    public function 認証企業専用の事務局お知らせは非認証企業には表示されず認証企業には表示される(): void
    {
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'verified',
            'title'               => '認証企業限定キャンペーン',
            'content'             => '認証企業様向け特典のご案内です。',
            'is_important'        => false,
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        // 1. 非認証企業Aには表示されない
        $response = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index'));
        $response->assertOk();
        $response->assertDontSee('認証企業限定キャンペーン');

        // 2. 企業Bに認証バッジを付与し、企業Bのユーザーでアクセスすると表示される
        CompanyBadge::create([
            'company_id'        => $this->companyB->id,
            'verifier_owner_id' => $this->adminOwner->id,
            'status'            => 'active',
            'signature'         => 'dummy_signature_hash',
        ]);

        $userB = User::create([
            'company_id'      => $this->companyB->id,
            'email'           => 'user-b-' . uniqid() . '@example.com',
            'password_hash'   => Hash::make('password123'),
            'user_name'       => '担当者B',
            'user_name_kana'  => 'タントウシャビー',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        $responseVerified = $this->actingAs($userB, 'web')->get(route('tenant.notice.index'));
        $responseVerified->assertOk();
        $responseVerified->assertSee('認証企業限定キャンペーン');
    }

    #[Test]
    public function つながり承認企業の発信お知らせが表示され未関係企業のものは表示されない(): void
    {
        // 承認企業Bの発信
        Notice::create([
            'sender_type'  => 'company',
            'company_id'   => $this->companyB->id,
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '企業Bの新製品発表',
            'content'      => '新製品の取り扱いを開始いたしました。',
            'is_important' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // 無関係企業Cの発信
        Notice::create([
            'sender_type'  => 'company',
            'company_id'   => $this->companyC->id,
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '企業Cの独自案内',
            'content'      => '他社向けのお知らせです。',
            'is_important' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index'));
        $response->assertOk();
        $response->assertSee('企業Bの新製品発表');
        $response->assertSee('つながり企業B');
        $response->assertDontSee('企業Cの独自案内');
    }

    #[Test]
    public function 自社発信のお知らせが表示される(): void
    {
        Notice::create([
            'sender_type'  => 'company',
            'company_id'   => $this->companyA->id,
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '自社からの全社連絡',
            'content'      => '自社発信のお知らせ内容です。',
            'is_important' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index'));
        $response->assertOk();
        $response->assertSee('自社からの全社連絡');
    }

    #[Test]
    public function キーワード検索および発信元絞り込みが正常に機能する(): void
    {
        Notice::create([
            'sender_type'  => 'admin',
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '年末年始の営業について',
            'content'      => '営業スケジュールのご案内。',
            'is_important' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        Notice::create([
            'sender_type'  => 'company',
            'company_id'   => $this->companyB->id,
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '夏季休業のご案内',
            'content'      => 'お盆期間中の休業について。',
            'is_important' => false,
            'is_published' => true,
            'published_at' => now(),
        ]);

        // キーワード検索
        $resKeyword = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index', ['keyword' => '年末年始']));
        $resKeyword->assertOk();
        $resKeyword->assertSee('年末年始の営業について');
        $resKeyword->assertDontSee('夏季休業のご案内');

        // 発信元絞り込み（admin）
        $resAdmin = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index', ['sender_type' => 'admin']));
        $resAdmin->assertOk();
        $resAdmin->assertSee('年末年始の営業について');
        $resAdmin->assertDontSee('夏季休業のご案内');

        // 発信元絞り込み（company）
        $resCompany = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.index', ['sender_type' => 'company']));
        $resCompany->assertOk();
        $resCompany->assertSee('夏季休業のご案内');
        $resCompany->assertDontSee('年末年始の営業について');
    }

    #[Test]
    public function お知らせ詳細画面が表示され閲覧数がカウントアップされる(): void
    {
        $notice = Notice::create([
            'sender_type'         => 'admin',
            'created_by_admin_id' => $this->adminOwner->id,
            'category'            => 'info',
            'target_scope'        => 'all',
            'title'               => '重要なお知らせ詳細テスト',
            'content'             => 'これは本文の詳細内容テキストです。',
            'view_count'          => 5,
            'is_important'        => true,
            'is_published'        => true,
            'published_at'        => now(),
        ]);

        $response = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.show', ['id' => $notice->id]));
        $response->assertOk();
        $response->assertSee('重要なお知らせ詳細テスト');
        $response->assertSee('これは本文の詳細内容テキストです。');
        $response->assertSee('閲覧数: 6 回');

        // DBでも +1 されていることを確認
        $this->assertEquals(6, $notice->fresh()->view_count);
    }

    #[Test]
    public function 権限外または非公開のお知らせ詳細へアクセスした場合は404エラーとなる(): void
    {
        // 非公開のお知らせ
        $draftNotice = Notice::create([
            'sender_type'  => 'admin',
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '下書きお知らせ',
            'content'      => '公開前です。',
            'is_published' => false,
        ]);

        // 無関係企業Cのお知らせ
        $secretNotice = Notice::create([
            'sender_type'  => 'company',
            'company_id'   => $this->companyC->id,
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '企業Cの社内連絡',
            'content'      => '部外者アクセス不可。',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $resDraft = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.show', ['id' => $draftNotice->id]));
        $resDraft->assertNotFound();

        $resSecret = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.show', ['id' => $secretNotice->id]));
        $resSecret->assertNotFound();
    }

    #[Test]
    public function 添付ファイルダウンロードが正常に行える(): void
    {
        $notice = Notice::create([
            'sender_type'  => 'admin',
            'category'     => 'info',
            'target_scope' => 'all',
            'title'        => '添付ファイル付きお知らせ',
            'content'      => '資料を添付しました。',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $fileContent = 'PDF_DUMMY_CONTENT';
        $attachment = NoticeAttachment::create([
            'notice_id'  => $notice->id,
            'file_name'  => 'document.pdf',
            'mime_type'  => 'application/pdf',
            'file_size'  => strlen($fileContent),
            'file_data'  => $fileContent,
        ]);

        $response = $this->actingAs($this->userA, 'web')->get(route('tenant.notice.attachment', ['id' => $attachment->id]));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertEquals($fileContent, $response->getContent());
    }
}
