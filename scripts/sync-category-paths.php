<?php
/**
 * sync-category-paths.php — 依權威對應表同步文章的分類路徑
 *
 * 用法（wp eval-file）：
 *   wp eval-file scripts/sync-category-paths.php <map.csv>          # dry-run：只報告不寫入
 *   wp eval-file scripts/sync-category-paths.php <map.csv> apply    # 實際套用
 *   （注意：用 "apply" 不是 "--apply"，雙破折號參數會被 wp-cli 攔截）
 *
 * map.csv 格式：每行「post_slug,category/path」（path 為 "." 表示無分類，跳過）。
 * 可用 bin/export-category-map.sh 從權威站的 post sitemap 產生。
 *
 * 行為：
 * - 依 slug 找本站文章；找不到＝跳過（該站沒有這篇）。
 * - 逐層確保目標分類存在：不存在就建立（slug＝路徑段、顯示名稱＝slug，之後可在後台改名）；
 *   存在但父分類不同＝重新掛載（re-parent，會連動該分類下所有文章的 URL）。
 * - 把目標葉分類附加到文章（不移除既有分類），並設 Yoast primary category 控制 permalink。
 * - 已一致的文章跳過。
 */

if (empty($args[0]) || !file_exists($args[0])) {
    WP_CLI::error('用法: wp eval-file scripts/sync-category-paths.php <map.csv> [apply]');
}
$apply = in_array('apply', $args, true);
$mode  = $apply ? 'APPLY' : 'DRY-RUN';
WP_CLI::log("=== sync-category-paths [$mode] ===");

/** 取得文章目前的 permalink 分類路徑（考慮 Yoast primary） */
function hcs_current_path($post_id) {
    $link = get_permalink($post_id);
    $path = trim((string) wp_parse_url($link, PHP_URL_PATH), '/');
    $segments = explode('/', $path);
    array_pop($segments); // 移除 postname
    return $segments ? implode('/', $segments) : '.';
}

/** 逐層確保分類鏈存在，回傳葉分類 term_id；$log 收集動作描述 */
function hcs_ensure_chain($path, $apply, array &$log) {
    $parent = 0;
    $term_id = 0;
    foreach (explode('/', $path) as $slug) {
        $term = get_term_by('slug', $slug, 'category');
        if ($term) {
            if ((int) $term->parent !== $parent) {
                $log[] = "re-parent 分類 {$slug}（parent {$term->parent} → {$parent}）";
                if ($apply) {
                    wp_update_term($term->term_id, 'category', ['parent' => $parent]);
                }
            }
            $term_id = (int) $term->term_id;
        } else {
            $log[] = "建立分類 {$slug}（parent {$parent}）";
            if ($apply) {
                $created = wp_insert_term($slug, 'category', ['slug' => $slug, 'parent' => $parent]);
                if (is_wp_error($created)) {
                    WP_CLI::warning("建立分類 {$slug} 失敗: " . $created->get_error_message());
                    return 0;
                }
                $term_id = (int) $created['term_id'];
            } else {
                $term_id = -1; // dry-run 佔位
            }
        }
        $parent = $term_id > 0 ? $term_id : 0;
    }
    return $term_id;
}

$rows = array_filter(array_map('trim', file($args[0])));
$stats = ['moved' => 0, 'ok' => 0, 'missing' => 0, 'skipped' => 0];

foreach ($rows as $row) {
    $parts = explode(',', $row, 2);
    if (count($parts) !== 2) continue;
    list($slug, $target) = $parts;
    if ($target === '.' || $target === '') { $stats['skipped']++; continue; }

    $posts = get_posts(['name' => $slug, 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1]);
    if (!$posts) { $stats['missing']++; continue; }
    $post_id = $posts[0]->ID;

    if (hcs_current_path($post_id) === $target) { $stats['ok']++; continue; }

    $actions = [];
    $leaf = hcs_ensure_chain($target, $apply, $actions);
    $actions[] = "文章 {$slug}: → {$target}（附加分類＋設 Yoast primary）";
    if ($apply && $leaf > 0) {
        wp_set_post_categories($post_id, [$leaf], true); // append
        update_post_meta($post_id, '_yoast_wpseo_primary_category', $leaf);
        clean_post_cache($post_id);
    }
    foreach ($actions as $a) WP_CLI::log("  [$mode] $a");
    $stats['moved']++;
}

WP_CLI::success(sprintf(
    '%s 完成：搬移 %d、已一致 %d、本站無此文 %d、無分類跳過 %d',
    $mode, $stats['moved'], $stats['ok'], $stats['missing'], $stats['skipped']
));
if (!$apply) {
    WP_CLI::log('確認無誤後在結尾加 "apply" 執行；執行後建議 purge 快取並跑 bin/check-hreflang.sh --sitemap 驗收。');
}
