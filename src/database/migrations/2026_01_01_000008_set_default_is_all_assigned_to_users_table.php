<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * mshdev（CI4）完全準拠スキーマ調整・突合せ一括適用マイグレーション
     */
    public function up(): void
    {
        // 1. users.is_all_assigned の初期値を 1 (全担当) に変更
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('is_all_assigned')->default(1)->change();
        });

        // 明示的な PostgreSQL コメントの更新
        DB::statement("COMMENT ON COLUMN users.is_all_assigned IS '全取引先担当フラグ（1: 全担当[デフォルト], 0: 個別担当分け）'");

        // 2. companies.amount_rounding / tax_rounding を rounding_type_enum に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'rounding_type_enum') THEN
                    CREATE TYPE rounding_type_enum AS ENUM ('round', 'floor', 'ceil');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN amount_rounding DROP DEFAULT,
                ALTER COLUMN amount_rounding TYPE rounding_type_enum USING amount_rounding::rounding_type_enum,
                ALTER COLUMN amount_rounding SET DEFAULT 'round';
        ");

        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN tax_rounding DROP DEFAULT,
                ALTER COLUMN tax_rounding TYPE rounding_type_enum USING tax_rounding::rounding_type_enum,
                ALTER COLUMN tax_rounding SET DEFAULT 'round';
        ");

        DB::statement("COMMENT ON COLUMN companies.amount_rounding IS '金額端数処理区分（round: 四捨五入[デフォルト], floor: 切り捨て, ceil: 切り上げ）'");
        DB::statement("COMMENT ON COLUMN companies.tax_rounding IS '消費税端数処理区分（round: 四捨五入[デフォルト], floor: 切り捨て, ceil: 切り上げ）'");

        // 3. companies.status を account_status_enum に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'account_status_enum') THEN
                    CREATE TYPE account_status_enum AS ENUM ('suspended', 'active', 'deleted');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE account_status_enum USING status::account_status_enum,
                ALTER COLUMN status SET DEFAULT 'suspended';
        ");

        DB::statement("COMMENT ON COLUMN companies.status IS '企業の稼働ステータス（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 4. company_links.status を link_status_enum に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'link_status_enum') THEN
                    CREATE TYPE link_status_enum AS ENUM ('pending', 'approved', 'denied');
                END IF;
            END
            $$;
        ");

        DB::statement("DROP INDEX IF EXISTS idx_company_links_buyer_seller_approved");
        DB::statement("DROP INDEX IF EXISTS idx_company_links_seller_approved");

        DB::statement("
            ALTER TABLE company_links
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE link_status_enum USING status::link_status_enum,
                ALTER COLUMN status SET DEFAULT 'pending';
        ");

        DB::statement("CREATE INDEX idx_company_links_buyer_seller_approved ON company_links(buyer_company_id, seller_company_id) WHERE status = 'approved'");
        DB::statement("CREATE INDEX idx_company_links_seller_approved ON company_links(seller_company_id) WHERE status = 'approved'");

        DB::statement("COMMENT ON COLUMN company_links.status IS '取引リンクステータス（pending: 申請中, approved: 承認, denied: 否認）'");

        // 5. invitations.registered_at カラムを削除（mshdev準拠：本登録時は物理削除されるため不要）
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn('registered_at');
        });

        // 6. owners.role, status を enum 型に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'owner_role_enum') THEN
                    CREATE TYPE owner_role_enum AS ENUM ('admin', 'reviewer', 'viewer');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'account_status_enum') THEN
                    CREATE TYPE account_status_enum AS ENUM ('suspended', 'active', 'deleted');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE owners
                ALTER COLUMN role DROP DEFAULT,
                ALTER COLUMN role TYPE owner_role_enum USING role::owner_role_enum,
                ALTER COLUMN role SET DEFAULT 'viewer';
        ");

        DB::statement("
            ALTER TABLE owners
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE account_status_enum USING status::account_status_enum,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("COMMENT ON COLUMN owners.role IS '事務局権限（admin: システム管理者, reviewer: 審査担当者, viewer: 閲覧のみ）'");
        DB::statement("COMMENT ON COLUMN owners.status IS '事務局アカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 7. notices.sender_type, category, target_scope を enum 型に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notice_sender_type_enum') THEN
                    CREATE TYPE notice_sender_type_enum AS ENUM ('admin', 'company');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notice_category_enum') THEN
                    CREATE TYPE notice_category_enum AS ENUM ('important', 'maintenance', 'info');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'notice_target_scope_enum') THEN
                    CREATE TYPE notice_target_scope_enum AS ENUM ('all', 'verified', 'unverified');
                END IF;
            END
            $$;
        ");

        DB::statement("DROP INDEX IF EXISTS notices_sender_type_company_id_index");
        DB::statement("DROP INDEX IF EXISTS notices_category_index");
        DB::statement("DROP INDEX IF EXISTS notices_target_scope_index");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN sender_type DROP DEFAULT,
                ALTER COLUMN sender_type TYPE notice_sender_type_enum USING sender_type::notice_sender_type_enum,
                ALTER COLUMN sender_type SET DEFAULT 'admin';
        ");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN category DROP DEFAULT,
                ALTER COLUMN category TYPE notice_category_enum USING category::notice_category_enum,
                ALTER COLUMN category SET DEFAULT 'info';
        ");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN target_scope DROP DEFAULT,
                ALTER COLUMN target_scope TYPE notice_target_scope_enum USING target_scope::notice_target_scope_enum,
                ALTER COLUMN target_scope SET DEFAULT 'all';
        ");

        DB::statement("CREATE INDEX notices_sender_type_company_id_index ON notices(sender_type, company_id)");
        DB::statement("CREATE INDEX notices_category_index ON notices(category)");
        DB::statement("CREATE INDEX notices_target_scope_index ON notices(target_scope)");

        DB::statement("COMMENT ON COLUMN notices.sender_type IS '発信元区分（admin: 事務局, company: 企業）'");
        DB::statement("COMMENT ON COLUMN notices.category IS 'カテゴリ（important: 重要, maintenance: メンテ, info: お知らせ）'");
        DB::statement("COMMENT ON COLUMN notices.target_scope IS '開示対象範囲（all: 全員, verified: 認証企業のみ, unverified: 非認証のみ）'");

        // 8. transactions および transaction_* テーブルを mshdev 仕様に完全準拠させて再構築
        DB::statement("DROP TABLE IF EXISTS transaction_comments, transaction_attachments, transaction_items, transaction_histories, transactions CASCADE;");

        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'transaction_status_enum') THEN
                    CREATE TYPE transaction_status_enum AS ENUM (
                        'estimate_request',
                        'estimate_replied',
                        'estimate_declined',
                        'order_placed',
                        'order_accepted',
                        'order_declined'
                    );
                END IF;
            END
            $$;
        ");

        // 8.1. transactions テーブル
        DB::statement("
            CREATE TABLE transactions (
                id BIGSERIAL PRIMARY KEY,
                company_link_id INT NOT NULL REFERENCES company_links(id) ON DELETE CASCADE,
                status transaction_status_enum NOT NULL DEFAULT 'estimate_request',
                title TEXT NOT NULL,
                desired_delivery_date DATE,
                delivery_date DATE,
                is_tax_exempt SMALLINT NOT NULL DEFAULT 0,
                tax_rate SMALLINT,
                amount_rounding rounding_type_enum,
                tax_rounding rounding_type_enum,
                subtotal_amount BIGINT NOT NULL DEFAULT 0,
                shipping_fee BIGINT NOT NULL DEFAULT 0,
                discount_amount BIGINT NOT NULL DEFAULT 0,
                tax_amount BIGINT NOT NULL DEFAULT 0,
                total_amount BIGINT NOT NULL DEFAULT 0,
                buyer_remarks TEXT,
                seller_remarks TEXT,
                decline_category TEXT,
                decline_reason TEXT,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL
            );
        ");
        DB::statement("CREATE INDEX idx_transactions_link_status ON transactions(company_link_id, status);");

        DB::statement("COMMENT ON TABLE transactions IS '取引（見積・注文・回答・辞退）ヘッダテーブル';");
        DB::statement("COMMENT ON COLUMN transactions.id IS '取引ID（主キー）';");
        DB::statement("COMMENT ON COLUMN transactions.company_link_id IS '企業間リンクID（company_links.idへの外部キー / 買手・売手を特定）';");
        DB::statement("COMMENT ON COLUMN transactions.status IS '取引ステータス（estimate_request: 見積依頼, estimate_replied: 見積回答, estimate_declined: 見積辞退, order_placed: 注文, order_accepted: 注文回答/受注承諾, order_declined: 注文辞退）';");
        DB::statement("COMMENT ON COLUMN transactions.title IS '件名 / 案件名';");
        DB::statement("COMMENT ON COLUMN transactions.desired_delivery_date IS '買手希望納期';");
        DB::statement("COMMENT ON COLUMN transactions.delivery_date IS '確定納期';");
        DB::statement("COMMENT ON COLUMN transactions.is_tax_exempt IS '非課税フラグ（0: 課税, 1: 非課税）';");
        DB::statement("COMMENT ON COLUMN transactions.tax_rate IS '適用消費税率（金額入力時に売手companiesよりスナップショット転記）';");
        DB::statement("COMMENT ON COLUMN transactions.amount_rounding IS '金額端数処理（金額入力時に売手companiesよりスナップショット転記: round/floor/ceil）';");
        DB::statement("COMMENT ON COLUMN transactions.tax_rounding IS '消費税端数処理（金額入力時に売手companiesよりスナップショット転記: round/floor/ceil）';");
        DB::statement("COMMENT ON COLUMN transactions.subtotal_amount IS '税抜小計（整数円）';");
        DB::statement("COMMENT ON COLUMN transactions.shipping_fee IS '送料（整数円）';");
        DB::statement("COMMENT ON COLUMN transactions.discount_amount IS '値引き額（整数円）';");
        DB::statement("COMMENT ON COLUMN transactions.tax_amount IS '消費税額（整数円）';");
        DB::statement("COMMENT ON COLUMN transactions.total_amount IS '税込合計金額（整数円）';");
        DB::statement("COMMENT ON COLUMN transactions.buyer_remarks IS '買手からの納品指示・備考';");
        DB::statement("COMMENT ON COLUMN transactions.seller_remarks IS '売手からの承諾メッセージ・備考';");
        DB::statement("COMMENT ON COLUMN transactions.decline_category IS '辞退理由種別（辞退時）';");
        DB::statement("COMMENT ON COLUMN transactions.decline_reason IS '辞退理由詳細（辞退時）';");
        DB::statement("COMMENT ON COLUMN transactions.created_at IS '取引作成日時';");
        DB::statement("COMMENT ON COLUMN transactions.updated_at IS '取引更新日時';");

        // 8.2. transaction_histories テーブル
        DB::statement("
            CREATE TABLE transaction_histories (
                id BIGSERIAL PRIMARY KEY,
                transaction_id BIGINT NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
                user_id INT REFERENCES users(id) ON DELETE SET NULL,
                status transaction_status_enum NOT NULL,
                note TEXT,
                created_at TIMESTAMP NOT NULL
            );
        ");
        DB::statement("CREATE INDEX idx_transaction_histories_tx_created ON transaction_histories(transaction_id, created_at);");

        DB::statement("COMMENT ON TABLE transaction_histories IS '取引ステータス変更履歴・伝達事項テーブル';");
        DB::statement("COMMENT ON COLUMN transaction_histories.id IS '履歴ID（主キー）';");
        DB::statement("COMMENT ON COLUMN transaction_histories.transaction_id IS '対象取引ID（transactions.idへの外部キー）';");
        DB::statement("COMMENT ON COLUMN transaction_histories.user_id IS '操作担当者ID（users.idへの外部キー / ユーザー削除時はSET NULL）';");
        DB::statement("COMMENT ON COLUMN transaction_histories.status IS '変更後の取引ステータス（6種）';");
        DB::statement("COMMENT ON COLUMN transaction_histories.note IS '伝達事項・補足メモ（辞退理由など）';");
        DB::statement("COMMENT ON COLUMN transaction_histories.created_at IS '操作・ステータス変更日時';");

        // 8.3. transaction_items テーブル
        DB::statement("
            CREATE TABLE transaction_items (
                id BIGSERIAL PRIMARY KEY,
                transaction_id BIGINT NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
                line_no INT NOT NULL DEFAULT 1,
                item_name TEXT NOT NULL,
                item_code VARCHAR(100),
                spec TEXT,
                quantity NUMERIC(10, 2) NOT NULL DEFAULT 1.00,
                unit VARCHAR(30) NOT NULL DEFAULT '個',
                unit_price NUMERIC(12, 2),
                amount BIGINT,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL
            );
        ");
        DB::statement("CREATE INDEX idx_transaction_items_tx_line ON transaction_items(transaction_id, line_no);");

        DB::statement("COMMENT ON TABLE transaction_items IS '取引明細行テーブル';");
        DB::statement("COMMENT ON COLUMN transaction_items.id IS '明細行ID（主キー）';");
        DB::statement("COMMENT ON COLUMN transaction_items.transaction_id IS '対象取引ID（transactions.idへの外部キー）';");
        DB::statement("COMMENT ON COLUMN transaction_items.line_no IS '表示順・行番号（1, 2, ...）';");
        DB::statement("COMMENT ON COLUMN transaction_items.item_name IS '品名・品目名';");
        DB::statement("COMMENT ON COLUMN transaction_items.item_code IS '型番・品番・コード（任意）';");
        DB::statement("COMMENT ON COLUMN transaction_items.spec IS '仕様・寸法・備考（任意）';");
        DB::statement("COMMENT ON COLUMN transaction_items.quantity IS '数量（小数点対応）';");
        DB::statement("COMMENT ON COLUMN transaction_items.unit IS '単位（個, 台, 式, kg, m等）';");
        DB::statement("COMMENT ON COLUMN transaction_items.unit_price IS '税抜単価（見積依頼時は未定・NULL）';");
        DB::statement("COMMENT ON COLUMN transaction_items.amount IS '税抜行金額（単価×数量 端数処理後 / 整数円）';");
        DB::statement("COMMENT ON COLUMN transaction_items.created_at IS '作成日時';");
        DB::statement("COMMENT ON COLUMN transaction_items.updated_at IS '更新日時';");

        // 8.4. transaction_attachments テーブル
        DB::statement("
            CREATE TABLE transaction_attachments (
                id BIGSERIAL PRIMARY KEY,
                transaction_id BIGINT NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
                company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                user_id INT REFERENCES users(id) ON DELETE SET NULL,
                file_name TEXT NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                file_size BIGINT NOT NULL,
                file_data BYTEA NOT NULL,
                created_at TIMESTAMP NOT NULL
            );
        ");
        DB::statement("CREATE INDEX idx_transaction_attachments_tx ON transaction_attachments(transaction_id);");

        DB::statement("COMMENT ON TABLE transaction_attachments IS '取引添付ファイルテーブル（BYTEA直接格納）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.id IS '添付ファイルID（主キー）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.transaction_id IS '対象取引ID（transactions.idへの外部キー）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.company_id IS 'アップロード企業ID（companies.idへの外部キー / 買手・売手を判別）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.user_id IS 'アップロード担当者ID（users.idへの外部キー / ユーザー削除時はSET NULL）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_name IS 'ファイル名（拡張子含む）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.mime_type IS 'Content-Type（MIMEタイプ）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_size IS 'ファイル容量（バイト数）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_data IS 'ファイル実バイナリデータ（PostgreSQL BYTEA）';");
        DB::statement("COMMENT ON COLUMN transaction_attachments.created_at IS 'アップロード日時';");

        // 8.5. transaction_comments テーブル
        DB::statement("
            CREATE TABLE transaction_comments (
                id BIGSERIAL PRIMARY KEY,
                transaction_id BIGINT NOT NULL REFERENCES transactions(id) ON DELETE CASCADE,
                company_id INT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
                user_id INT REFERENCES users(id) ON DELETE SET NULL,
                comment TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL,
                updated_at TIMESTAMP NOT NULL
            );
        ");
        DB::statement("CREATE INDEX idx_transaction_comments_tx_created ON transaction_comments(transaction_id, created_at);");

        DB::statement("COMMENT ON TABLE transaction_comments IS '取引コメントスレッドテーブル';");
        DB::statement("COMMENT ON COLUMN transaction_comments.id IS 'コメントID（主キー）';");
        DB::statement("COMMENT ON COLUMN transaction_comments.transaction_id IS '対象取引ID（transactions.idへの外部キー）';");
        DB::statement("COMMENT ON COLUMN transaction_comments.company_id IS '投稿企業ID（companies.idへの外部キー / 買手・売手を判別）';");
        DB::statement("COMMENT ON COLUMN transaction_comments.user_id IS '投稿担当者ID（users.idへの外部キー / ユーザー削除時はSET NULL）';");
        DB::statement("COMMENT ON COLUMN transaction_comments.comment IS 'コメント・メッセージ本文';");
        DB::statement("COMMENT ON COLUMN transaction_comments.created_at IS '投稿日時';");
        DB::statement("COMMENT ON COLUMN transaction_comments.updated_at IS '更新日時';");

        // 9. users.role, status を enum 型に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role_enum') THEN
                    CREATE TYPE user_role_enum AS ENUM ('admin', 'staff');
                END IF;
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'account_status_enum') THEN
                    CREATE TYPE account_status_enum AS ENUM ('suspended', 'active', 'deleted');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE users
                ALTER COLUMN role DROP DEFAULT,
                ALTER COLUMN role TYPE user_role_enum USING role::user_role_enum,
                ALTER COLUMN role SET DEFAULT 'staff';
        ");

        DB::statement("
            ALTER TABLE users
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE account_status_enum USING status::account_status_enum,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("COMMENT ON COLUMN users.role IS '社内権限（admin: 管理者, staff: 一般社員）'");
        DB::statement("COMMENT ON COLUMN users.status IS 'ユーザーアカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 10. company_badges.status を enum 型に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'badge_status_enum') THEN
                    CREATE TYPE badge_status_enum AS ENUM ('active', 'revoked');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE company_badges
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE badge_status_enum USING status::badge_status_enum,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("COMMENT ON COLUMN company_badges.status IS 'バッジの状態（active: 有効, revoked: 制限など）'");

        // 11. invitations.buysell を enum 型に変更
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'buysell_role_enum') THEN
                    CREATE TYPE buysell_role_enum AS ENUM ('buyer', 'seller');
                END IF;
            END
            $$;
        ");

        DB::statement("
            ALTER TABLE invitations
                ALTER COLUMN buysell DROP DEFAULT,
                ALTER COLUMN buysell TYPE buysell_role_enum USING buysell::buysell_role_enum,
                ALTER COLUMN buysell SET DEFAULT 'buyer';
        ");

        DB::statement("COMMENT ON COLUMN invitations.buysell IS '登録時の自社属性 (buyer: 購入側, seller: 販売側)'");

        // 12. Laravel標準機能以外の全MESHu独自テーブルのcreated_atにデフォルト値CURRENT_TIMESTAMPを付加
        DB::statement("
            DO $$
            DECLARE
                r RECORD;
            BEGIN
                FOR r IN (
                    SELECT table_name
                    FROM information_schema.columns
                    WHERE table_schema = 'public'
                      AND column_name = 'created_at'
                      AND table_name NOT IN ('migrations', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks')
                ) LOOP
                    EXECUTE format('ALTER TABLE %I ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP', r.table_name);
                END LOOP;
            END
            $$;
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 12. created_at のデフォルト値を解除（元々useCurrentだったテーブルを除く）
        DB::statement("
            DO $$
            DECLARE
                r RECORD;
            BEGIN
                FOR r IN (
                    SELECT table_name
                    FROM information_schema.columns
                    WHERE table_schema = 'public'
                      AND column_name = 'created_at'
                      AND table_name NOT IN ('migrations', 'jobs', 'failed_jobs', 'job_batches', 'cache', 'cache_locks', 'notice_attachments', 'chat_message_attachments', 'password_reset_tokens')
                ) LOOP
                    EXECUTE format('ALTER TABLE %I ALTER COLUMN created_at DROP DEFAULT', r.table_name);
                END LOOP;
            END
            $$;
        ");

        // 11. invitations.buysell を VARCHAR(20) に戻す
        DB::statement("
            ALTER TABLE invitations
                ALTER COLUMN buysell DROP DEFAULT,
                ALTER COLUMN buysell TYPE VARCHAR(20) USING buysell::text,
                ALTER COLUMN buysell SET DEFAULT 'buyer';
        ");

        DB::statement("COMMENT ON COLUMN invitations.buysell IS '登録時の自社属性 (buyer / seller)'");

        // 10. company_badges.status を VARCHAR(50) に戻す
        DB::statement("
            ALTER TABLE company_badges
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE VARCHAR(50) USING status::text,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("COMMENT ON COLUMN company_badges.status IS 'バッジの状態（active: 有効, revoked: 制限など）'");

        // 9. users.role, status を VARCHAR(20) に戻す
        DB::statement("
            ALTER TABLE users
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE VARCHAR(20) USING status::text,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("
            ALTER TABLE users
                ALTER COLUMN role DROP DEFAULT,
                ALTER COLUMN role TYPE VARCHAR(20) USING role::text,
                ALTER COLUMN role SET DEFAULT 'staff';
        ");

        DB::statement("COMMENT ON COLUMN users.role IS '社内権限（admin: 管理者, staff: 一般社員）'");
        DB::statement("COMMENT ON COLUMN users.status IS 'ユーザーアカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 8. 取引関連テーブルのロールバック
        DB::statement("DROP TABLE IF EXISTS transaction_comments, transaction_attachments, transaction_items, transaction_histories, transactions CASCADE;");

        // 7. notices.sender_type, category, target_scope を VARCHAR に戻す
        DB::statement("DROP INDEX IF EXISTS notices_sender_type_company_id_index");
        DB::statement("DROP INDEX IF EXISTS notices_category_index");
        DB::statement("DROP INDEX IF EXISTS notices_target_scope_index");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN target_scope DROP DEFAULT,
                ALTER COLUMN target_scope TYPE VARCHAR(50) USING target_scope::text,
                ALTER COLUMN target_scope SET DEFAULT 'all';
        ");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN category DROP DEFAULT,
                ALTER COLUMN category TYPE VARCHAR(50) USING category::text,
                ALTER COLUMN category SET DEFAULT 'info';
        ");

        DB::statement("
            ALTER TABLE notices
                ALTER COLUMN sender_type DROP DEFAULT,
                ALTER COLUMN sender_type TYPE VARCHAR(20) USING sender_type::text,
                ALTER COLUMN sender_type SET DEFAULT 'admin';
        ");

        DB::statement("CREATE INDEX notices_sender_type_company_id_index ON notices(sender_type, company_id)");
        DB::statement("CREATE INDEX notices_category_index ON notices(category)");
        DB::statement("CREATE INDEX notices_target_scope_index ON notices(target_scope)");

        DB::statement("COMMENT ON COLUMN notices.sender_type IS '発信元区分（admin: 事務局, company: 企業）'");
        DB::statement("COMMENT ON COLUMN notices.category IS 'カテゴリ（important: 重要, maintenance: メンテ, info: お知らせ）'");
        DB::statement("COMMENT ON COLUMN notices.target_scope IS '開示対象範囲（all: 全員, verified: 認証企業のみ, unverified: 非認証のみ）'");

        // 6. owners.role, status を VARCHAR(20) に戻す
        DB::statement("
            ALTER TABLE owners
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE VARCHAR(20) USING status::text,
                ALTER COLUMN status SET DEFAULT 'active';
        ");

        DB::statement("
            ALTER TABLE owners
                ALTER COLUMN role DROP DEFAULT,
                ALTER COLUMN role TYPE VARCHAR(20) USING role::text,
                ALTER COLUMN role SET DEFAULT 'viewer';
        ");

        DB::statement("COMMENT ON COLUMN owners.role IS '事務局権限（admin: システム管理者, reviewer: 審査担当者, viewer: 閲覧のみ）'");
        DB::statement("COMMENT ON COLUMN owners.status IS '事務局アカウント状態（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 5. invitations.registered_at カラムを再作成
        Schema::table('invitations', function (Blueprint $table) {
            $table->timestamp('registered_at')->nullable()->after('user_name');
        });
        DB::statement("COMMENT ON COLUMN invitations.registered_at IS '招待経由での本登録完了日時'");

        // 4. company_links.status を VARCHAR(20) に戻す
        DB::statement("DROP INDEX IF EXISTS idx_company_links_buyer_seller_approved");
        DB::statement("DROP INDEX IF EXISTS idx_company_links_seller_approved");

        DB::statement("
            ALTER TABLE company_links
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE VARCHAR(20) USING status::text,
                ALTER COLUMN status SET DEFAULT 'pending';
        ");

        DB::statement("CREATE INDEX idx_company_links_buyer_seller_approved ON company_links(buyer_company_id, seller_company_id) WHERE status = 'approved'");
        DB::statement("CREATE INDEX idx_company_links_seller_approved ON company_links(seller_company_id) WHERE status = 'approved'");

        DB::statement("COMMENT ON COLUMN company_links.status IS '取引リンクステータス（pending: 申請中, approved: 承認, denied: 否認）'");

        // 3. companies.status を VARCHAR(20) に戻す
        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN status DROP DEFAULT,
                ALTER COLUMN status TYPE VARCHAR(20) USING status::text,
                ALTER COLUMN status SET DEFAULT 'suspended';
        ");

        DB::statement("COMMENT ON COLUMN companies.status IS '企業の稼働ステータス（suspended: 停止中, active: 稼働, deleted: 削除済）'");

        // 2. companies.amount_rounding / tax_rounding を VARCHAR(10) に戻す
        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN tax_rounding DROP DEFAULT,
                ALTER COLUMN tax_rounding TYPE VARCHAR(10) USING tax_rounding::text,
                ALTER COLUMN tax_rounding SET DEFAULT 'round';
        ");

        DB::statement("
            ALTER TABLE companies
                ALTER COLUMN amount_rounding DROP DEFAULT,
                ALTER COLUMN amount_rounding TYPE VARCHAR(10) USING amount_rounding::text,
                ALTER COLUMN amount_rounding SET DEFAULT 'round';
        ");

        DB::statement("COMMENT ON COLUMN companies.amount_rounding IS '行金額計算の端数処理（round: 四捨五入, floor: 切捨て, ceil: 切上げ）'");
        DB::statement("COMMENT ON COLUMN companies.tax_rounding IS '消費税計算の端数処理（round: 四捨五入, floor: 切捨て, ceil: 切上げ）'");

        // 1. users.is_all_assigned を 0 に戻す
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('is_all_assigned')->default(0)->change();
        });

        DB::statement("COMMENT ON COLUMN users.is_all_assigned IS '取引先担当区分（0: 全担当, 1: 担当分け）'");
    }
};
