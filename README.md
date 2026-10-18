# MESHu (B2B Transaction Platform)

<p align="center">
  <strong>誰もデータを独占せず、特定の1社に覗かせない。</strong><br>
  <strong>中立な非営利技術コンソーシアム運営を目指す、オープンソースのB2B取引プラットフォーム。</strong>
</p>

<p align="center">
  <a href="https://www.gnu.org/licenses/agpl-3.0"><img src="https://img.shields.io/badge/License-AGPL_v3-blue.svg" alt="License: AGPL v3"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php" alt="PHP Version"></a>
  <a href="https://laravel.com/"><img src="https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel" alt="Laravel Version"></a>
  <a href="https://www.postgresql.org/"><img src="https://img.shields.io/badge/PostgreSQL-16%2B-336791?logo=postgresql" alt="PostgreSQL"></a>
</p>

<p align="center">
  <a href="#-プロジェクト理念-the-meshu-philosophy">プロジェクト理念</a> •
  <a href="#-主な機能">機能一覧</a> •
  <a href="#-クイックスタート-docker">クイックスタート</a> •
  <a href="#-セキュリティへのコミットメント">セキュリティ</a> •
  <a href="#-賛同企業パートナーの募集">パートナー募集</a> •
  <a href="./docs/README.md">ドキュメント</a>
</p>

---

## 💡 プロジェクト理念 (The MESHu Philosophy)

### 1. 「特定の1社がデータを覗き見たり、独占して利益を貪る構造にしない」
企業間取引データ（見積条件、受発注履歴、単価、チャットでの商談）は、各企業にとって最も重要な機密情報です。
従来の商用SaaSでは、プラットフォーム運営企業にデータが集約され、将来的な利用料の引き上げやデータの囲い込み・二次利用といった構造的リスクが存在していました。

MESHu は、特定の営利企業が取引データを私物化したり独占することを拒否し、**「公共のデジタル公道（インフラ）」** として機能することを目指しています。

### 2. 「非営利の技術コンソーシアム」による中立運営
MESHu は、将来的には参加企業や業界団体が共同で管理・相互監視する **非営利の技術コンソーシアム（一般社団法人等）** による運営を志向しています（ドメインに `.org` を採用している理由もここにあります）。
誰か1社の恣意的な判断でルールが変えられたり、サービスが打ち切られることのない、永続的で中立な取引基盤を提供します。

### 3. 「健全な運営体制を維持し、余剰金はセキュリティと堅牢性へ積極投資」
MESHu は株主配当や役員報酬のための利益追求を行いません。
プラットフォームの維持分担金や賛助金として得られた資金は、**健全で持続可能な運営・保守体制の維持を最優先としつつ、余剰金は積極的にセキュリティ対策（WAF多重防御、第三者機関による侵入診断、改ざん防止監査ログ、暗号化、災害対策）へ再投資** されます。
利益を削ってセキュリティを妥協する商業ツールとは一線を画す、圧倒的な堅牢性を実現します。

---

## 🌟 主な機能

- 🤝 **つながり管理 (Company Links)**: 取引先企業の検索、連携申請・承認、担当者ごとの権限割当
- 📑 **取引管理 (Transactions)**: 見積依頼、見積回答、注文、受注承諾、適格請求書（インボイス）対応、端数処理・消費税計算、各種帳票ブラウザ印刷（A4対応）
- 💬 **企業間チャット (Chats)**: 取引先との1対1リアルタイムメッセージ、ファイル添付（最大20MB）、既読管理、厳格な担当者振り分け通知メール
- 📢 **お知らせ機能 (Notices)**: 事務局連絡、取引先発信業務連絡、自社お知らせ配信管理
- 📚 **マニュアル・ヘルプ (Manual & Help)**: システム操作マニュアル、FAQ（よくある質問）、企業退会申請・データ保護フロー
- 🏢 **マルチテナント企業認証**: 企業専用URL（`/{url_code}/login`）、SHA-256トークンによるセキュアなパスワード再設定

---

## 🚀 クイックスタート (Docker)

Docker がインストールされた環境であれば、以下のコマンドだけで数分でローカル環境が立ち上がります。

```bash
# 1. リポジトリのクローン
git clone https://github.com/meshuoffice/MESHu-OSS.git
cd MESHu-OSS

# 2. コンテナのビルド・起動 (Nginx, PHP 8.2, PostgreSQL 16, Mailpit)
docker compose up -d

# 3. 依存ライブラリのインストール & マイグレーション & 初期データ投入
docker exec meshu-laravel-app composer install
docker exec meshu-laravel-app php artisan migrate --seed

# 4. ブラウザでアクセス
# ・ポータル画面: http://localhost:8080
# ・メール確認用 Mailpit: http://localhost:8025
```

### 🔑 すぐに試せる初期デモアカウント

初期Seeder（`php artisan migrate --seed`）を実行すると、以下のテスト用企業アカウントが即座に利用可能となります。両社間には最初から「承認済みつながり（Link）」が確立されており、見積・受発注・チャットの相互送受信を即座に体験できます。

| 企業種別 | 企業名 | 専用ログインURL | メールアドレス（ID） | 初期パスワード | 特徴 |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **買手企業** | 株式会社サンプル商事 | [http://localhost:8080/sample-buyer/login](http://localhost:8080/sample-buyer/login) | `admin@buyer.example.com` | `Secret1234!` | 買手管理者（発注側） |
| **売手企業** | 株式会社テスト販売 | [http://localhost:8080/sample-seller/login](http://localhost:8080/sample-seller/login) | `admin@seller.example.com` | `Secret1234!` | 売手管理者・**MESHu認証バッジ付与済み** |

---

## 🛡️ セキュリティへのコミットメント

- **データベース保護**: PostgreSQL 16+ の ACID トランザクション、カスタム ENUM 型、CHECK 制約、BYTEA による安全なバイナリ分離。
- **暗号化とハッシュ化**: パスワードには Argon2id / BCRYPT、パスワード再設定トークンには SHA-256 ハッシュを適用。
- **厳格な参照分離**: `is_all_assigned` および `user_assigned_links` による厳格な担当外データへのアクセス遮断。
- **網羅的なテスト自動化**: 140件以上の Feature / Unit テストで保護（`php artisan test` で全件検証可能）。

---

## 🤝 賛同企業・パートナーの募集 (Call for Partners)

MESHu プロジェクトでは、**「中立で安全なB2B取引の未来」** を共に創り、育ててくださるパートナー企業・開発者・業界団体を募集しています：

1. **実証実験（PoC）への参加**:
   自社の取引先との受発注・連絡業務で実際に MESHu をテスト利用し、現場のフィードバックをいただける企業様。
2. **コンソーシアム設立への参画**:
   非営利コンソーシアムの設立趣旨に賛同し、共同運営・相互監視の枠組みに加わっていただける企業様。
3. **オープンソース・コントリビューター**:
   セキュリティ向上、機能改善、ドキュメント整備などに知恵と技術を貸していただけるエンジニア。

ご興味をお持ちいただけましたら、GitHub の Issue / Discussions または公式サイトのお問い合わせ窓口よりお気軽にお声がけください。

---

## 📖 ドキュメント一覧

詳細なシステム仕様書および設計資料は [`docs/`](./docs/README.md) にて公開しています：

- [システム全体アーキテクチャ概要](./docs/architecture/01_system_overview.md)
- [データベース設計書・全体ER図](./docs/database/01_schema_and_er.md)
- [OSS配布ガイド・非公開モジュール分離方針](./docs/architecture/02_oss_distribution_guide.md)
- [機能別詳細仕様書一覧](./docs/specs/)

---

## 📜 ライセンス

本ソフトウェアは [GNU Affero General Public License v3 (AGPL-3.0)](LICENSE) のもとで公開されています。

