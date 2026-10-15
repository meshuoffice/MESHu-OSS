# MESHu システム全体アーキテクチャ概要

## 1. システム理念と目的
MESHu（メッシュ）は、企業間（B2B）における受発注、見積、取引関係構築、チャットコミュニケーション、お知らせ配信をデジタル化・効率化するオープンソースプラットフォームです。
企業間の「つながり（Link）」をノードとエッジのネットワーク構造として管理し、安全かつ柔軟な取引基盤を提供します。

---

## 2. 技術スタック

| レイヤー | 採用技術 / バージョン | 選定理由・役割 |
| :--- | :--- | :--- |
| **バックエンド言語** | PHP 8.2+ | 型安全性、最新の言語機能（Typed Properties, Enums, Match文、Constructor Promotion等） |
| **フレームワーク** | Laravel 12 | モダンなWebアーキテクチャ、Eloquent ORM、Mailable、Bladeテンプレート、堅牢なDI・サービスコンテナ |
| **データベース** | PostgreSQL 16+ | 堅牢なACIDトランザクション、ENUM型・部分インデックス・CHECK制約・BYTEAバイナリ保存 |
| **フロントエンド** | HTML5 / Bootstrap 5.3 / Bootstrap Icons | レスポンシブUI、直感的なダッシュボード・操作性、一貫したデザインシステム |
| **コンテナ環境** | Docker / Docker Compose | 開発・本番環境の統一（AlmaLinux 9 + PHP 8.2, Nginx, PostgreSQL 16, Mailpit） |
| **Webサーバー** | Nginx 1.25+ (Alpine Linux) | 静的ファイル高速配信、PHP-FPMリバースプロキシ |
| **テスト自動化** | PHPUnit 11 / Laravel Testing | 140件以上の網羅的なFeature / Unitテストスイート（`php artisan test`） |
| **コードスタイル** | Laravel Pint / PSR-12 | コード規約の自動フォーマットおよび静的検証 |

---

## 3. アプリケーション構成

```text
src/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # 事務局（システム運営）専用コントローラー
│   │   │   ├── Auth/            # 企業別ログイン・パスワード再設定認証コントローラー
│   │   │   ├── Tenant/          # ユーザー企業ポータル向けコントローラー (Buy, Sell, Chat, Notice, Contact, etc.)
│   │   │   └── SignupController.php # パブリック企業アカウント新規登録コントローラー
│   │   └── Middleware/          # 認証・認可ミドルウェア (TenantAuth, AdminRole, AdminSecret, etc.)
│   ├── Mail/                    # Laravel Mailable クラス群 (通知メール、自動返信、招待メール等)
│   ├── Models/                  # Eloquent モデル群 (Company, User, Transaction, ChatMessage, etc.)
│   └── Services/                # ビジネスロジック・サービス層 (TransactionService, ChatNotificationService等)
├── bootstrap/                   # アプリケーション起動・プロバイダ登録
├── config/                      # アプリケーション設定 (auth, database, mail, app等)
├── database/
│   ├── migrations/              # DBマイグレーション定義 (PostgreSQL完全準拠)
│   └── seeders/                 # 初期シードデータ
├── resources/
│   └── views/                   # Bladeテンプレート
│       ├── admin/               # 事務局専用画面テンプレート
│       ├── contact/             # 事務局お問い合わせ画面テンプレート
│       ├── emails/              # メール送信用Bladeテンプレート (プレーンテキスト/HTML)
│       ├── layouts/             # 共通レイアウト (tenant.blade.php, app.blade.php)
│       └── tenant/              # 企業ポータル向け機能別テンプレート (chat, buy, sell, etc.)
├── routes/
│   ├── web.php                  # Webルーティング定義
│   └── console.php              # Artisan CLIコマンド定義
├── storage/                     # アップロード一時ファイル・ログ
└── tests/                       # 自動テストスイート
    ├── Feature/                 # 機能テスト (Tenant, Auth, Admin, etc.)
    └── Unit/                    # 単体テスト
```

---

## 4. テナント分離・URL設計と認証アーキテクチャ

### 4.1. 企業専用ログインURL（`/{url_code}/login`）
- 各企業は固有の識別コード（`url_code`）を持ちます。
- 従業員は自社専用URL（例: `https://meshu.org/{url_code}/login`）からログインします。
- ログインID（`email`）は**同一企業内でのみ一意（`UNIQUE(company_id, email)`）**であり、異なる企業であれば同一メールアドレスでの登録が可能です。
- パスワード再設定も企業コードスコープ下（`/{url_code}/forgot-password`, `/{url_code}/reset-password`）で安全に処理されます。

### 4.2. 事務局専用シークレットURL（`/{ADMIN_URL_PATH}/login`）
- 事務局（システム運営者）のログイン画面は、環境変数 `ADMIN_URL_PATH`（デフォルト: `admin`）で定義されるシークレットパスで保護されます。
- 一般ユーザー用セッションと事務局セッションは完全に分離されています。

### 4.3. 認証・認可ミドルウェア

| ミドルウェア名 | 対象スコープ | 主な処理 |
| :--- | :--- | :--- |
| `auth:web` | ユーザーポータル全般 | 企業ユーザーログイン状態を検証。未ログイン時はログイン画面へリダイレクト |
| `tenant.auth` | 企業ログイン・セッション | 企業の有効ステータス（`active`）を検証し、停止中企業のアクセスを遮断 |
| `admin.role` | 各種設定・管理メニュー | ログインユーザーの権限が `admin`（管理者）であるかを検証。`staff`（一般社員）の不正アクセスを遮断 |
| `admin.auth` | 事務局管理画面（`/own*`） | 事務局アカウント（`owners`）のログイン状態を検証 |
| `admin.ip` | 事務局ログイン・管理画面全体 | `.env` の `ADMIN_ALLOWED_IPS` に基づくIPアドレス制限。許可外アクセスを404で遮断 |

---

## 5. セキュリティ設計原則
1. **パスワード管理**: PHP `password_hash()` / Laravel `Hash::make()` によるセキュアハッシュ化（Argon2id / BCRYPT）。
2. **トークン保護**: パスワード再設定トークンは SHA-256 ハッシュ化してDB保管、有効期限30分・ワンタイム消費。
3. **SQLインジェクション対策**: Eloquent ORM および Query Builder のバインディングによる完全保護。
4. **XSS対策**: Blade テンプレートエンジンによる `{{ $var }}` 自動エスケープ。
5. **CSRF対策**: Laravel 標準の `@csrf` トークン保護を全フォーム・POST通信に適用。
6. **アクセス制御**: 取引先リンク担当（`is_all_assigned` / `user_assigned_links`）による厳格なデータ参照分離。
