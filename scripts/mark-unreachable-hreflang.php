<?php
/**
 * mark-unreachable-hreflang.php — 掃描指定 post type，沒有明確填寫某語言 alt URL 的文章，
 * 用「同 slug 自動對應」規則組出該語言的預設網址，實際打一次確認是否存在。
 *
 * 用途：新語言站（例如內容還沒補齊的 comcn）加入 hreflang_languages 集群後，
 * hreflang_auto_same_slug 開啟時會對「所有沒填 meta 的文章」盲猜同路徑網址，
 * 不檢查目標頁面是否真的存在。這支腳本反過來驗證一次：
 *   存在 → 不寫，交給自動對應規則處理；
 *   不存在 → 寫入 "-" 明確標記無對應版本，避免自動規則之後對外宣告一個不存在的頁面。
 *
 * 用法（wp eval-file，在來源站台執行，例如 comtw/tw/cl）：
 *   wp eval-file mark-unreachable-hreflang.php <lang_code> <post_types_csv>          # dry-run
 *   wp eval-file mark-unreachable-hreflang.php <lang_code> <post_types_csv> apply    # 實際寫入
 *
 * 例：wp eval-file mark-unreachable-hreflang.php zh-hans post,page apply
 */

$lang_code = $args[0] ?? '';
$post_types_csv = $args[1] ?? '';
$apply = in_array('apply', $args, true);
$mode = $apply ? 'APPLY' : 'DRY-RUN';

if ($lang_code === '' || $post_types_csv === '') {
    WP_CLI::error('用法: wp eval-file mark-unreachable-hreflang.php <lang_code> <post_types_csv> [apply]');
}

$post_types = array_filter(array_map('trim', explode(',', $post_types_csv)));

$languages = get_option('hreflang_languages', []);
$lang_by_code = [];
foreach ($languages as $l) {
    $lang_by_code[$l['code']] = $l;
}

if (empty($lang_by_code[$lang_code]['domain'])) {
    WP_CLI::error("hreflang_languages 設定裡找不到語言代碼 {$lang_code}");
}
$domain = $lang_by_code[$lang_code]['domain'];
$meta_key = 'alt_' . $lang_code . '_url';

function muh_default_url($post_id, $domain) {
    $permalink = get_permalink($post_id);
    if (!$permalink) return '';
    $parsed = wp_parse_url($permalink);
    $path = $parsed['path'] ?? '';
    if ($path === '' || $path === '/' || !empty($parsed['query'])) return '';
    return untrailingslashit($domain) . $path;
}

$post_ids = get_posts([
    'post_type'      => $post_types,
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'fields'         => 'ids',
]);

$stats = ['examined' => 0, 'skip_already_set' => 0, 'reachable' => 0, 'marked_dash' => 0, 'no_default' => 0];

foreach ($post_ids as $post_id) {
    $stats['examined']++;

    $existing = trim((string) get_post_meta($post_id, $meta_key, true));
    if ($existing !== '') {
        $stats['skip_already_set']++;
        continue;
    }

    $default_url = muh_default_url($post_id, $domain);
    if ($default_url === '') {
        $stats['no_default']++;
        continue;
    }

    $response = wp_remote_get($default_url, ['timeout' => 10, 'redirection' => 3]);
    $code = is_wp_error($response) ? 0 : (int) wp_remote_retrieve_response_code($response);

    if ($code === 200) {
        $stats['reachable']++;
    } else {
        WP_CLI::log("  [$mode] #{$post_id} 對應頁不存在（{$default_url} -> HTTP {$code}），標記 \"-\"");
        $stats['marked_dash']++;
        if ($apply) {
            update_post_meta($post_id, $meta_key, '-');
        }
    }
}

WP_CLI::log(sprintf(
    '[%s] 檢視 %d，已有值略過 %d，permalink 無法組網址略過 %d，對應頁存在略過 %d，標記無對應 "-" %d',
    $mode,
    $stats['examined'],
    $stats['skip_already_set'],
    $stats['no_default'],
    $stats['reachable'],
    $stats['marked_dash']
));
WP_CLI::success($apply ? '已完成標記。' : '確認無誤後加 "apply" 執行。');
