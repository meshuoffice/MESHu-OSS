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
        // 1. ENUM型の生成（重複防止のため安全に事前削除）
        DB::statement("DROP TYPE IF EXISTS owner_role_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS account_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS user_role_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS link_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS transaction_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS rounding_type_enum CASCADE");

        DB::statement("CREATE TYPE owner_role_enum AS ENUM ('admin', 'reviewer', 'viewer')");
        DB::statement("CREATE TYPE account_status_enum AS ENUM ('suspended', 'active', 'deleted')");
        DB::statement("CREATE TYPE user_role_enum AS ENUM ('admin', 'staff')");
        DB::statement("CREATE TYPE link_status_enum AS ENUM ('pending', 'approved', 'denied')");
        DB::statement("CREATE TYPE transaction_status_enum AS ENUM (
            'estimate_request',
            'estimate_replied',
            'estimate_declined',
            'order_placed',
            'order_accepted',
            'order_declined'
        )");
        DB::statement("CREATE TYPE rounding_type_enum AS ENUM ('round', 'floor', 'ceil')");

        // 2. owners テーブル（事務局管理者）
        Schema::create('owners', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('email')->unique();
            $table->text('password_hash');
            $table->string('role', 20)->default('viewer');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        // owners テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE owners IS 'システム運営を行う事務局アカウントを管理するテーブル'");
        DB::statement("COMMENT ON COLUMN owners.id IS '主キーID'");
        DB::statement("COMMENT ON COLUMN owners.name IS '事務局管理者氏名'");
        DB::statement("COMMENT ON COLUMN owners.email IS 'メールアドレス（ログインID）'");
        DB::statement("COMMENT ON COLUMN owners.password_hash IS 'ハッシュ化されたパスワード'");
        DB::statement("COMMENT ON COLUMN owners.role IS '事務局権限（admin: システム管理者, reviewer: 審査担当者, viewer: 閲覧のみ）'");
        DB::statement("COMMENT ON COLUMN owners.status IS '事務局アカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");
        DB::statement("COMMENT ON COLUMN owners.last_login_at IS '最終ログイン日時'");
        DB::statement("COMMENT ON COLUMN owners.created_at IS '登録日時'");
        DB::statement("COMMENT ON COLUMN owners.updated_at IS '更新日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('owners');
        DB::statement("DROP TYPE IF EXISTS owner_role_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS account_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS user_role_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS link_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS transaction_status_enum CASCADE");
        DB::statement("DROP TYPE IF EXISTS rounding_type_enum CASCADE");
    }
};
