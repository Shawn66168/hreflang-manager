<?php
/**
 * apply-hreflang-plan.php — 套用 bin/plan-hreflang-cluster.py 產生的 hreflang meta 計畫。
 *
 * 用法（wp eval-file，在計畫對應的站台執行）：
 *   wp eval-file scripts/apply-hreflang-plan.php <plan.json>          # dry-run：只計數不寫入
 *   wp eval-file scripts/apply-hreflang-plan.php <plan.json> apply    # 實際寫入
 *
 * 計畫每列：{"id": int, "key": "alt_<code>_url", "want": "-" | "https://..."}
 * apply 時「先」把每筆原值寫成回滾檔 /tmp/hreflang-rollback-<host>-<時間>.json
 * （old=null 表示原本沒有這個 key），寫檔失敗就整批不動；還原用 scripts/rollback-hreflang-plan.php。
 * 回滾檔放 /tmp 是因為 wp-cli 以 web 使用者執行，計畫檔所在目錄不一定可寫；/tmp 重開機會清，要留存請自行複製。
 */
$args  = isset($args) ? $args : [];
$plan  = $args[0] ?? '';
$apply = ($args[1] ?? '') === 'apply';
if (!$plan || !is_readable($plan)) {
    WP_CLI::error("plan file not readable: $plan");
}
$rows = json_decode(file_get_contents($plan), true);
if (!is_array($rows)) {
    WP_CLI::error('plan is not a JSON array');
}

$allowed = [];
foreach ((array) get_option('hreflang_languages', []) as $lang) {
    if (!empty($lang['code'])) {
        $allowed[] = 'alt_' . $lang['code'] . '_url';
    }
}
$backup  = [];
$done    = 0;
$skipped = 0;

foreach ($rows as $r) {
    $id   = (int) ($r['id'] ?? 0);
    $key  = (string) ($r['key'] ?? '');
    $want = (string) ($r['want'] ?? '');
    if (!$id || !in_array($key, $allowed, true) || ($want !== '-' && !filter_var($want, FILTER_VALIDATE_URL))) {
        WP_CLI::warning("invalid row: " . json_encode($r));
        $skipped++;
        continue;
    }
    if (get_post_status($id) !== 'publish') {
        WP_CLI::warning("post $id not published, skipped");
        $skipped++;
        continue;
    }
    $exists = metadata_exists('post', $id, $key);
    $old    = $exists ? get_post_meta($id, $key, true) : null;
    if ($old === $want) {
        continue;
    }
    $backup[] = ['id' => $id, 'key' => $key, 'old' => $old, 'want' => $want];
}

// Save the rollback file before touching anything.
if ($apply && $backup) {
    // /tmp itself is writable by the web user; the plan directory may not be.
    $file = '/tmp/hreflang-rollback-' . parse_url(home_url(), PHP_URL_HOST) . '-' . gmdate('Ymd-His') . '.json';
    if (file_put_contents($file, wp_json_encode($backup)) === false) {
        WP_CLI::error("cannot write rollback file $file");
    }
    WP_CLI::log("rollback file: $file");
}

foreach ($backup as $b) {
    if ($apply) {
        update_post_meta($b['id'], $b['key'], $b['want']);
        if (get_post_meta($b['id'], $b['key'], true) !== $b['want']) {
            WP_CLI::warning("read-back mismatch on {$b['id']} {$b['key']}");
            continue;
        }
    }
    $done++;
}

WP_CLI::success(sprintf('%s: %d changes, %d skipped, %d rows in plan', $apply ? 'APPLIED' : 'DRY RUN', $done, $skipped, count($rows)));
