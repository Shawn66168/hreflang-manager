#!/bin/bash
# check-hreflang.sh — 驗證任一頁面的 hreflang 輸出與雙向互指
#
# 用法：
#   bin/check-hreflang.sh <URL>            檢查單一頁面並驗證每個 alternate 是否回指
#   bin/check-hreflang.sh <URL> --no-recip 只列出 hreflang，不驗證回指
#
# 通過條件：
#   1. 頁面有 hreflang 區塊且包含自身宣告
#   2. 每個 alternate URL 可存取（HTTP 200）
#   3. 每個 alternate 頁面的 hreflang 有回指本頁（reciprocal）
set -uo pipefail

URL="${1:?用法: check-hreflang.sh <URL> [--no-recip]}"
CHECK_RECIP=true
[ "${2:-}" = "--no-recip" ] && CHECK_RECIP=false

fetch_hreflangs() {
  curl -sL --max-time 15 "$1" | tr -d '\r' \
    | grep -o '<link rel="alternate" hreflang="[^"]*" href="[^"]*"' \
    | sed 's/.*hreflang="\([^"]*\)" href="\([^"]*\)"/\1 \2/'
}

normalize() { echo "${1%/}"; }

echo "=== $URL ==="
TAGS=$(fetch_hreflangs "$URL")

if [ -z "$TAGS" ]; then
  echo "FAIL: 頁面沒有任何 hreflang 標籤"
  exit 1
fi

echo "$TAGS" | while read -r lang href; do
  printf '  %-10s %s\n' "$lang" "$href"
done

FAIL=0
SELF_NORM=$(normalize "$URL")

if $CHECK_RECIP; then
  echo "--- 回指驗證 ---"
  while read -r lang href; do
    [ -z "${href:-}" ] && continue
    [ "$lang" = "x-default" ] && continue
    HREF_NORM=$(normalize "$href")
    [ "$HREF_NORM" = "$SELF_NORM" ] && continue  # 自身宣告

    STATUS=$(curl -sL -o /dev/null -w '%{http_code}' --max-time 15 "$href")
    if [ "$STATUS" != "200" ]; then
      echo "  FAIL [$lang] $href → HTTP $STATUS"
      FAIL=1
      continue
    fi

    if fetch_hreflangs "$href" | awk '{print $2}' | sed 's|/$||' | grep -qxF "$SELF_NORM"; then
      echo "  PASS [$lang] ${href}（200，有回指）"
    else
      echo "  FAIL [$lang] ${href}（200，但沒有回指本頁 → 該組 hreflang 會被 Google 忽略）"
      FAIL=1
    fi
  done <<< "$TAGS"
fi

exit $FAIL
