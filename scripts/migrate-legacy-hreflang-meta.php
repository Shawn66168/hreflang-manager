<?php
/**
 * migrate-legacy-hreflang-meta.php — 例外優先搬移改版前的 hreflang 網址
 *
 * 背景：外掛把 alt URL meta key 命名從 alt_tw_url/alt_es_url 改成依
 * hreflang_languages 語言代碼命名的 alt_zh-hant_url/alt_es-419_url，
 * 但近千篇文章的既有資料還在舊 key 下，新版讀不到。
 *
 * 策略：不整批搬移。舊網址如果跟「同 slug 自動對應」（該語言網域＋相同路徑）
 * 會產生的預設網址一致，就不寫——之後交給自動對應規則處理，降低未來
 * 維護成本；只有網址跟預設規則不同的才是真正需要明確設定的例外，才寫進
 * 新 key。只新增寫入，不刪除、不覆蓋任何既有資料（新 key 已有值＝略過）。
 *
 * 用法（wp eval-file）：
 *   wp eval-file migrate-legacy-hreflang-meta.php          # dry-run：只報告
 *   wp eval-file migrate-legacy-hreflang-meta.php apply    # 實際寫入
 */

$apply = in_array('apply', $args, true);
$mode  = $apply ? 'APPLY' : 'DRY-RUN';
WP_CLI::log("=== migrate-legacy-hreflang-meta [$mode] ===");

$languages = get_option('hreflang_languages', []);
$lang_by_code = [];
foreach ($languages as $l) {
    $lang_by_code[$l['code']] = $l;
}

// 新語言代碼 → 舊 meta key
$legacy_map = [
    'zh-hant' => 'alt_tw_url',
    'es-419'  => 'alt_es_url',
];

function mlm_normalize($url) {
    if (!$url) return '';
    $parsed = wp_parse_url($url);
    if (!$parsed || empty($parsed['host'])) return trim($url);
    $path = trailingslashit('/' . ltrim($parsed['path'] ?? '/', '/'));
    $query = isset($parsed['query']) ? ('?' . $parsed['query']) : '';
    return strtolower($parsed['host']) . $path . $query;
}

function mlm_default_url($post_id, $domain) {
    $permalink = get_permalink($post_id);
    if (!$permalink) return '';
    $parsed = wp_parse_url($permalink);
    $path = $parsed['path'] ?? '';
    if ($path === '' || $path === '/' || !empty($parsed['query'])) return '';
    return untrailingslashit($domain) . $path;
}

global $wpdb;
$stats = [];

foreach ($legacy_map as $new_code => $legacy_key) {
    $stats[$new_code] = ['examined' => 0, 'skip_new_set' => 0, 'skip_empty' => 0, 'matches_default' => 0, 'write_exception' => 0];

    if (empty($lang_by_code[$new_code]['domain'])) {
        WP_CLI::warning("略過 {$new_code}：hreflang_languages 設定裡找不到網域，無法判斷預設規則");
        continue;
    }
    $domain = $lang_by_code[$new_code]['domain'];
    $new_key = 'alt_' . $new_code . '_url';

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT pm.post_id, pm.meta_value
         FROM {$wpdb->postmeta} pm
         JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s
           AND pm.meta_value != ''
           AND p.post_type NOT IN ('revision', 'auto-draft')
           AND p.post_status != 'trash'",
        $legacy_key
    ));

    foreach ($rows as $row) {
        $post_id = (int) $row->post_id;
        $legacy_value = trim($row->meta_value);
        $stats[$new_code]['examined']++;

        if ($legacy_value === '' || $legacy_value === '-') {
            $stats[$new_code]['skip_empty']++;
            continue;
        }

        $existing_new = trim((string) get_post_meta($post_id, $new_key, true));
        if ($existing_new !== '') {
            $stats[$new_code]['skip_new_set']++;
            continue;
        }

        $default_url = mlm_default_url($post_id, $domain);
        if ($default_url !== '' && mlm_normalize($legacy_value) === mlm_normalize($default_url)) {
            $stats[$new_code]['matches_default']++;
            continue;
        }

        $post_type = get_post_type($post_id);
        WP_CLI::log("  [$mode] #{$post_id} ({$post_type}) {$legacy_key} -> {$new_key}: {$legacy_value}");
        $stats[$new_code]['write_exception']++;
        if ($apply) {
            update_post_meta($post_id, $new_key, esc_url_raw($legacy_value));
        }
    }
}

foreach ($stats as $code => $s) {
    WP_CLI::log(sprintf(
        '[%s] %s：檢視 %d，符合預設規則略過 %d，新 key 已有值略過 %d，空值/無對應略過 %d，寫入例外 %d',
        $mode,
        $code,
        $s['examined'],
        $s['matches_default'],
        $s['skip_new_set'],
        $s['skip_empty'],
        $s['write_exception']
    ));
}

WP_CLI::success($apply ? '已寫入例外資料。' : '確認數字與清單無誤後加 "apply" 執行。');
