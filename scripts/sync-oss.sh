#!/usr/bin/env bash
# ====================================================================
# MESHu OSS 公開用ブランチ自動生成・同期スクリプト (Bash)
#
# 使い方:
#   ./scripts/sync-oss.sh [target_branch] [--push]
# ====================================================================

set -euo pipefail

TARGET_BRANCH="${1:-release/oss}"
PUSH_FLAG="${2:-}"

echo "=== MESHu OSS 公開用ブランチ同期処理を開始します ==="
echo "対象ブランチ: ${TARGET_BRANCH}"

if [[ -n $(git status --porcelain) ]]; then
    echo "エラー: 未コミットの変更が存在します。コミットまたはスタッシュ後に実行してください。"
    exit 1
fi

CURRENT_BRANCH=$(git branch --show-current)
TEMP_BRANCH="temp/oss-sync-$(date +%Y%m%d%H%M%S)"

echo "一時ブランチを作成: ${TEMP_BRANCH}"
git checkout -b "${TEMP_BRANCH}"

cleanup() {
    echo "元のブランチ (${CURRENT_BRANCH}) に復帰中..."
    git checkout "${CURRENT_BRANCH}"
    git branch -D "${TEMP_BRANCH}" 2>/dev/null || true
}
trap cleanup EXIT

echo "事務局関連ファイル（Admin機能）を除外中..."
EXCLUDE_PATHS=(
    "src/modules/Admin"
    "src/tests/Feature/Admin"
    "docs/specs/01_admin_management.md"
)

for path in "${EXCLUDE_PATHS[@]}"; do
    if [ -e "$path" ]; then
        git rm -rf --ignore-unmatch "$path"
        echo "  [除外完了] $path"
    fi
done

git commit -m "chore(oss): 事務局非公開モジュールを除外したOSS公開用ツリーの生成 (from ${CURRENT_BRANCH})" --allow-empty

echo "公開用ブランチ (${TARGET_BRANCH}) へ反映中..."
git branch -f "${TARGET_BRANCH}" "${TEMP_BRANCH}"

echo "=== OSS 公開用ブランチの生成が正常に完了しました ==="

if [[ "${PUSH_FLAG}" == "--push" ]]; then
    echo "GitHub リモート (${TARGET_BRANCH}) へプッシュ中..."
    git push origin "${TARGET_BRANCH}" -f
    echo "プッシュが完了しました。"
else
    echo "※ リモートへのプッシュはスキップされました。"
    echo "   プッシュする場合は: git push origin ${TARGET_BRANCH}"
fi
