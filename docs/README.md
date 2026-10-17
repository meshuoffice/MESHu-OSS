# MESHu (B2B Platform) ドキュメント

MESHu（メッシュ）は、企業間の受発注・見積・取引連携・チャット・お知らせ配信・事務局サポートをシームレスにつなぐオープンソースのB2Bプラットフォームです。
本システムは **Laravel 12 / PostgreSQL 16+** を基盤とし、高い堅牢性、保守性、網羅的な自動テスト（Feature Test）を備えたモダンなアーキテクチャで構築されています。

> **【プロジェクト理念】**  
> MESHu は「誰もデータを覗かせず、事務局を1社が独占しない」中立な非営利技術コンソーシアムによる公共インフラ運営を目指しています。  
> 利益追求ではなく、参画企業で維持費を支え合い、得られた資金は全額セキュリティと堅牢性の向上へ再投資されます。詳細は [プロジェクト理念 (README.md)](../README.md#-プロジェクト理念-the-meshu-philosophy) をご覧ください。

本ディレクトリ（`docs/`）では、システムの全体アーキテクチャ、データベース設計、各機能の仕様書を管理しています。

---

## ドキュメント一覧

### 1. システムアーキテクチャ (`docs/architecture/`)
- [01_system_overview.md](./architecture/01_system_overview.md)
  - システム理念・全体構成・技術スタック（Laravel 12 / PHP 8.2+ / PostgreSQL 16+）
  - テナント分離・URL設計・認証認可モデル・セキュリティ設計
  - ディレクトリ構造とコンポーネント配置方針
- [02_oss_distribution_guide.md](./architecture/02_oss_distribution_guide.md)
  - OSS配布ガイドおよび事務局（Admin）モジュール非公開管理方針
  - 除外対象ファイル一覧、モジュラー設計、自動同期スクリプトの使い方

### 2. データベース設計 (`docs/database/`)
- [01_schema_and_er.md](./database/01_schema_and_er.md)
  - 全体ER図（Mermaid）
  - テーブル定義一覧（companies, users, company_links, user_assigned_links, invitations, transactions, transaction_items, transaction_attachments, transaction_comments, notices, notice_attachments, chat_messages, chat_message_attachments, chat_read_statuses, password_reset_tokens）
  - PostgreSQLカスタムENUM型・制約・インデックス方針
  - 取引ステータスマトリクスおよび Laravel Eloquent モデル / マイグレーションとの対応

### 3. 機能仕様書 (`docs/specs/`)
- [01_admin_management.md](./specs/01_admin_management.md)
  - 事務局管理機能（シークレットURLログイン、企業審査・承認、代理ログイン、事務局アカウント管理）
- [02_company_and_users.md](./specs/02_company_and_users.md)
  - 企業情報・組織設定（企業情報メンテ、従業員管理CRUD、権限、担当企業割当制御、マイプロフィール）
- [03_company_links.md](./specs/03_company_links.md)
  - つながり管理（取引先企業検索、連携申請・承認/否認、稼働/停止ステータス制御、担当者割当）
- [04_invitations.md](./specs/04_invitations.md)
  - 招待管理（未登録企業招待、法人番号重複チェック、招待経由登録時の自動つながり生成、invitation_company_id保持）
- [05_transactions.md](./specs/05_transactions.md)
  - 取引管理（見積・注文・回答・計算・可視性制御ポリシー、端数処理、ステータス遷移）
- [06_notices.md](./specs/06_notices.md)
  - お知らせ機能（受信お知らせ閲覧、自社発信お知らせ配信管理、開示対象制御、BYTEA添付ファイル）
- [07_chats.md](./specs/07_chats.md)
  - チャット機能（企業間メッセージ送受信、BYTEAファイル添付、既読管理、通知配信マトリクスメール送信）
- [08_auth_and_reminders.md](./specs/08_auth_and_reminders.md)
  - 企業別認証・パスワード再設定（url_code企業専用ログイン、SHA-256トークンハッシュ化、ワンタイム再設定）
- [09_contact.md](./specs/09_contact.md)
  - 事務局へのお問い合わせ（3ステップ画面遷移、添付ファイル一時保持、事務局メイン/サブ/ユーザー自動返信配信）
- [10_manual_and_help.md](./specs/10_manual_and_help.md)
  - 操作マニュアル・ヘルプ（機能別操作マニュアル、FAQアコーディオン、横断検索、関連機能クイックリンク、サポート窓口導線）
