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
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token_hash', 64);
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index('token_hash');
            $table->index('user_id');
            $table->index('expires_at');
        });

        // password_reset_tokens テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE password_reset_tokens IS '企業ユーザー用パスワード再設定トークン管理テーブル'");
        DB::statement("COMMENT ON COLUMN password_reset_tokens.id IS 'トークンID（主キー）'");
        DB::statement("COMMENT ON COLUMN password_reset_tokens.user_id IS '再設定対象ユーザーID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN password_reset_tokens.token_hash IS 'トークンのSHA-256ハッシュ値'");
        DB::statement("COMMENT ON COLUMN password_reset_tokens.expires_at IS 'トークン有効期限（発行から1時間後）'");
        DB::statement("COMMENT ON COLUMN password_reset_tokens.created_at IS 'トークン発行日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
