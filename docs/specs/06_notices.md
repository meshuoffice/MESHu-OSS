# 機能仕様書: お知らせ機能（受信閲覧・自社配信管理）

## 1. 概要
MESHu プラットフォームにおいて、システム運営事務局からの重要連絡・メンテナンス告知、および取引先企業や自社からの業務連絡・お知らせを配信・閲覧・管理する機能です。

---

## 2. 権限とアクセススコープ

| 機能 | 対象ユーザー | URLパス |
| :--- | :--- | :--- |
| **受信お知らせ一覧・詳細** | 全従業員（管理者 `admin` / 一般 `staff`） | `/notice`, `/notice/show/{id}` |
| **添付ファイルダウンロード** | 全従業員（閲覧権限のあるお知らせ） | `/notice/attachment/{id}` |
| **自社お知らせ配信管理** | 管理者（`role === 'admin'` のみ） | `/notice/manage/*` |
| **事務局全体お知らせ配信** | 事務局オーナー（`owners`） | `/{ADMIN_URL_PATH}/notices/*` |

---

## 3. 受信お知らせ機能（`/notice`）仕様

### 3.1. 閲覧対象ルール
ログイン企業が閲覧可能なお知らせは以下の通りです：
1. **事務局発信（`sender_type = 'admin'`）**:
   - `target_scope = 'all'`: 全企業に公開。
   - `target_scope = 'verified'`: 認証済み（`status = 'active'`）企業のみに公開。
   - `target_scope = 'unverified'`: 未認証企業のみに公開。
   - `is_published = true` かつ `published_at <= NOW()` のもの。
2. **取引先発信（`sender_type = 'company'`）**:
   - つながりステータスが承認済み（`link_status = 'approved'`）の取引先企業が発信した公開お知らせ。
3. **自社発信（`sender_type = 'company'` かつ `company_id = 自社ID`）**:
   - 自社が作成・公開したお知らせ。

### 3.2. 一覧表示と絞り込み
- **表示項目**: カテゴリバッジ、重要バッジ、タイトル、発信元（事務局 / 取引先企業名 / 自社）、掲載日時、閲覧数、添付ファイル有無。
- **ソート順**: 重要フラグ（`is_important = true`）を最上部に固定 $\rightarrow$ `published_at DESC`。
- **絞り込み条件**:
  - キーワード検索（タイトル・本文部分一致）
  - カテゴリ（重要 `important` / メンテナンス `maintenance` / お知らせ `info`）
  - 発信元区分（すべて / 事務局 / つながり企業 / 自社）
  - 重要なお知らせのみ表示
- **詳細閲覧時のカウントアップ**:
  - 企業側ユーザーが詳細画面（`/notice/show/{id}`）を閲覧した際、`view_count` を自動で +1 インクリメント。

---

## 4. 自社発信お知らせ配信管理（`/notice/manage`）仕様

企業管理者（`admin`）が自社の取引先（承認済みつながり企業）に向けてお知らせを発信・管理する機能です。

### 4.1. 画面構成とCRUDフロー
1. **一覧（`/notice/manage`）**: 自社が発信したお知らせ一覧（公開中 / 下書き、掲載日時、閲覧数、編集・削除リンク）。
2. **新規作成・編集（`/notice/manage/new`, `/notice/manage/edit/{id}`）**:
   - 入力項目: タイトル（必須）、カテゴリ（必須）、重要固定フラグ（任意）、本文（必須）、添付ファイル（任意、最大10MB）、公開ステータス（公開 / 下書き保存）。
3. **確認画面（`/notice/manage/confirm`）**: 入力内容プレビュー。
4. **完了（`/notice/manage/complete/{id}`）**: 登録・更新完了。
5. **削除（`/notice/manage/delete/{id}`）**: 確認画面を経て物理削除（添付ファイルもカスケード削除）。

### 4.2. 添付ファイル管理仕様
- **保存方式**: PostgreSQL `BYTEA` 型バイナリ直接格納（`notice_attachments` テーブル）。
- **対応フォーマット**: PDF, PNG, JPG, JPEG, GIF, CSV, XLSX, ZIP。
- **ダウンロード配信**: 権限チェック後、適切な Content-Type および Content-Disposition ヘッダーを付与してストリーム配信。
