#!/usr/bin/env python3
"""plan-hreflang-cluster.py — 以權威站為準，比對多站內容，產生各站的 hreflang meta 寫入計畫。

為什麼需要：外掛的「同 slug 自動對應」只是「對方網域＋相同路徑」，不檢查目標是否存在；
各站分類路徑又常不一致（例如 /application-note/ vs /application-note-2/），
自動對應會大量指向不存在的網址。這支腳本改用內容比對，產生顯式的對應 URL 或「-」。

用法：
  1. 各站匯出（唯讀）：wp eval-file scripts/export-hreflang-inventory.php > inventory-<site>.json
  2. 產生計畫：bin/plan-hreflang-cluster.py --anchor en --out plans/ inventory-*.json
  3. 各站套用：wp eval-file scripts/apply-hreflang-plan.php plans/plan-<host>.json [apply]

規則：
  - 對應頁＝同 post type、同 slug（網址最後一段）的已發布頁面；不是 Yoast noindex。
    已手填且指向對方已發布頁面的 URL 優先採用（異 slug 對應靠這個）。
  - 權威站（--anchor）的頁面：對方站有對應頁 → 寫完整 URL；沒有 → 寫「-」。
  - 其他站的頁面：權威站沒有對應頁＝在地內容 → 所有語言寫「-」；
    有的話，照權威站那一組的成員互連，確保每一對都雙向。
  - 既有的「-」是人工判斷：一對頁面只要任一邊標了「-」，兩邊都維持「-」。
  - 只輸出「跟外掛目前實際輸出不同」的項目，已一致的不動。
"""
import argparse
import collections
import json
import os
from urllib.parse import unquote, urlparse

# 對應外掛 hreflang_get_legacy_meta_key()
LEGACY_KEY = {'zh-hant': 'alt_tw_url', 'es-419': 'alt_es_url'}
CONTENT_TYPES = ('post', 'page', 'product', 'portwell-event')


def norm(path):
    p = unquote(path or '/')
    return p if p.endswith('/') else p + '/'


def slug(path):
    parts = [x for x in norm(path).split('/') if x]
    return parts[-1] if parts else ''


def host_of(url):
    return (urlparse(url).hostname or '').lower()


def main():
    ap = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument('--anchor', required=True, help='權威站的語言代碼（hreflang_languages 的 code，例如 en）')
    ap.add_argument('--out', required=True, help='計畫檔輸出目錄')
    ap.add_argument('inventories', nargs='+', help='各站 export-hreflang-inventory.php 的輸出')
    args = ap.parse_args()

    inv = {}
    domain_of = {}
    for path in args.inventories:
        data = json.load(open(path, encoding='utf-8'))
        host = data['host'].lower()
        code = None
        for lang in data['languages']:
            if host_of(lang['domain']) == host:
                code = lang['code']
        if code is None:
            raise SystemExit('%s：在 hreflang_languages 找不到本站網域 %s' % (path, host))
        inv[code] = data
        domain_of[code] = 'https://' + host
    if args.anchor not in inv:
        raise SystemExit('沒有權威站 %s 的 inventory' % args.anchor)

    key_of = {c: 'alt_%s_url' % c for c in inv}

    # 每站可對應的已發布內容：(type, slug) -> [post]、path -> post
    by_slug, by_path = {}, {}
    for code, data in inv.items():
        bs, bp = collections.defaultdict(list), {}
        for p in data['posts']:
            p['meta'] = p['meta'] or {}
            p['np'] = norm(p['path'])
            live = (not p['query'] and p['np'] != '/' and str(p['noindex']) != '1'
                    and p['type'] in CONTENT_TYPES) or p['id'] == data['shop']
            if live and p['id'] != data['front']:
                bs[(p['type'], slug(p['path']))].append(p)
                bp[p['np']] = p
        by_slug[code], by_path[code] = bs, bp

    def raw_meta(p, code):
        v = (p['meta'].get(key_of[code]) or '').strip()
        if v == '' and code in LEGACY_KEY:
            lv = (p['meta'].get(LEGACY_KEY[code]) or '').strip()
            if lv == '-' or lv.startswith('http'):
                v = lv
        return v

    def effective(src, p, code):
        """外掛目前對這個語言實際輸出的值（None＝不輸出）。"""
        v = raw_meta(p, code)
        if v:
            return v
        if inv[src]['auto'] and p['path'] not in ('', '/') and not p['query']:
            return domain_of[code] + p['path']
        return None

    def counterpart(src, p, dst):
        v = raw_meta(p, dst)
        if v.startswith('http') and host_of(v) == host_of(domain_of[dst]):
            hit = by_path[dst].get(norm(urlparse(v).path))
            if hit is not None:
                return hit
        cands = by_slug[dst].get((p['type'], slug(p['path'])), [])
        exact = [c for c in cands if c['np'] == p['np']]
        if exact:
            return exact[0]
        return cands[0] if len(cands) == 1 else None

    anchor = args.anchor
    os.makedirs(args.out, exist_ok=True)
    for src, data in inv.items():
        rows, local = [], 0
        for plist in by_slug[src].values():
            for p in plist:
                head = p if src == anchor else counterpart(src, p, anchor)
                group = {}
                if head is not None:
                    group[anchor] = head
                    for dst in inv:
                        if dst == anchor:
                            continue
                        group[dst] = p if dst == src else counterpart(anchor, head, dst)
                    group = {k: v for k, v in group.items() if v is not None}
                    if src != anchor and group.get(src) is not p:
                        group = {}
                if not group:
                    local += 1
                for dst in inv:
                    if dst == src:
                        continue
                    have = effective(src, p, dst)
                    if dst in group and (have == '-' or effective(dst, group[dst], src) == '-'):
                        want = '-'
                    else:
                        want = domain_of[dst] + group[dst]['path'] if dst in group else '-'
                    if have != want:
                        rows.append({'id': p['id'], 'key': key_of[dst], 'want': want})
        out = os.path.join(args.out, 'plan-%s.json' % data['host'])
        json.dump(rows, open(out, 'w', encoding='utf-8'), ensure_ascii=False)
        counts = collections.Counter((r['key'], 'dash' if r['want'] == '-' else 'url') for r in rows)
        print('%-24s %4d 筆  在地內容 %d  %s' % (data['host'], len(rows), local, dict(sorted(counts.items()))))


if __name__ == '__main__':
    main()
