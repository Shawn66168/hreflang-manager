<?php
/**
 * export-hreflang-inventory.php — 匯出本站已發布內容與 hreflang meta，供 bin/plan-hreflang-cluster.py 比對。
 *
 * 唯讀，不寫入任何資料。每個站各跑一次，把輸出存成 inventory-<site>.json：
 *   wp eval-file scripts/export-hreflang-inventory.php > inventory-comtw.json
 *
 * 輸出 JSON：
 *   host, languages（hreflang_languages）, default, auto（同 slug 自動對應）, front（靜態首頁 ID）,
 *   shop（WooCommerce 商店頁 ID）, posts[]：{id, type, path, query, noindex, meta}
 * meta 只收 alt_<code>_url 與相容的舊 key（alt_tw_url、alt_es_url）。
 */

$types = array_values(array_diff(get_post_types(['public' => true]), ['attachment']));

$languages = get_option('hreflang_languages', []);
$keys = [];
foreach ((array) $languages as $lang) {
    if (!empty($lang['code'])) {
        $keys[] = 'alt_' . $lang['code'] . '_url';
    }
}
// 舊版 meta key（見 hreflang_get_legacy_meta_key）
$keys = array_values(array_unique(array_merge($keys, ['alt_tw_url', 'alt_es_url'])));

$out = [
    'host'      => wp_parse_url(home_url(), PHP_URL_HOST),
    'languages' => $languages,
    'default'   => get_option('hreflang_default_lang'),
    'auto'      => (int) get_option('hreflang_auto_same_slug'),
    'front'     => (int) get_option('page_on_front'),
    'shop'      => function_exists('wc_get_page_id') ? (int) wc_get_page_id('shop') : 0,
    'posts'     => [],
];

$ids = get_posts([
    'post_type'        => $types,
    'post_status'      => 'publish',
    'posts_per_page'   => -1,
    'fields'           => 'ids',
    'suppress_filters' => true,
]);

foreach ($ids as $id) {
    $parsed = wp_parse_url(get_permalink($id));
    $meta = new stdClass();
    foreach ($keys as $key) {
        $value = get_post_meta($id, $key, true);
        if ($value !== '') {
            $meta->$key = $value;
        }
    }
    $out['posts'][] = [
        'id'      => $id,
        'type'    => get_post_type($id),
        'path'    => $parsed['path'] ?? '',
        'query'   => $parsed['query'] ?? '',
        'noindex' => get_post_meta($id, '_yoast_wpseo_meta-robots-noindex', true),
        'meta'    => $meta,
    ];
}

echo wp_json_encode($out);
