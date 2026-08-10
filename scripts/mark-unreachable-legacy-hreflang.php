<?php
/**
 * mark-unreachable-legacy-hreflang.php — 反向掃描「舊 key 值不是有效網址」的文章
 *
 * 用於 migrate-legacy-hreflang-meta.php 因網址驗證失敗而跳過的文章：
 * 用「同 slug 自動對應」規則組出該語言的預設網址，實際打一次確認是否存在。
 * 存在 → 不寫，交給自動對應規則處理；不存在 → 寫入 "-" 明確標記無對應版本，
 * 避免自動規則之後對外宣告一個不存在的頁面。
 *
 * 用法（wp eval-file）：
 *   wp eval-file mark-unreachable-legacy-hreflang.php <legacy_key> <new_code>          # dry-run
 *   wp eval-file mark-unreachable-legacy-hreflang.php <legacy_key> <new_code> apply    # 實際寫入
 *
 * 例：wp eval-file mark-unreachable-legacy-hreflang.php alt_tw_url zh-hant apply
 */

$legacy_key = $args[0] ?? '';
$new_code = $args[1] ?? '';
$apply = in_array('apply', $args, true);
$mode = $apply ? 'APPLY' : 'DRY-RUN';

if ($legacy_key === '' || $new_code === '') {
    WP_CLI::error('用法: wp eval-file mark-unreachable-legacy-hreflang.php <legacy_key> <new_code> [apply]');
}

$languages = get_option('hreflang_languages', []);
$lang_by_code = [];
foreach ($languages as $l) {
    $lang_by_code[$l['code']] = $l;
}

if (empty($lang_by_code[$new_code]['domain'])) {
    WP_CLI::error("hreflang_languages 設定裡找不到語言代碼 {$new_code}");
}
$domain = $lang_by_code[$new_code]['domain'];
$new_key = 'alt_' . $new_code . '_url';

function mulh_default_url($post_id, $domain) {
    $permalink = get_permalink($post_id);
    if (!$permalink) return '';
    $parsed = wp_parse_url($permalink);
    $path = $parsed['path'] ?? '';
    if ($path === '' || $path === '/' || !empty($parsed['query'])) return '';
    return untrailingslashit($domain) . $path;
}

global $wpdb;
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT pm.post_id, pm.meta_value
     FROM {$wpdb->postmeta} pm
     JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE pm.meta_key = %s
       AND pm.meta_value != ''
       AND pm.meta_value != '-'
       AND pm.meta_value NOT REGEXP '^https?://'
       AND p.post_type NOT IN ('revision', 'auto-draft')
       AND p.post_status != 'trash'",
    $legacy_key
));

$stats = ['examined' => 0, 'skip_new_set' => 0, 'reachable' => 0, 'marked_dash' => 0, 'no_default' => 0];

foreach ($rows as $row) {
    $post_id = (int) $row->post_id;
    $stats['examined']++;

    $existing_new = trim((string) get_post_meta($post_id, $new_key, true));
    if ($existing_new !== '') {
        $stats['skip_new_set']++;
        continue;
    }

    $default_url = mulh_default_url($post_id, $domain);
    if ($default_url === '') {
        WP_CLI::log("  [$mode] #{$post_id} permalink 無法組出對應網址，略過");
        $stats['no_default']++;
        continue;
    }

    $response = wp_remote_get($default_url, ['timeout' => 10, 'redirection' => 3]);
    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);

    if ($code === 200) {
        WP_CLI::log("  [$mode] #{$post_id} 對應頁存在（{$default_url}），交給自動對應，不寫");
        $stats['reachable']++;
    } else {
        WP_CLI::log("  [$mode] #{$post_id} 對應頁不存在（{$default_url} -> HTTP {$code}），標記 \"-\"");
        $stats['marked_dash']++;
        if ($apply) {
            update_post_meta($post_id, $new_key, '-');
        }
    }
}

WP_CLI::log(sprintf(
    '[%s] 檢視 %d，新 key 已有值略過 %d，permalink 無法組網址 %d，對應頁存在略過 %d，標記無對應 "-" %d',
    $mode,
    $stats['examined'],
    $stats['skip_new_set'],
    $stats['no_default'],
    $stats['reachable'],
    $stats['marked_dash']
));
WP_CLI::success($apply ? '已完成標記。' : '確認無誤後加 "apply" 執行。');
