<?php

namespace Tests\Feature\Tenant;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminUserA;
    protected User $staffUserA;

    protected function setUp(): void
    {
        parent::setUp();

        // テスト企業A
        $this->companyA = Company::create([
            'url_code'         => 'company-a',
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '千代田1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
            'status'           => 'active',
        ]);

        // テスト企業B (他社)
        $this->companyB = Company::create([
            'url_code'         => 'company-b',
            'name'             => '株式会社企業B',
            'abbreviated_name' => '企業B',
            'abbreviated_kana' => 'キギョウビー',
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

        // 企業Aの管理者 (role=admin)
        $this->adminUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => 'admin-a@example.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A 管理者',
            'role'            => 'admin',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);

        // 企業Aの一般スタッフ (role=staff)
        $this->staffUserA = User::create([
            'company_id'      => $this->companyA->id,
            'email'           => 'staff-a@example.com',
            'password_hash'   => Hash::make('Password123'),
            'user_name'       => '企業A スタッフ',
            'role'            => 'staff',
            'status'          => 'active',
            'is_all_assigned' => 1,
            'notify_trade'    => true,
            'notify_comment'  => true,
            'notify_chat'     => true,
        ]);
    }

    #[Test]
    public function 管理者は自社の企業情報編集画面を表示できる(): void
    {
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/{$this->companyA->url_code}/settings/company");

        $response->assertStatus(200);
        $response->assertSee('企業情報メンテナンス');
        $response->assertSee('株式会社企業A');
        $response->assertSee('03-1111-2222');
    }

    #[Test]
    public function 他社のurl_codeを指定した場合は403で遮断される_マルチテナント隔離(): void
    {
        // 企業Aの管理者が企業BのURLへアクセス
        $response = $this->actingAs($this->adminUserA, 'web')
            ->get("/{$this->companyB->url_code}/settings/company");

        $response->assertStatus(403);
    }

    #[Test]
    public function 一般社員は企業情報メンテナンスにアクセスできず403となる_権限制御(): void
    {
        $response = $this->actingAs($this->staffUserA, 'web')
            ->get("/{$this->companyA->url_code}/settings/company");

        $response->assertStatus(403);
    }

    #[Test]
    public function 未認証ユーザーはログイン画面またはリダイレクトされる(): void
    {
        $response = $this->get("/{$this->companyA->url_code}/settings/company");

        $response->assertRedirect("/{$this->companyA->url_code}/login");
    }

    #[Test]
    public function 入力値が正常な場合確認画面が表示される(): void
    {
        $postData = [
            'name'             => '株式会社企業A 更新後',
            'abbreviated_name' => '企業A新',
            'abbreviated_kana' => 'キギョウエーシン',
            'url_code'         => 'company-a',
            'corporate_number' => '1234567890123',
            'tel'              => '03-3333-4444',
            'homepage'         => 'https://company-a.example.com',
            'zip_code'         => '100-0002',
            'prefecture_code'  => 13,
            'city'             => '千代田区皇居外苑',
            'address'          => '1-1 サンプルタワー',
            'tax_rate'         => 10,
            'amount_rounding'  => 'floor',
            'tax_rounding'     => 'ceil',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/{$this->companyA->url_code}/settings/company/confirm", $postData);

        $response->assertStatus(200);
        $response->assertSee('変更内容確認');
        $response->assertSee('株式会社企業A 更新後');
        $response->assertSee('切り捨て');
        $response->assertSee('切り上げ');
    }

    #[Test]
    public function 他社と重複するurl_codeはバリデーションエラーとなる(): void
    {
        $postData = [
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'url_code'         => 'company-b', // 他社Bのコードと衝突
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->from("/{$this->companyA->url_code}/settings/company")
            ->post("/{$this->companyA->url_code}/settings/company/confirm", $postData);

        $response->assertRedirect("/{$this->companyA->url_code}/settings/company");
        $response->assertSessionHasErrors(['url_code']);
    }

    #[Test]
    public function 不正な形式の電話番号はバリデーションエラーとなる(): void
    {
        $postData = [
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'url_code'         => 'company-a',
            'tel'              => '00-0000-11aa', // 不正な電話番号
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->from("/{$this->companyA->url_code}/settings/company")
            ->post("/{$this->companyA->url_code}/settings/company/confirm", $postData);

        $response->assertRedirect("/{$this->companyA->url_code}/settings/company");
        $response->assertSessionHasErrors([
            'tel' => '電話番号は正しい形式（例：03-1234-5678）で入力してください。',
        ]);
    }

    #[Test]
    public function 不正な形式の郵便番号はバリデーションエラーとなる(): void
    {
        $postData = [
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'url_code'         => 'company-a',
            'tel'              => '03-1111-2222',
            'zip_code'         => '763-001a', // 不正な郵便番号
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->from("/{$this->companyA->url_code}/settings/company")
            ->post("/{$this->companyA->url_code}/settings/company/confirm", $postData);

        $response->assertRedirect("/{$this->companyA->url_code}/settings/company");
        $response->assertSessionHasErrors([
            'zip_code' => '郵便番号は7桁の半角数字（例：100-0001）で入力してください。',
        ]);
    }

    #[Test]
    public function 法人番号の桁数や半角数字不正時に適切なエラーメッセージが返る(): void
    {
        $postData = [
            'name'             => '株式会社企業A',
            'abbreviated_name' => '企業A',
            'abbreviated_kana' => 'キギョウエー',
            'url_code'         => 'company-a',
            'corporate_number' => '12345', // 13桁未満
            'tel'              => '03-1111-2222',
            'zip_code'         => '100-0001',
            'prefecture_code'  => 13,
            'city'             => '千代田区',
            'address'          => '1-1',
            'tax_rate'         => 10,
            'amount_rounding'  => 'round',
            'tax_rounding'     => 'round',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->from("/{$this->companyA->url_code}/settings/company")
            ->post("/{$this->companyA->url_code}/settings/company/confirm", $postData);

        $response->assertRedirect("/{$this->companyA->url_code}/settings/company");
        $response->assertSessionHasErrors([
            'corporate_number' => '法人番号は13桁の半角数字で入力してください。',
        ]);
    }

    #[Test]
    public function 企業情報更新が正常に実行されDBに反映される(): void
    {
        $updateData = [
            'name'             => '株式会社企業A 確定更新',
            'abbreviated_name' => '企業A更新',
            'abbreviated_kana' => 'キギョウエーコウシン',
            'url_code'         => 'company-a-new',
            'corporate_number' => '9876543210123',
            'tel'              => '03-9999-0000',
            'homepage'         => 'https://new.company-a.com',
            'zip_code'         => '102-0082',
            'prefecture_code'  => 13,
            'city'             => '千代田区一番町',
            'address'          => '2-2',
            'tax_rate'         => 8,
            'amount_rounding'  => 'ceil',
            'tax_rounding'     => 'floor',
        ];

        $response = $this->actingAs($this->adminUserA, 'web')
            ->post("/{$this->companyA->url_code}/settings/company/update", $updateData);

        // 新しい url_code の完了画面へリダイレクト
        $response->assertRedirect("/company-a-new/settings/company/complete");

        // DBの更新検証
        $this->assertDatabaseHas('companies', [
            'id'               => $this->companyA->id,
            'name'             => '株式会社企業A 確定更新',
            'url_code'         => 'company-a-new',
            'tel'              => '03-9999-0000',
            'tax_rate'         => 8,
            'amount_rounding'  => 'ceil',
            'tax_rounding'     => 'floor',
        ]);
    }
}
