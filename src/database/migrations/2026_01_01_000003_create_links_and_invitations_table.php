<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. company_links テーブル（企業間取引リンク）
        Schema::create('company_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('seller_company_id')->constrained('companies')->onDelete('cascade');
            $table->string('status', 20)->default('pending'); // pending, approved, denied
            $table->timestamps();

            $table->unique(['buyer_company_id', 'seller_company_id']);
        });

        // company_links テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE company_links IS '企業間の受発注・取引連携ルート（エッジ）を管理するテーブル'");
        DB::statement("COMMENT ON COLUMN company_links.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN company_links.buyer_company_id IS '購入（申請）側企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN company_links.seller_company_id IS '販売（受取）側企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN company_links.status IS '取引リンクステータス（pending: 申請中, approved: 承認, denied: 否認）'");
        DB::statement("COMMENT ON COLUMN company_links.created_at IS '登録日時'");
        DB::statement("COMMENT ON COLUMN company_links.updated_at IS '更新日時'");

        // 自己取引禁止制約 & 部分インデックス
        DB::statement("ALTER TABLE company_links ADD CONSTRAINT check_buyer_ne_seller CHECK (buyer_company_id <> seller_company_id)");
        DB::statement("CREATE INDEX idx_company_links_buyer_seller_approved ON company_links(buyer_company_id, seller_company_id) WHERE status = 'approved'");
        DB::statement("CREATE INDEX idx_company_links_seller_approved ON company_links(seller_company_id) WHERE status = 'approved'");

        // 2. user_assigned_links テーブル（担当取引先割当）
        Schema::create('user_assigned_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_link_id')->constrained('company_links')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'company_link_id']);
        });

        // user_assigned_links テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE user_assigned_links IS '従業員ごとの担当取引先（company_link）の個別割当テーブル'");
        DB::statement("COMMENT ON COLUMN user_assigned_links.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN user_assigned_links.user_id IS '担当従業員ユーザーID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN user_assigned_links.company_link_id IS '取引リンクID（company_links.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN user_assigned_links.created_at IS '登録日時'");
        DB::statement("COMMENT ON COLUMN user_assigned_links.updated_at IS '更新日時'");

        // 3. invitations テーブル（未登録企業招待履歴）
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('invitation_code', 64)->unique();
            $table->string('buysell', 20)->default('buyer');
            $table->text('email');
            $table->text('mail_subject');
            $table->text('mail_detail');
            $table->text('company_name');
            $table->string('corporate_number', 13)->nullable();
            $table->text('user_name')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });

        // invitations テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE invitations IS '未登録企業への招待送信履歴テーブル'");
        DB::statement("COMMENT ON COLUMN invitations.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN invitations.company_id IS '招待元企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN invitations.user_id IS '招待送信者ユーザーID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN invitations.invitation_code IS '一意の招待コード'");
        DB::statement("COMMENT ON COLUMN invitations.buysell IS '登録時の自社属性 (buyer / seller)'");
        DB::statement("COMMENT ON COLUMN invitations.email IS '送信先メールアドレス'");
        DB::statement("COMMENT ON COLUMN invitations.mail_subject IS '招待メール件名'");
        DB::statement("COMMENT ON COLUMN invitations.mail_detail IS '招待メール本文'");
        DB::statement("COMMENT ON COLUMN invitations.company_name IS '招待先 企業名'");
        DB::statement("COMMENT ON COLUMN invitations.corporate_number IS '招待先 法人番号 (13桁)'");
        DB::statement("COMMENT ON COLUMN invitations.user_name IS '招待先 管理者名'");
        DB::statement("COMMENT ON COLUMN invitations.registered_at IS '招待経由での本登録完了日時'");
        DB::statement("COMMENT ON COLUMN invitations.created_at IS '招待作成日時'");
        DB::statement("COMMENT ON COLUMN invitations.updated_at IS '更新日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitations');
        Schema::dropIfExists('user_assigned_links');
        Schema::dropIfExists('company_links');
    }
};
