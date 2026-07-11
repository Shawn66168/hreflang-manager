#!/bin/bash
# export-category-map.sh — 從權威站的 post sitemap 產生「slug,分類路徑」對應表
# 用法: bin/export-category-map.sh https://example.com/post-sitemap.xml > map.csv
set -euo pipefail
SITEMAP="${1:?用法: export-category-map.sh <post-sitemap.xml URL>}"

BASE=$(echo "$SITEMAP" | sed -E 's|(https?://[^/]*).*|\1|')
curl -sL --max-time 30 "$SITEMAP" | tr -d '\r' \
  | grep -o '<loc>[^<]*</loc>' | sed 's/<[^>]*>//g' \
  | sed "s|$BASE/||; s|/$||" \
  | awk -F/ '{slug=$NF; path=""; for(i=1;i<NF;i++) path=path (i>1?"/":"") $i; if(path=="") path="."; print slug","path}' \
  | sort -t, -k1,1
