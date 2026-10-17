<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * companiesテーブルに適格請求書発行事業者登録番号（invoice_number: VARCHAR(14)）およびカラムコメントを追加
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('invoice_number', 14)->nullable()->after('corporate_number')->comment('適格請求書発行事業者登録番号(T+13桁)');
        });

        // PostgreSQL カラムコメントの明示的付与
        DB::statement("COMMENT ON COLUMN companies.invoice_number IS '適格請求書発行事業者登録番号(T+13桁)';");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('invoice_number');
        });
    }
};
