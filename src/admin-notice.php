<?php
/**
 * Admin Notices - 提示未填寫語言 URL 的內容
 */
defined('ABSPATH') || exit;

/**
 * 初始化後台提示
 */
function hreflang_admin_notice_init() {
    add_action('admin_notices', 'hreflang_display_missing_url_notice');
}
add_action('admin_init', 'hreflang_admin_notice_init');

/**
 * 顯示缺少語言 URL 的提示
 */
function hreflang_display_missing_url_notice() {
    $screen = get_current_screen();
    
    // 只在文章編輯頁面顯示
    if (!$screen || !in_array($screen->id, ['post', 'page', 'edit-category', 'edit-post_tag'])) {
        return;
    }
    
    // 如果是文章編輯頁面
    if (in_array($screen->id, ['post', 'page'])) {
        global $post;
        if (!$post) return;
        
        $missing = hreflang_get_missing_language_urls($post->ID, 'post');
        
        if (!empty($missing)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>⚠ Hreflang 提示：</strong>此內容未填寫以下語言的對應 URL：</p>';
            echo '<ul style="margin-left: 20px;">';
            
            foreach ($missing as $lang) {
                printf(
                    '<li><strong>%s</strong> (%s)</li>',
                    esc_html($lang['label']),
                    esc_html($lang['meta_key'])
                );
            }
            
            echo '</ul>';
            echo '<p>請在側邊欄的「Hreflang 多語言 URL」區塊中填寫對應 URL，否則該語言的 hreflang 標籤將不會輸出。</p>';
            echo '</div>';
        }
    }
    
    // 如果是分類/標籤編輯頁面
    if (isset($_GET['tag_ID'])) {
        $term_id = intval($_GET['tag_ID']);
        $missing = hreflang_get_missing_language_urls($term_id, 'term');
        
        if (!empty($missing)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>⚠ Hreflang 提示：</strong>此分類/標籤未填寫以下語言的對應 URL：</p>';
            echo '<ul style="margin-left: 20px;">';
            
            foreach ($missing as $lang) {
                printf(
                    '<li><strong>%s</strong> (%s)</li>',
                    esc_html($lang['label']),
                    esc_html($lang['meta_key'])
                );
            }
            
            echo '</ul>';
            echo '<p>請在下方的「Hreflang 多語言 URL」欄位中填寫對應 URL。</p>';
            echo '</div>';
        }
    }
}

/**
 * 在文章列表頁面顯示缺少語言 URL 的警告圖示
 */
function hreflang_add_missing_url_column($columns) {
    $columns['hreflang_status'] = 'Hreflang';
    return $columns;
}
add_filter('manage_posts_columns', 'hreflang_add_missing_url_column');
add_filter('manage_pages_columns', 'hreflang_add_missing_url_column');

/**
 * 顯示 Hreflang 狀態欄位內容
 */
function hreflang_display_missing_url_column($column, $post_id) {
    if ($column === 'hreflang_status') {
        $missing = hreflang_get_missing_language_urls($post_id, 'post');
        
        if (empty($missing)) {
            echo '<span style="color: green;" title="所有語言 URL 都已填寫">✓</span>';
        } else {
            $missing_labels = array_column($missing, 'label');
            printf(
                '<span style="color: orange;" title="缺少：%s">⚠ %d</span>',
                esc_attr(implode(', ', $missing_labels)),
                count($missing)
            );
        }
    }
}
add_action('manage_posts_custom_column', 'hreflang_display_missing_url_column', 10, 2);
add_action('manage_pages_custom_column', 'hreflang_display_missing_url_column', 10, 2);

/**
 * 在分類/標籤列表頁面加入 Hreflang 狀態欄位
 */
function hreflang_add_term_missing_url_column($columns) {
    $columns['hreflang_status'] = 'Hreflang';
    return $columns;
}
add_filter('manage_edit-category_columns', 'hreflang_add_term_missing_url_column');
add_filter('manage_edit-post_tag_columns', 'hreflang_add_term_missing_url_column');

/**
 * 顯示分類/標籤的 Hreflang 狀態
 */
function hreflang_display_term_missing_url_column($content, $column, $term_id) {
    if ($column === 'hreflang_status') {
        $missing = hreflang_get_missing_language_urls($term_id, 'term');
        
        if (empty($missing)) {
            return '<span style="color: green;" title="所有語言 URL 都已填寫">✓</span>';
        } else {
            $missing_labels = array_column($missing, 'label');
            return sprintf(
                '<span style="color: orange;" title="缺少：%s">⚠ %d</span>',
                esc_attr(implode(', ', $missing_labels)),
                count($missing)
            );
        }
    }
    return $content;
}
add_filter('manage_category_custom_column', 'hreflang_display_term_missing_url_column', 10, 3);
add_filter('manage_post_tag_custom_column', 'hreflang_display_term_missing_url_column', 10, 3);
