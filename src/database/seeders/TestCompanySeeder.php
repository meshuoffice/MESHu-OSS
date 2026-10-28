<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyBadge;
use App\Models\CompanyLink;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TestCompanySeeder extends Seeder
{
    /**
     * テスト用企業・ユーザー・過去6か月の商流推移サンプルデータの投入
     */
    public function run(): void
    {
        // 1. テスト企業A（買手想定: 株式会社サンプル商事）
        $buyerCompany = Company::firstOrCreate(
            ['url_code' => 'sample-buyer'],
            [
                'name'             => '株式会社サンプル商事',
                'abbreviated_name' => 'サンプル商事',
                'abbreviated_kana' => 'サンプルショウジ',
                'corporate_number' => '1234567890123',
                'zip_code'         => '100-0001',
                'prefecture_code'  => 13,
                'city'             => '千代田区',
                'address'          => '大手町1-2-3',
                'tel'              => '03-1234-5678',
                'tax_rate'         => 10,
                'amount_rounding'  => 'round',
                'tax_rounding'     => 'round',
                'status'           => 'active',
            ]
        );

        User::firstOrCreate(
            ['company_id' => $buyerCompany->id, 'email' => 'admin@buyer.example.com'],
            [
                'password_hash'   => Hash::make('Secret1234!'),
                'user_name'       => '買手 管理者',
                'user_name_kana'  => 'バイテ カンリシャ',
                'department'      => '購買部',
                'position'        => '部長',
                'role'            => 'admin',
                'status'          => 'active',
            ]
        );

        // 2. テスト企業B（売手想定: 株式会社テスト販売）
        $sellerCompany = Company::firstOrCreate(
            ['url_code' => 'sample-seller'],
            [
                'name'             => '株式会社テスト販売',
                'abbreviated_name' => 'テスト販売',
                'abbreviated_kana' => 'テストハンバイ',
                'corporate_number' => '9876543210987',
                'zip_code'         => '530-0001',
                'prefecture_code'  => 27,
                'city'             => '大阪市北区',
                'address'          => '梅田1-1-1',
                'tel'              => '06-9876-5432',
                'tax_rate'         => 10,
                'amount_rounding'  => 'round',
                'tax_rounding'     => 'round',
                'status'           => 'active',
            ]
        );

        User::firstOrCreate(
            ['company_id' => $sellerCompany->id, 'email' => 'admin@seller.example.com'],
            [
                'password_hash'   => Hash::make('Secret1234!'),
                'user_name'       => '売手 管理者',
                'user_name_kana'  => 'ウリテ カンリシャ',
                'department'      => '営業部',
                'position'        => '課長',
                'role'            => 'admin',
                'status'          => 'active',
            ]
        );

        // 売手企業にMESHu認証済みバッジを付与
        $firstOwnerId = DB::table('owners')->value('id') ?? 1;
        CompanyBadge::firstOrCreate(
            ['company_id' => $sellerCompany->id],
            [
                'verifier_owner_id' => $firstOwnerId,
                'status'            => 'active',
                'signature'         => hash('sha256', "MESHu:Badge:{$sellerCompany->id}:{$firstOwnerId}:demo-seed"),
            ]
        );

        // 3. 買手企業と売手企業の「承認済みつながり（Link）」を初期確立
        $link = CompanyLink::firstOrCreate(
            [
                'buyer_company_id'  => $buyerCompany->id,
                'seller_company_id' => $sellerCompany->id,
            ],
            [
                'status' => 'approved',
            ]
        );

        // 4. 過去6か月のサンプル取引（月数件程度）を投入
        $this->seedSampleTransactions($link);
    }

    /**
     * 過去6か月間の取引推移サンプルデータの投入
     * （全伝票件数と注文回答完了分の成約金額、歩留まりの推移が体感できるデータ）
     */
    private function seedSampleTransactions(CompanyLink $link): void
    {
        // すでに取引データが存在する場合は二重投入を防止
        if (Transaction::where('company_link_id', $link->id)->exists()) {
            return;
        }

        // 月別シナリオ定義 [月前, 総件数(引合含む), 成約件数, 成約目標合計額]
        $scenarios = [
            5 => ['total' => 3, 'success' => 2, 'amount' => 1500000], // 5か月前
            4 => ['total' => 4, 'success' => 3, 'amount' => 2400000], // 4か月前
            3 => ['total' => 3, 'success' => 2, 'amount' => 1800000], // 3か月前
            2 => ['total' => 4, 'success' => 3, 'amount' => 2100000], // 2か月前
            1 => ['total' => 3, 'success' => 1, 'amount' => 600000],  // 1か月前（引合3件あるが成約1件に低下）
            0 => ['total' => 2, 'success' => 1, 'amount' => 850000],  // 当月（直近数日前に起票あり・青バッジ）
        ];

        foreach ($scenarios as $monthsAgo => $cfg) {
            $baseDate = Carbon::now()->subMonths($monthsAgo);
            $totalCount   = $cfg['total'];
            $successCount = $cfg['success'];
            $targetAmount = $cfg['amount'];

            for ($k = 1; $k <= $totalCount; $k++) {
                // 当月の場合は直近3日〜5日前に起票、過去月は5〜25日の間で分散
                if ($monthsAgo === 0) {
                    $createdAt = Carbon::now()->subDays(max(1, 5 - $k * 2))->setTime(rand(9, 16), rand(10, 50));
                } else {
                    $day = min(28, 5 + $k * 6);
                    $createdAt = Carbon::create($baseDate->year, $baseDate->month, $day, rand(9, 17), rand(10, 50));
                }

                $isSuccess = $k <= $successCount;
                $status = $isSuccess ? 'order_accepted' : 'estimate_request';

                $total = $isSuccess && $successCount > 0 ? (int)($targetAmount / $successCount) : 0;
                $subtotal = (int)($total / 1.1);
                $tax = $total - $subtotal;

                $title = $isSuccess
                    ? "定期資材・部品納入案件 (" . $createdAt->format('Y/m') . "ロット-{$k})"
                    : "新規カスタムパーツ見積引合 (" . $createdAt->format('Y/m') . "-{$k})";

                $tx = Transaction::create([
                    'company_link_id' => $link->id,
                    'title'           => $title,
                    'status'          => $status,
                    'subtotal_amount' => $subtotal,
                    'tax_amount'      => $tax,
                    'total_amount'    => $total,
                    'delivery_date'   => $createdAt->copy()->addDays(14)->toDateString(),
                    'created_at'      => $createdAt,
                    'updated_at'      => $createdAt->copy()->addHours(2),
                ]);

                TransactionItem::create([
                    'transaction_id' => $tx->id,
                    'line_no'        => 1,
                    'item_name'      => '高精度工業用パーツ A型',
                    'item_code'      => 'PART-A-' . rand(100, 999),
                    'quantity'       => 100,
                    'unit'           => '個',
                    'unit_price'     => (int)($subtotal / 100),
                    'amount'         => $subtotal,
                    'created_at'     => $createdAt,
                    'updated_at'     => $createdAt,
                ]);
            }
        }
    }
}
