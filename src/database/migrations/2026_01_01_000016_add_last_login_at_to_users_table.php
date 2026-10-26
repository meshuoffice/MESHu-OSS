<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * usersテーブルに最終ログイン日時（last_login_at: TIMESTAMP）およびカラムコメントを追加
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_login_at')->nullable()->after('status')->comment('最終ログイン日時');
        });

        // PostgreSQL カラムコメントの明示的付与
        DB::statement("COMMENT ON COLUMN users.last_login_at IS '最終ログイン日時';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('last_login_at');
        });
    }
};
