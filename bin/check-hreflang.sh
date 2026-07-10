#!/bin/bash
# check-hreflang.sh — 驗證頁面的 hreflang 輸出與雙向互指
#
# 用法：
#   bin/check-hreflang.sh <URL>                     檢查單頁並驗證每個 alternate 回指
#   bin/check-hreflang.sh <URL> --no-recip          只列出 hreflang，不驗證回指
#   bin/check-hreflang.sh --sitemap <sitemap.xml> [N]   批次檢查 sitemap 前 N 頁（預設 10）
#
# 通過條件：
#   1. 頁面有 hreflang 區塊且包含自身宣告
#   2. 每個 alternate URL 可存取（HTTP 200）
#   3. 每個 alternate 頁面的 hreflang 有回指本頁（reciprocal）
set -uo pipefail

fetch_hreflangs() {
  curl -sL --max-time 15 "$1" | tr -d '\r' \
    | grep -o '<link rel="alternate" hreflang="[^"]*" href="[^"]*"' \
    | sed 's/.*hreflang="\([^"]*\)" href="\([^"]*\)"/\1 \2/'
}

normalize() { echo "${1%/}"; }

# 檢查單一頁面；回傳 0=PASS、1=FAIL
check_page() {
  local url="$1" check_recip="$2" fail=0
  local tags self_norm

  echo "=== $url ==="
  tags=$(fetch_hreflangs "$url")

  if [ -z "$tags" ]; then
    echo "FAIL: 頁面沒有任何 hreflang 標籤"
    return 1
  fi

  echo "$tags" | while read -r lang href; do
    printf '  %-10s %s\n' "$lang" "$href"
  done

  self_norm=$(normalize "$url")

  local alt_count
  alt_count=$(echo "$tags" | awk -v self="$self_norm" '$1 != "x-default" { sub(/\/$/, "", $2); if ($2 != self) n++ } END { print n+0 }')
  if [ "$alt_count" -eq 0 ]; then
    echo "  WARN: 只有自身宣告，沒有任何 alternate（未填對應 URL 或自動對應未啟用）"
  fi

  [ "$check_recip" = false ] && return 0

  echo "--- 回指驗證 ---"
  while read -r lang href; do
    [ -z "${href:-}" ] && continue
    [ "$lang" = "x-default" ] && continue
    local href_norm status
    href_norm=$(normalize "$href")
    [ "$href_norm" = "$self_norm" ] && continue  # 自身宣告

    status=$(curl -sL -o /dev/null -w '%{http_code}' --max-time 15 "$href")
    if [ "$status" != "200" ]; then
      echo "  FAIL [$lang] $href → HTTP $status"
      fail=1
      continue
    fi

    if fetch_hreflangs "$href" | awk '{print $2}' | sed 's|/$||' | grep -qxF "$self_norm"; then
      echo "  PASS [$lang] ${href}（200，有回指）"
    else
      echo "  FAIL [$lang] ${href}（200，但沒有回指本頁 → 該組 hreflang 會被 Google 忽略）"
      fail=1
    fi
  done <<< "$tags"

  return $fail
}

if [ "${1:-}" = "--sitemap" ]; then
  SITEMAP="${2:?用法: check-hreflang.sh --sitemap <sitemap.xml> [N]}"
  LIMIT="${3:-10}"
  PASS=0; FAILED=0; FAILED_URLS=""

  URLS=$(curl -sL --max-time 20 "$SITEMAP" | tr -d '\r' \
    | grep -o '<loc>[^<]*</loc>' | sed 's/<[^>]*>//g' | head -n "$LIMIT")
  [ -z "$URLS" ] && { echo "FAIL: sitemap 讀不到任何 <loc>"; exit 1; }

  while read -r u; do
    [ -z "$u" ] && continue
    if check_page "$u" true; then PASS=$((PASS+1)); else FAILED=$((FAILED+1)); FAILED_URLS="$FAILED_URLS$u"$'\n'; fi
    echo
  done <<< "$URLS"

  echo "================ 總結 ================"
  echo "PASS: $PASS / $((PASS+FAILED))"
  if [ "$FAILED" -gt 0 ]; then
    echo "需處理（手填對應 URL 或補發文章）："
    printf '%s' "$FAILED_URLS" | sed 's/^/  - /'
    exit 1
  fi
  exit 0
fi

URL="${1:?用法: check-hreflang.sh <URL> [--no-recip] 或 --sitemap <xml> [N]}"
CHECK_RECIP=true
[ "${2:-}" = "--no-recip" ] && CHECK_RECIP=false

check_page "$URL" "$CHECK_RECIP"
exit $?
