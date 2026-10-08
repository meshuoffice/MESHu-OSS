<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestCompanySeeder extends Seeder
{
    /**
     * テスト用企業・ユーザーデータの投入
     */
    public function run(): void
    {
        // 1. テスト企業A（買手想定）
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
                'address'          => '千代田1-1',
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
                'is_all_assigned' => 0,
            ]
        );

        // 2. テスト企業B（売手想定）
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
                'address'          => '梅田1-1',
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
                'is_all_assigned' => 0,
            ]
        );
    }
}
