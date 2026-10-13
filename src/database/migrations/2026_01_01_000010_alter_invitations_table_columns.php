<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * invitationsテーブルのemail, mail_subject, company_name, user_nameをtext型に変更
     * user_nameのカラムコメントを「招待先 管理者名」に変更
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE invitations
                ALTER COLUMN email TYPE text,
                ALTER COLUMN mail_subject TYPE text,
                ALTER COLUMN company_name TYPE text,
                ALTER COLUMN user_name TYPE text;
        ");

        DB::statement("COMMENT ON COLUMN invitations.user_name IS '招待先 管理者名'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE invitations
                ALTER COLUMN email TYPE varchar(255),
                ALTER COLUMN mail_subject TYPE varchar(255),
                ALTER COLUMN company_name TYPE varchar(255),
                ALTER COLUMN user_name TYPE varchar(100);
        ");

        DB::statement("COMMENT ON COLUMN invitations.user_name IS '招待先 担当者名'");
    }
};
