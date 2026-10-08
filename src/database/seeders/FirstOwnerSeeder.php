<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FirstOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('FIRST_OWNER_EMAIL', 'admin@meshu.org');
        $password = env('FIRST_OWNER_PASSWORD', 'ChangeMe1234!');
        $name     = env('FIRST_OWNER_NAME', 'システム最高管理者');

        // 既に同メールアドレスの事務局が存在しない場合のみ登録
        $exists = DB::table('owners')->where('email', $email)->exists();

        if (!$exists) {
            DB::table('owners')->insert([
                'name'          => $name,
                'email'         => $email,
                'password_hash' => Hash::make($password),
                'role'          => 'admin',
                'status'        => 'active',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            $this->command->info("First owner created successfully: {$email}");
        } else {
            $this->command->info("First owner already exists: {$email}");
        }
    }
}
