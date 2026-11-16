# MESHu セキュリティ検証仕様書 & 監査レポート（Security Audits & Penetration Test Reports）

自律分散型B2B受発注プロトコル「MESHu」における、マルチテナント認可制御（IDOR対策）、境界防御、および深層ビジネスロジックの堅牢性を実証するための公開セキュリティドキュメントです。

---

## 1. ドキュメント一覧

| ファイル | 分類 | 概要 |
| :--- | :--- | :--- |
| [**security_test_matrix.md**](./security_test_matrix.md) | **基本テスト仕様書** | アプリ層（IDOR・チャット機密性）およびインフラ層（不要ポート・PostgreSQL遮断）の基本監査チェックリスト。 |
| [**alisa_custom_attack_plan.md**](./alisa_custom_attack_plan.md) | **レッドチーム独自攻撃計画** | 現場AI社員（alisa）が攻撃者の視点で自律立案した、CWE脆弱性カタログに基づく深層急所シナリオ（マス・アサインメント、セッション破棄、競合状態等）。 |
| [**alisa_security_report_phase1.md**](./alisa_security_report_phase1.md) | **フェーズ1 監査レポート** | 第3企業アカウントによる他社IDORアクセス（すべて `403 Forbidden`）および本番ポートスキャン（全DBポート遮断）の完全合格（オールPASS）レポート。 |
| [**alisa_security_report_phase2.md**](./alisa_security_report_phase2.md) | **フェーズ2 監査レポート** | 自立立案した深層攻撃（マス・アサインメント、セッションハイジャック、並行競合リクエスト）のPoC検証と完全迎撃の合格レポート。 |

---

## 2. 監査の背景と目的
B2B取引において最も致命的となる「他社データの盗聴」および「取引の不正操作・なりすまし」が物理的・論理的に不可能であることを、ゼロトラストの原則（Security through obscurity の否定）に基づき客観的ログをもって証明・担保しています。

- **検証環境:** `http://mshoss:8080` (Docker / Laravel 12)
- **本番環境:** `https://meshu.org` (Ubuntu / Nginx / PostgreSQL)
- **テスター:** 現場AI社員 alisa（OpenClaw / Gemini 3.5 Flash Lite）

---

## 3. 実証実験・解説記事（Zenn連載）
本セキュリティ仕様書の策定背景、現場AI社員「alisa」が自律立案した経緯、および画面キャプチャを含む詳細な実証記録は、Zenn記事にて公開されています。

- 🔗 **Zenn第13弾:** [10年前のCeleronノートに住むAI社員を「ブラック化」させたら、自社B2Bシステムの急所を容赦なく突いてきた話【自律型AIペンテスト実践】](https://zenn.dev/meshu/articles/※公開後URL)

