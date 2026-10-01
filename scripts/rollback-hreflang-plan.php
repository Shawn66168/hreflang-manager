<?php
/**
 * rollback-hreflang-plan.php — 依 apply-hreflang-plan.php 寫出的回滾檔還原 hreflang meta。
 *
 * 用法：wp eval-file scripts/rollback-hreflang-plan.php /tmp/hreflang-rollback-<host>-<時間>.json
 * old=null 的項目代表套用前沒有這個 key，還原時直接刪除。
 */
$file = $args[0] ?? '';
$rows = json_decode((string) @file_get_contents($file), true);
if (!is_array($rows)) {
    WP_CLI::error("cannot read rollback file: $file");
}
foreach ($rows as $r) {
    if ($r['old'] === null) {
        delete_post_meta((int) $r['id'], $r['key']);
    } else {
        update_post_meta((int) $r['id'], $r['key'], $r['old']);
    }
}
WP_CLI::success(count($rows) . ' rows restored');
