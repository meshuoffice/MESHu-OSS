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
        // 1. companies テーブル（企業・拠点）
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->foreignId('invitation_company_id')->nullable()->constrained('companies')->onDelete('set null');
            $table->string('url_code', 50)->unique();
            $table->string('corporate_number', 13)->nullable();
            $table->string('name', 255);
            $table->string('abbreviated_name', 100)->nullable();
            $table->string('abbreviated_kana', 100)->nullable();
            $table->string('sub_name', 100)->nullable();
            $table->string('zip_code', 8)->nullable();
            $table->smallInteger('prefecture_code')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('address', 255)->nullable();
            $table->string('homepage', 255)->nullable();
            $table->string('tel', 20)->nullable();
            $table->smallInteger('tax_rate')->default(10);
            $table->string('amount_rounding', 10)->default('round'); // round, floor, ceil
            $table->string('tax_rounding', 10)->default('round');    // round, floor, ceil
            $table->string('status', 20)->default('suspended');      // suspended, active, deleted
            $table->timestamps();

            $table->index('corporate_number');
            $table->index('parent_id');
        });

        // companies テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE companies IS '企業・拠点ノード情報を管理するテーブル'");
        DB::statement("COMMENT ON COLUMN companies.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN companies.parent_id IS '親企業ID（自己参照）。拠点・子会社管理用。親削除時はNULL'");
        DB::statement("COMMENT ON COLUMN companies.invitation_company_id IS '招待経由登録時の招待元企業ID'");
        DB::statement("COMMENT ON COLUMN companies.url_code IS '専用ログインURL用の識別子（システム全体でユニーク）'");
        DB::statement("COMMENT ON COLUMN companies.corporate_number IS '13桁の法人番号（拠点追加を許容するためUNIQUE制約なし）'");
        DB::statement("COMMENT ON COLUMN companies.name IS '企業名・法人名'");
        DB::statement("COMMENT ON COLUMN companies.abbreviated_name IS '法人名略称（法人格無し）'");
        DB::statement("COMMENT ON COLUMN companies.abbreviated_kana IS '法人名カナ（法人格無し：全角カナ）'");
        DB::statement("COMMENT ON COLUMN companies.sub_name IS '拠点名・区分名（例: 九州支社、滋賀工場）'");
        DB::statement("COMMENT ON COLUMN companies.zip_code IS '郵便番号（例：101-0063）'");
        DB::statement("COMMENT ON COLUMN companies.prefecture_code IS '都道府県コード（JIS X 0401規格準拠、1:北海道〜47:沖縄）'");
        DB::statement("COMMENT ON COLUMN companies.city IS '市区町村'");
        DB::statement("COMMENT ON COLUMN companies.address IS '番地建物名'");
        DB::statement("COMMENT ON COLUMN companies.homepage IS 'ホームページURL'");
        DB::statement("COMMENT ON COLUMN companies.tel IS '電話番号'");
        DB::statement("COMMENT ON COLUMN companies.tax_rate IS '標準消費税率（%）'");
        DB::statement("COMMENT ON COLUMN companies.amount_rounding IS '行金額計算の端数処理（round: 四捨五入, floor: 切捨て, ceil: 切上げ）'");
        DB::statement("COMMENT ON COLUMN companies.tax_rounding IS '消費税計算の端数処理（round: 四捨五入, floor: 切捨て, ceil: 切上げ）'");
        DB::statement("COMMENT ON COLUMN companies.status IS '企業の稼働ステータス（suspended: 停止中, active: 稼働, deleted: 削除済）'");
        DB::statement("COMMENT ON COLUMN companies.created_at IS '登録日時'");
        DB::statement("COMMENT ON COLUMN companies.updated_at IS '更新日時'");

        // 2. company_badges テーブル（認証バッジ）
        Schema::create('company_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->onDelete('cascade');
            $table->foreignId('verifier_owner_id')->constrained('owners')->onDelete('restrict');
            $table->string('status', 50)->default('active');
            $table->text('signature');
            $table->timestamps();
        });

        // company_badges テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE company_badges IS '企業の実在性を証明する認証バッジの付与情報を管理するテーブル'");
        DB::statement("COMMENT ON COLUMN company_badges.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN company_badges.company_id IS '企業ID（companies.idへの外部キー。1社につき1レコードのみ）'");
        DB::statement("COMMENT ON COLUMN company_badges.verifier_owner_id IS '承認した事務局管理者のID（owners.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN company_badges.status IS 'バッジの状態（active: 有効, revoked: 制限など）'");
        DB::statement("COMMENT ON COLUMN company_badges.signature IS 'デジタル署名/検証用ハッシュ（DB直接改ざん検知用）'");
        DB::statement("COMMENT ON COLUMN company_badges.created_at IS '認証日（レコード作成日）'");
        DB::statement("COMMENT ON COLUMN company_badges.updated_at IS '更新日時'");

        // 3. users テーブル（従業員・担当者）
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('email', 255);
            $table->string('password_hash', 255);
            $table->string('user_name', 100);
            $table->string('user_name_kana', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('position', 100)->nullable();
            $table->string('role', 20)->default('staff'); // admin, staff
            $table->string('status', 20)->default('active'); // suspended, active, deleted
            $table->smallInteger('is_all_assigned')->default(0); // 0: 全担当, 1: 担当分け
            $table->smallInteger('notify_transaction_buyer')->default(1);
            $table->smallInteger('notify_transaction_seller')->default(1);
            $table->smallInteger('notify_chat')->default(1);
            $table->smallInteger('notify_notice')->default(1);
            $table->timestamps();

            $table->unique(['company_id', 'email']);
        });

        // users テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE users IS '企業に所属するユーザーアカウントを管理するテーブル'");
        DB::statement("COMMENT ON COLUMN users.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN users.company_id IS '所属企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN users.email IS 'メールアドレス（連絡用兼ログインID。同一企業内でのみ重複不可）'");
        DB::statement("COMMENT ON COLUMN users.password_hash IS 'ハッシュ化されたパスワード'");
        DB::statement("COMMENT ON COLUMN users.user_name IS '担当者氏名'");
        DB::statement("COMMENT ON COLUMN users.user_name_kana IS '担当者氏名フリガナ'");
        DB::statement("COMMENT ON COLUMN users.department IS '所属部署名'");
        DB::statement("COMMENT ON COLUMN users.position IS '役職名'");
        DB::statement("COMMENT ON COLUMN users.role IS '社内権限（admin: 管理者, staff: 一般社員）'");
        DB::statement("COMMENT ON COLUMN users.status IS 'ユーザーアカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");
        DB::statement("COMMENT ON COLUMN users.is_all_assigned IS '取引先担当区分（0: 全担当[デフォルト], 1: 担当分け）'");
        DB::statement("COMMENT ON COLUMN users.notify_transaction_buyer IS '買手取引通知メール (0: OFF, 1: ON)'");
        DB::statement("COMMENT ON COLUMN users.notify_transaction_seller IS '売手取引通知メール (0: OFF, 1: ON)'");
        DB::statement("COMMENT ON COLUMN users.notify_chat IS 'チャット受信通知メール (0: OFF, 1: ON)'");
        DB::statement("COMMENT ON COLUMN users.notify_notice IS '重要お知らせ通知メール (0: OFF, 1: ON)'");
        DB::statement("COMMENT ON COLUMN users.created_at IS '登録日時'");
        DB::statement("COMMENT ON COLUMN users.updated_at IS '更新日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('company_badges');
        Schema::dropIfExists('companies');
    }
};
