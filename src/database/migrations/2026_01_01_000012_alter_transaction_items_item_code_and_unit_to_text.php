<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * transaction_itemsテーブルのitem_code, unitカラムをtext型に変更
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE transaction_items
                ALTER COLUMN item_code TYPE text,
                ALTER COLUMN unit DROP DEFAULT,
                ALTER COLUMN unit TYPE text,
                ALTER COLUMN unit SET DEFAULT '個';
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE transaction_items
                ALTER COLUMN item_code TYPE varchar(100),
                ALTER COLUMN unit DROP DEFAULT,
                ALTER COLUMN unit TYPE varchar(20),
                ALTER COLUMN unit SET DEFAULT '個';
        ");
    }
};
