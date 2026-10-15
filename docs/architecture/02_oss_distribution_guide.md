# OSS 配布および事務局モジュール非公開ガイド

## 1. 概要と方針
MESHu は企業間取引プラットフォームとして OSS（オープンソースソフトウェア）公開を前提に開発されていますが、**システム運営事務局機能（Admin）** については、以下のセキュリティおよびビジネス上の理由から初期リリース段階では **非公開（社内独自資産）** として運用します：

1. **攻撃対象領域（アタックサーフェス）の最小化**:
   - シークレットURL、事務局認証、IPホワイトリスト制限、代理ログイン（なりすまし）機構などの特権機能を外部の脅威から保護。
2. **運営ノウハウ・審査ロジックの保護**:
   - 企業の認証バッジ付与基準やプラットフォーム運営ロジックを差別化資産として保護。
3. **安全性の担保**:
   - 後から公開することはいつでも即座に可能ですが、一度パブリックGitHubに公開されたコードを完全に抹消することは困難であるため、安全側に倒した初期運用とします。

---

## 2. 非公開対象モジュール・ファイル一覧

以下のファイルおよびディレクトリは、OSS公開用ツリー（GitHub Public リポジトリ）から完全に除外されます：

| 対象パス | 種別 | 説明 |
| :--- | :--- | :--- |
| `src/modules/Admin/` | モジュール本体 | 事務局コントローラー、ルーティング、ミドルウェア、Bladeテンプレート |
| `src/tests/Feature/Admin/` | 自動テスト | 事務局機能専用のFeatureテストスイート |
| `docs/specs/01_admin_management.md` | 仕様書 | 事務局機能仕様書 |

---

## 3. アプリケーションの疎結合性（モジュラー設計）

事務局機能が除外された環境でも、MESHu 本体（ユーザー企業向け機能、受発注、見積、チャット、お知らせ、お問い合わせ等）は何のエラーも生じずに正常動作するように設計されています：

### サービスプロバイダの動的登録（`src/bootstrap/providers.php`）
```php
<?php

$providers = [
    App\Providers\AppServiceProvider::class,
];

// 事務局モジュールが存在する場合のみ登録（OSS公開版など事務局モジュール除外環境との互換性を担保）
if (class_exists(\Modules\Admin\AdminServiceProvider::class)) {
    $providers[] = \Modules\Admin\AdminServiceProvider::class;
}

return $providers;
```
これにより、`src/modules/Admin/` ディレクトリが存在しない OSS 公開環境でも、Laravel は自動的に該当プロバイダのロードをスキップし、安全にブートストラップされます。

---

## 4. OSS公開用ブランチの生成・同期手順

### 4.1. 自動同期スクリプトの実行
リポジトリ直下の `scripts/sync-oss.ps1`（Windows）または `scripts/sync-oss.sh`（Linux / Mac）を実行するだけで、現在のブランチから事務局機能を除外した公開用ブランチ（`release/oss`）が自動生成されます。

#### Windows (PowerShell) の場合:
```powershell
# 1. ローカルに release/oss ブランチを生成（確認用）
.\scripts\sync-oss.ps1

# 2. 生成と同時に GitHub リモートへ直接プッシュする場合
.\scripts\sync-oss.ps1 -Push
```

#### Linux / macOS / Bash の場合:
```bash
# 1. ローカルに release/oss ブランチを生成
./scripts/sync-oss.sh

# 2. リモートへプッシュ
./scripts/sync-oss.sh release/oss --push
```

---

## 5. 将来「事務局機能も公開する」と判断した場合の手順
将来的に事務局機能も含めて完全公開する判断を行った場合は、単に同期スクリプトによる除外を行わず、開発ブランチ（`main`）をそのまま公開リポジトリへマージ・プッシュするだけで、即座にフルスタックの完全 OSS として移行可能です。
