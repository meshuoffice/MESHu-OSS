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
        // 1. transactions テーブル（取引ヘッダー）
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_link_id')->constrained('company_links')->onDelete('cascade');
            $table->string('title', 255);
            $table->string('order_number', 100)->nullable();
            $table->string('status', 30)->default('estimate_request');
            $table->decimal('subtotal_amount', 15, 2)->default(0.00);
            $table->decimal('tax_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->decimal('discount_amount', 15, 2)->default(0.00);
            $table->decimal('shipping_amount', 15, 2)->default(0.00);
            $table->date('delivery_date')->nullable();
            $table->text('delivery_location')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('buyer_notes')->nullable();
            $table->text('seller_notes')->nullable();
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->index(['company_link_id', 'status'], 'idx_transactions_link_status');
        });

        // transactions テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE transactions IS '取引（見積依頼・回答・発注・受注承諾・辞退）ヘッダーテーブル'");
        DB::statement("COMMENT ON COLUMN transactions.id IS '取引ID（主キー）'");
        DB::statement("COMMENT ON COLUMN transactions.company_link_id IS 'つながりID（company_links.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transactions.title IS '取引件名'");
        DB::statement("COMMENT ON COLUMN transactions.order_number IS '注文管理番号・発注コード'");
        DB::statement("COMMENT ON COLUMN transactions.status IS '取引進捗ステータス（estimate_request/estimate_replied/estimate_declined/order_placed/order_accepted/order_declined）'");
        DB::statement("COMMENT ON COLUMN transactions.subtotal_amount IS '税抜小計金額'");
        DB::statement("COMMENT ON COLUMN transactions.tax_amount IS '消費税額'");
        DB::statement("COMMENT ON COLUMN transactions.total_amount IS '税込合計金額'");
        DB::statement("COMMENT ON COLUMN transactions.discount_amount IS '値引き額（税抜）'");
        DB::statement("COMMENT ON COLUMN transactions.shipping_amount IS '送料（税抜）'");
        DB::statement("COMMENT ON COLUMN transactions.delivery_date IS '希望/予定納期'");
        DB::statement("COMMENT ON COLUMN transactions.delivery_location IS '納品場所・送付先'");
        DB::statement("COMMENT ON COLUMN transactions.payment_terms IS '支払条件・締め支払い'");
        DB::statement("COMMENT ON COLUMN transactions.buyer_notes IS '買手備考・依頼要件'");
        DB::statement("COMMENT ON COLUMN transactions.seller_notes IS '売手備考・見積条件'");
        DB::statement("COMMENT ON COLUMN transactions.decline_reason IS '辞退理由（辞退時のみ）'");
        DB::statement("COMMENT ON COLUMN transactions.created_at IS '起票日時'");
        DB::statement("COMMENT ON COLUMN transactions.updated_at IS '更新日時'");

        // 2. transaction_items テーブル（明細行）
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->integer('line_no')->default(1);
            $table->string('item_name', 255);
            $table->string('item_code', 100)->nullable();
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->string('unit', 20)->default('個');
            $table->decimal('unit_price', 15, 2)->default(0.00);
            $table->decimal('line_amount', 15, 2)->default(0.00);
            $table->smallInteger('tax_rate')->default(10);
            $table->boolean('is_tax_free')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        // transaction_items テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE transaction_items IS '取引の品目・明細行テーブル'");
        DB::statement("COMMENT ON COLUMN transaction_items.id IS '明細ID（主キー）'");
        DB::statement("COMMENT ON COLUMN transaction_items.transaction_id IS '取引ID（transactions.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_items.line_no IS '行番号（表示順）'");
        DB::statement("COMMENT ON COLUMN transaction_items.item_name IS '品名・項目名'");
        DB::statement("COMMENT ON COLUMN transaction_items.item_code IS '型番・商品コード'");
        DB::statement("COMMENT ON COLUMN transaction_items.quantity IS '数量'");
        DB::statement("COMMENT ON COLUMN transaction_items.unit IS '単位'");
        DB::statement("COMMENT ON COLUMN transaction_items.unit_price IS '税抜単価'");
        DB::statement("COMMENT ON COLUMN transaction_items.line_amount IS '行金額（単価 × 数量）'");
        DB::statement("COMMENT ON COLUMN transaction_items.tax_rate IS '適用消費税率（%）'");
        DB::statement("COMMENT ON COLUMN transaction_items.is_tax_free IS '非課税フラグ'");
        DB::statement("COMMENT ON COLUMN transaction_items.remarks IS '明細備考'");
        DB::statement("COMMENT ON COLUMN transaction_items.created_at IS '作成日時'");
        DB::statement("COMMENT ON COLUMN transaction_items.updated_at IS '更新日時'");

        // 3. transaction_histories テーブル（ステータス変更履歴）
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('action_name', 100);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // transaction_histories テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE transaction_histories IS '取引ステータス変更履歴テーブル'");
        DB::statement("COMMENT ON COLUMN transaction_histories.id IS '履歴ID（主キー）'");
        DB::statement("COMMENT ON COLUMN transaction_histories.transaction_id IS '取引ID（transactions.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_histories.user_id IS '操作者ユーザーID（users.idへの外部キー / 削除時SET NULL）'");
        DB::statement("COMMENT ON COLUMN transaction_histories.action_name IS '操作アクション名'");
        DB::statement("COMMENT ON COLUMN transaction_histories.from_status IS '変更前ステータス'");
        DB::statement("COMMENT ON COLUMN transaction_histories.to_status IS '変更後ステータス'");
        DB::statement("COMMENT ON COLUMN transaction_histories.comment IS '履歴付帯コメント'");
        DB::statement("COMMENT ON COLUMN transaction_histories.created_at IS '実行日時'");

        // 4. transaction_attachments テーブル（取引添付ファイル）
        Schema::create('transaction_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('file_name');
            $table->string('mime_type', 100);
            $table->bigInteger('file_size');
            $table->binary('file_data');
            $table->timestamp('created_at')->useCurrent();
        });

        // transaction_attachments テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE transaction_attachments IS '取引添付ファイルテーブル（バイナリ格納）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.id IS '添付ID（主キー）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.transaction_id IS '対象取引ID（transactions.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.company_id IS 'アップロード企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.user_id IS 'アップロード者ID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_name IS 'ファイル名'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.mime_type IS 'Content-Type'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_size IS 'ファイル容量（バイト）'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.file_data IS 'ファイル実バイナリデータ'");
        DB::statement("COMMENT ON COLUMN transaction_attachments.created_at IS 'アップロード日時'");

        // 5. transaction_comments テーブル（取引コメント）
        Schema::create('transaction_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('comment');
            $table->timestamps();
        });

        // transaction_comments テーブル・カラムコメント
        DB::statement("COMMENT ON TABLE transaction_comments IS '取引コメントスレッドテーブル'");
        DB::statement("COMMENT ON COLUMN transaction_comments.id IS 'コメントID（主キー）'");
        DB::statement("COMMENT ON COLUMN transaction_comments.transaction_id IS '対象取引ID（transactions.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_comments.company_id IS '投稿企業ID（companies.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_comments.user_id IS '投稿担当者ID（users.idへの外部キー）'");
        DB::statement("COMMENT ON COLUMN transaction_comments.comment IS 'コメント本文'");
        DB::statement("COMMENT ON COLUMN transaction_comments.created_at IS '投稿日時'");
        DB::statement("COMMENT ON COLUMN transaction_comments.updated_at IS '更新日時'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_comments');
        Schema::dropIfExists('transaction_attachments');
        Schema::dropIfExists('transaction_histories');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
