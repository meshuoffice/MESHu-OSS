<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * usersテーブルのemail, password_hash, user_name, user_name_kana, department, positionをtext型に変更
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
                ALTER COLUMN email TYPE text,
                ALTER COLUMN password_hash TYPE text,
                ALTER COLUMN user_name TYPE text,
                ALTER COLUMN user_name_kana TYPE text,
                ALTER COLUMN department TYPE text,
                ALTER COLUMN position TYPE text;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE users
                ALTER COLUMN email TYPE varchar(255),
                ALTER COLUMN password_hash TYPE varchar(255),
                ALTER COLUMN user_name TYPE varchar(100),
                ALTER COLUMN user_name_kana TYPE varchar(100),
                ALTER COLUMN department TYPE varchar(100),
                ALTER COLUMN position TYPE varchar(100);
        ");
    }
};
