<?php
/**
 * Admin Notices - ?內?芸‵撖怨?閮 URL ?摰?
 * 
 * @package Hreflang_Manager
 */

// 憒??湔閮芸?甇斗?獢????
if (!defined('ABSPATH')) {
    exit;
}

/**
 * ?????唳?蝷?
 */
function hreflang_admin_notice_init() {
    add_action('admin_notices', 'hreflang_display_missing_url_notice');
}
add_action('admin_init', 'hreflang_admin_notice_init');

/**
 * 憿舐內蝻箏?隤? URL ??蝷?
 */
function hreflang_display_missing_url_notice() {
    $screen = get_current_screen();
    
    // ?芸??蝺刻摩?憿舐內
    if (!$screen || !in_array($screen->id, ['post', 'page', 'edit-category', 'edit-post_tag'])) {
        return;
    }
    
    // 憒??舀?蝡楊頛舫???
    if (in_array($screen->id, ['post', 'page'])) {
        global $post;
        if (!$post) return;
        
        $missing = hreflang_get_missing_language_urls($post->ID, 'post');
        
        if (!empty($missing)) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>??Hreflang ?內嚗?/strong>甇文摰寞憛怠神隞乩?隤?????URL嚗?/p>';
            echo '<ul style="margin-left: 20px;">';
            
            foreach ($missing as $lang) {
                printf(
                    '<li><strong>%s</strong> (%s)</li>',
                    esc_html($lang['label']),
                    esc_html($lang['meta_key'])
                );
            }
            
            echo '</ul>';
            echo '<p>隢?湧?甈??reflang 憭?閮 URL??憛葉憛怠神撠? URL嚗?府隤???hreflang 璅惜撠??撓?箝?/p>';
            echo '</div>';
        }
    }
    
    // 憒??臬?憿?璅惜蝺刻摩?
    if (isset($_GET['tag_ID'])) {
        $term_id = intval($_GET['tag_ID']);
        $missing = hreflang_get_missing_language_urls($term_id, 'term');
        
        if (!empty($missing)) {
            echo '<div class="notice notice-warning">';
            echo '<p><strong>??Hreflang ?內嚗?/strong>甇文?憿?璅惜?芸‵撖思誑銝?閮????URL嚗?/p>';
            echo '<ul style="margin-left: 20px;">';
            
            foreach ($missing as $lang) {
                printf(
                    '<li><strong>%s</strong> (%s)</li>',
                    esc_html($lang['label']),
                    esc_html($lang['meta_key'])
                );
            }
            
            echo '</ul>';
            echo '<p>隢銝?reflang 憭?閮 URL??雿葉憛怠神撠? URL??/p>';
            echo '</div>';
        }
    }
}

/**
 * ?冽?蝡?銵券??ａ＊蝷箇撩撠?閮 URL ?郎??蝷?
 */
function hreflang_add_missing_url_column($columns) {
    $columns['hreflang_status'] = 'Hreflang';
    return $columns;
}
add_filter('manage_posts_columns', 'hreflang_add_missing_url_column');
add_filter('manage_pages_columns', 'hreflang_add_missing_url_column');

/**
 * 憿舐內 Hreflang ???雿摰?
 */
function hreflang_display_missing_url_column($column, $post_id) {
    if ($column === 'hreflang_status') {
        $missing = hreflang_get_missing_language_urls($post_id, 'post');
        
        if (empty($missing)) {
            echo '<span style="color: green;" title="???閮 URL ?賢歇憛怠神">??/span>';
        } else {
            $missing_labels = array_column($missing, 'label');
            printf(
                '<span style="color: orange;" title="蝻箏?嚗?s">??%d</span>',
                esc_attr(implode(', ', $missing_labels)),
                count($missing)
            );
        }
    }
}
add_action('manage_posts_custom_column', 'hreflang_display_missing_url_column', 10, 2);
add_action('manage_pages_custom_column', 'hreflang_display_missing_url_column', 10, 2);

/**
 * ?典?憿?璅惜?”?? Hreflang ???雿?
 */
function hreflang_add_term_missing_url_column($columns) {
    $columns['hreflang_status'] = 'Hreflang';
    return $columns;
}
add_filter('manage_edit-category_columns', 'hreflang_add_term_missing_url_column');
add_filter('manage_edit-post_tag_columns', 'hreflang_add_term_missing_url_column');

/**
 * 憿舐內??/璅惜??Hreflang ???
 */
function hreflang_display_term_missing_url_column($content, $column, $term_id) {
    if ($column === 'hreflang_status') {
        $missing = hreflang_get_missing_language_urls($term_id, 'term');
        
        if (empty($missing)) {
            return '<span style="color: green;" title="???閮 URL ?賢歇憛怠神">??/span>';
        } else {
            $missing_labels = array_column($missing, 'label');
            return sprintf(
                '<span style="color: orange;" title="蝻箏?嚗?s">??%d</span>',
                esc_attr(implode(', ', $missing_labels)),
                count($missing)
            );
        }
    }
    return $content;
}
add_filter('manage_category_custom_column', 'hreflang_display_term_missing_url_column', 10, 3);
add_filter('manage_post_tag_custom_column', 'hreflang_display_term_missing_url_column', 10, 3);
