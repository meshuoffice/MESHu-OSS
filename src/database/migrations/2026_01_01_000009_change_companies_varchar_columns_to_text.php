<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * mshdev（CI4）完全準拠：companiesテーブルの該当VARCHARカラムをTEXT型に変更
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN url_code TYPE text,
                ALTER COLUMN name TYPE text,
                ALTER COLUMN abbreviated_name TYPE text,
                ALTER COLUMN abbreviated_kana TYPE text,
                ALTER COLUMN sub_name TYPE text,
                ALTER COLUMN zip_code TYPE text,
                ALTER COLUMN city TYPE text,
                ALTER COLUMN address TYPE text,
                ALTER COLUMN homepage TYPE text,
                ALTER COLUMN tel TYPE text;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN url_code TYPE varchar(50),
                ALTER COLUMN name TYPE varchar(255),
                ALTER COLUMN abbreviated_name TYPE varchar(100),
                ALTER COLUMN abbreviated_kana TYPE varchar(100),
                ALTER COLUMN sub_name TYPE varchar(100),
                ALTER COLUMN zip_code TYPE varchar(8),
                ALTER COLUMN city TYPE varchar(100),
                ALTER COLUMN address TYPE varchar(255),
                ALTER COLUMN homepage TYPE varchar(255),
                ALTER COLUMN tel TYPE varchar(20);
        ");
    }
};
