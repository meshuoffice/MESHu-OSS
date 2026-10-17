<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManualTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $company;
    protected User $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create([
            'url_code'         => 'manual-test-corp',
            'name'             => 'マニュアル検証企業',
            'abbreviated_name' => 'マニュアル検証',
            'abbreviated_kana' => 'マニュアルケンショウ',
            'tel'              => '03-8888-0000',
            'zip_code'         => '1000001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'status'           => 'active',
        ]);

        $this->user = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'manual-staff@test.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => 'マニュアル 一般社員',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);

        $this->adminUser = User::create([
            'company_id'      => $this->company->id,
            'email'           => 'manual-admin@test.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => 'マニュアル 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
        ]);
    }

    /**
     * 未ログインユーザーはログイン画面へリダイレクトされること
     */
    #[Test]
    public function 未ログインユーザーがマニュアル関連画面にアクセスした場合はリダイレクトされる()
    {
        $this->get('/manual')->assertRedirect();
        $this->get('/manual/faq')->assertRedirect();
        $this->get('/manual/getting-started')->assertRedirect();
        $this->get('/help')->assertRedirect();
        $this->get('/faq')->assertRedirect();
    }

    /**
     * ショートカットURL（/help, /faq）が適切にリダイレクトされること
     */
    #[Test]
    public function ショートカットURLが適切にリダイレクトされる()
    {
        $this->actingAs($this->user, 'web');

        $this->get('/help')->assertRedirect(route('tenant.manual.index'));
        $this->get('/faq')->assertRedirect(route('tenant.manual.faq'));
    }

    /**
     * ログインユーザーがマニュアルトップ画面を正常に表示できること
     */
    #[Test]
    public function ログインユーザーがマニュアルトップ画面を正常に表示できる()
    {
        $response = $this->actingAs($this->user, 'web')->get('/manual');

        $response->assertStatus(200);
        $response->assertViewIs('manual.index');
        $response->assertSee('MESHu ヘルプ・操作マニュアル');
        $response->assertSee('はじめてのMESHu');
        $response->assertSee('取引先との「つながり」(MESH)');
        $response->assertSee('取引管理（購入・販売・帳票）');
        $response->assertSee('チャットとお知らせ');
        $response->assertSee('自社情報と従業員アカウント管理');
        $response->assertSee('セキュリティと事務局問合せ');
        $response->assertSee('よくあるご質問 (FAQ) ピックアップ');
    }

    /**
     * キーワード検索が正しく機能し、該当するマニュアルとFAQが絞り込まれること
     */
    #[Test]
    public function キーワード検索が正しく機能し該当項目が抽出される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/manual?q=注文');

        $response->assertStatus(200);
        $response->assertSee('「注文」の検索結果');
        $response->assertSee('操作マニュアル');
        $response->assertSee('取引管理');
    }

    /**
     * 検索結果がゼロ件の場合に適切なメッセージが表示されること
     */
    #[Test]
    public function 検索キーワードに一致しない場合はゼロ件メッセージが表示される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/manual?q=無効なあり得ないキーワードxyz999');

        $response->assertStatus(200);
        $response->assertSee('該当する項目が見つかりませんでした');
    }

    /**
     * 全6カテゴリの詳細マニュアル画面が正常に表示されること
     */
    #[Test]
    public function 全カテゴリの詳細マニュアル画面が正常に表示される()
    {
        $categories = [
            'getting-started',
            'links',
            'transactions',
            'chat-notices',
            'company-users',
            'security-contact',
        ];

        foreach ($categories as $cat) {
            $response = $this->actingAs($this->user, 'web')->get("/manual/{$cat}");
            $response->assertStatus(200);
            $response->assertViewIs('manual.category');
            $response->assertSee('ワンポイントアドバイス');
        }
    }

    /**
     * 存在しないカテゴリにアクセスした場合は404エラーとなること
     */
    #[Test]
    public function 存在しないカテゴリにアクセスした場合は404が返却される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/manual/non-existent-category');
        $response->assertStatus(404);
    }

    /**
     * FAQ一覧画面が正常に表示されること
     */
    #[Test]
    public function FAQ一覧画面が正常に表示される()
    {
        $response = $this->actingAs($this->user, 'web')->get('/manual/faq');

        $response->assertStatus(200);
        $response->assertViewIs('manual.faq');
        $response->assertSee('よくあるご質問 (FAQ)');
        $response->assertSee('Q. 取引先連携の申請を行いましたが、承認待ちが続いています。どうすればよいですか？');
        $response->assertSee('Q. 見積依頼を経由せずに、直接発注（直接注文）することはできますか？');
    }

    /**
     * FAQカテゴリ絞り込みおよび検索が正常に動作すること
     */
    #[Test]
    public function FAQのカテゴリ絞り込みおよび検索が動作する()
    {
        // カテゴリ絞り込み
        $catResponse = $this->actingAs($this->user, 'web')->get('/manual/faq?category=transactions');
        $catResponse->assertStatus(200);
        $catResponse->assertSee('見積依頼を経由せずに、直接発注');

        // キーワード検索（電話番号）
        $searchResponse = $this->actingAs($this->user, 'web')->get('/manual/faq?q=電話番号');
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('自社の住所や電話番号');

        // キーワード検索（インボイス）
        $invoiceResponse = $this->actingAs($this->user, 'web')->get('/manual/faq?q=インボイス');
        $invoiceResponse->assertStatus(200);
        $invoiceResponse->assertSee('適格請求書発行事業者登録番号');
    }

    /**
     * サイドバーメニューおよびユーザーメニューに操作マニュアルへのリンクが存在すること
     */
    #[Test]
    public function 各種ナビゲーションに操作マニュアルリンクが含まれている()
    {
        $response = $this->actingAs($this->user, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('tenant.manual.index'));
        $response->assertSee('操作マニュアル');
    }
}
