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
        // 1. chat_messages テーブル（企業間チャットメッセージ）
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_link_id')->constrained('company_links')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('message_body');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_link_id', 'id']);
            $table->index('user_id');
        });

        // chat_messages テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE chat_messages IS '企業間チャットメッセージ履歴テーブル'");
        DB::statement("COMMENT ON COLUMN chat_messages.id IS 'メッセージID（主キー）'");
        DB::statement("COMMENT ON COLUMN chat_messages.company_link_id IS '対象つながりID（company_links.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_messages.company_id IS '送信元企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_messages.user_id IS '送信者ユーザーID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_messages.message_body IS 'メッセージ本文'");
        DB::statement("COMMENT ON COLUMN chat_messages.created_at IS '送信日時'");
        DB::statement("COMMENT ON COLUMN chat_messages.updated_at IS '更新日時'");
        DB::statement("COMMENT ON COLUMN chat_messages.deleted_at IS '論理削除日時（NULL: 有効）'");

        // 部分インデックス作成（未削除メッセージの高速取得用）
        DB::statement("CREATE INDEX idx_chat_messages_link_active ON chat_messages(company_link_id, id DESC) WHERE deleted_at IS NULL");

        // 2. chat_message_attachments テーブル（チャット添付ファイル）
        Schema::create('chat_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_message_id')->constrained('chat_messages')->onDelete('cascade');
            $table->text('file_name');
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->binary('file_data');
            $table->timestamp('created_at')->useCurrent();
        });

        // chat_message_attachments テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE chat_message_attachments IS 'チャット添付ファイルテーブル（バイナリ格納）'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.id IS '添付ID（主キー）'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.chat_message_id IS '対象メッセージID（chat_messages.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.file_name IS 'アップロード元ファイル名'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.mime_type IS 'Content-Type'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.file_size IS 'ファイル容量（バイト）'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.file_data IS 'ファイル実バイナリデータ'");
        DB::statement("COMMENT ON COLUMN chat_message_attachments.created_at IS 'アップロード日時'");

        // 3. chat_read_statuses テーブル（既読管理）
        Schema::create('chat_read_statuses', function (Blueprint $table) {
            $table->foreignId('company_link_id')->constrained('company_links')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->bigInteger('last_read_message_id')->default(0);
            $table->timestamp('read_at')->nullable();

            $table->primary(['company_link_id', 'user_id']);
        });

        // chat_read_statuses テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE chat_read_statuses IS 'ユーザー別チャット既読位置管理テーブル'");
        DB::statement("COMMENT ON COLUMN chat_read_statuses.company_link_id IS 'つながりID（company_links.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_read_statuses.user_id IS '閲覧ユーザーID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN chat_read_statuses.last_read_message_id IS '最後に閲覧したメッセージID'");
        DB::statement("COMMENT ON COLUMN chat_read_statuses.read_at IS '最終既読確認日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_read_statuses');
        Schema::dropIfExists('chat_message_attachments');
        Schema::dropIfExists('chat_messages');
    }
};
