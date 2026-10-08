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
        // 1. notices テーブル（お知らせ情報）
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('sender_type', 20)->default('admin'); // admin / company
            $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('owners')->onDelete('set null');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('category', 50)->default('info'); // important, maintenance, info
            $table->string('target_scope', 50)->default('all'); // all, verified, unverified
            $table->string('title', 255);
            $table->text('content');
            $table->boolean('is_important')->default(false);
            $table->boolean('is_published')->default(false);
            $table->integer('view_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['sender_type', 'company_id']);
            $table->index(['is_published', 'published_at']);
            $table->index('category');
            $table->index('target_scope');
        });

        // notices テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE notices IS '事務局および企業からのお知らせ・アナウンス情報テーブル'");
        DB::statement("COMMENT ON COLUMN notices.id IS 'お知らせID（主キー）'");
        DB::statement("COMMENT ON COLUMN notices.sender_type IS '発信元区分（admin: 事務局, company: 企業）'");
        DB::statement("COMMENT ON COLUMN notices.company_id IS '発信元企業ID（事務局発信時はNULL）'");
        DB::statement("COMMENT ON COLUMN notices.created_by_admin_id IS '事務局作成者ID（owners.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN notices.created_by_user_id IS '企業作成者ID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN notices.category IS 'カテゴリ（important: 重要, maintenance: メンテ, info: お知らせ）'");
        DB::statement("COMMENT ON COLUMN notices.target_scope IS '開示対象範囲（all: 全員, verified: 認証企業のみ, unverified: 非認証のみ）'");
        DB::statement("COMMENT ON COLUMN notices.title IS 'お知らせタイトル'");
        DB::statement("COMMENT ON COLUMN notices.content IS 'お知らせ本文'");
        DB::statement("COMMENT ON COLUMN notices.is_important IS '重要フラグ（最上部固定表示）'");
        DB::statement("COMMENT ON COLUMN notices.is_published IS '公開ステータス（TRUE: 公開中, FALSE: 下書き）'");
        DB::statement("COMMENT ON COLUMN notices.view_count IS '閲覧カウンター'");
        DB::statement("COMMENT ON COLUMN notices.published_at IS '掲載開始日時'");
        DB::statement("COMMENT ON COLUMN notices.created_at IS '作成日時'");
        DB::statement("COMMENT ON COLUMN notices.updated_at IS '更新日時'");

        // 2. notice_attachments テーブル（お知らせ添付ファイル）
        Schema::create('notice_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notice_id')->constrained('notices')->onDelete('cascade');
            $table->text('file_name');
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->binary('file_data');
            $table->timestamp('created_at')->useCurrent();
        });

        // notice_attachments テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE notice_attachments IS 'お知らせ添付ファイルテーブル（バイナリ格納）'");
        DB::statement("COMMENT ON COLUMN notice_attachments.id IS '添付ID（主キー）'");
        DB::statement("COMMENT ON COLUMN notice_attachments.notice_id IS '対象お知らせID（notices.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN notice_attachments.file_name IS 'ファイル名'");
        DB::statement("COMMENT ON COLUMN notice_attachments.mime_type IS 'Content-Type'");
        DB::statement("COMMENT ON COLUMN notice_attachments.file_size IS 'ファイル容量（バイト）'");
        DB::statement("COMMENT ON COLUMN notice_attachments.file_data IS 'ファイル実バイナリデータ'");
        DB::statement("COMMENT ON COLUMN notice_attachments.created_at IS 'アップロード日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notice_attachments');
        Schema::dropIfExists('notices');
    }
};
