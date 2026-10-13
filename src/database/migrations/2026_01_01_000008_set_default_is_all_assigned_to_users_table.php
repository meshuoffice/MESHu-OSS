<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * mshdev（CI4）準拠: users.is_all_assigned の初期値を 1 (全担当) に変更
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('is_all_assigned')->default(1)->change();
        });

        // 明示的な PostgreSQL コメントの更新
        DB::statement("COMMENT ON COLUMN users.is_all_assigned IS '全取引先担当フラグ（1: 全担当[デフォルト], 0: 個別担当分け）'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('is_all_assigned')->default(0)->change();
        });

        DB::statement("COMMENT ON COLUMN users.is_all_assigned IS '取引先担当区分（0: 全担当, 1: 担当分け）'");
    }
};
