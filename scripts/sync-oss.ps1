# ====================================================================
# MESHu OSS 公開用ブランチ自動生成・同期スクリプト (PowerShell)
# 
# 目的:
#   事務局（Admin）独自機能・モジュールを完全に除外した状態で、
#   OSS 公開用ブランチ (デフォルト: release/oss) を安全に生成・同期します。
#
# 使い方:
#   .\scripts\sync-oss.ps1 [-TargetBranch "release/oss"] [-Push]
# ====================================================================

[CmdletBinding()]
param(
    [string]$TargetBranch = "release/oss",
    [switch]$Push
)

$ErrorActionPreference = "Stop"

Write-Host "=== MESHu OSS 公開用ブランチ同期処理を開始します ===" -ForegroundColor Cyan
Write-Host "対象ブランチ: $TargetBranch"

# 1. ワークツリーの変更確認
$status = git status --porcelain
if ($status) {
    Write-Warning "未コミットの変更が存在します。現在の作業をコミットまたはスタッシュしてから実行してください。"
    git status -s
    exit 1
}

$currentBranch = (git branch --show-current).Trim()
Write-Host "現在の作業ブランチ: $currentBranch"

# 2. 一時作業ブランチの作成
$tempBranch = "temp/oss-sync-" + (Get-Date -Format "yyyyMMdd-HHmmss")
Write-Host "一時作業ブランチを作成: $tempBranch"
git checkout -b $tempBranch

try {
    # 3. 事務局非公開対象の削除
    Write-Host "事務局関連ファイル（Admin機能）を除外中..." -ForegroundColor Yellow

    $excludePaths = @(
        "src/modules/Admin",
        "src/tests/Feature/Admin",
        "docs/specs/01_admin_management.md"
    )

    foreach ($path in $excludePaths) {
        if (Test-Path $path) {
            git rm -rf --ignore-unmatch $path
            Write-Host "  [除外完了] $path" -ForegroundColor Green
        }
    }

    # 4. コミットの作成
    $commitMsg = "chore(oss): 事務局非公開モジュールを除外したOSS公開用ツリーの生成 (from $currentBranch)"
    git commit -m "$commitMsg" --allow-empty

    # 5. 対象ブランチ（release/oss）へ反映
    Write-Host "公開用ブランチ ($TargetBranch) へ反映中..." -ForegroundColor Cyan
    git branch -f $TargetBranch $tempBranch

    Write-Host "=== OSS 公開用ブランチの生成が正常に完了しました ===" -ForegroundColor Green
    Write-Host "ローカルブランチ: $TargetBranch"

    # 6. リモートへプッシュする場合
    if ($Push) {
        Write-Host "GitHub リモート ($TargetBranch) へプッシュを実行中..." -ForegroundColor Cyan
        git push origin $TargetBranch -f
        Write-Host "プッシュが完了しました。" -ForegroundColor Green
    } else {
        Write-Host "※ リモートへのプッシュはスキップされました。" -ForegroundColor Yellow
        Write-Host "   プッシュする場合は以下を実行してください："
        Write-Host "   git push origin $TargetBranch" -ForegroundColor White
    }

} finally {
    # 7. 元のブランチへ復帰し一時ブランチを削除
    Write-Host "元のブランチ ($currentBranch) に復帰中..."
    git checkout $currentBranch
    git branch -D $tempBranch
}
