<?php
/**
 * 무지예뽀 쇼핑몰 플러그인 삭제 시 실행
 *
 * @package MuziyeppoShop
 * @since 1.0.0
 */

// 워드프레스를 통한 삭제가 아닌 경우 종료
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 플러그인 옵션 삭제
delete_option('muziyeppo_currency');
delete_option('muziyeppo_products_per_page');

// 커스텀 포스트 타입의 모든 게시물 삭제
$muziyeppo_products = get_posts(array(
    'post_type' => 'muziyeppo_product',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($muziyeppo_products as $product) {
    // 첨부 이미지도 함께 삭제
    $attachments = get_attached_media('', $product->ID);
    foreach ($attachments as $attachment) {
        wp_delete_attachment($attachment->ID, true);
    }
    
    // 게시물 삭제
    wp_delete_post($product->ID, true);
}

// 커스텀 분류 용어 삭제
$terms = get_terms(array(
    'taxonomy' => 'product_category',
    'hide_empty' => false,
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'product_category');
}

// 데이터베이스 테이블 삭제
global $wpdb;

$tables = array(
    $wpdb->prefix . 'muziyeppo_orders',
    $wpdb->prefix . 'muziyeppo_cart',
    $wpdb->prefix . 'muziyeppo_points',
    $wpdb->prefix . 'muziyeppo_coupons',
    $wpdb->prefix . 'muziyeppo_user_coupons',
    $wpdb->prefix . 'muziyeppo_likes'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// 사용자 메타 데이터 삭제
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'muziyeppo_%'");

// 트랜지언트 삭제
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_muziyeppo_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_muziyeppo_%'");

// 업로드 디렉토리의 플러그인 폴더 삭제
$upload_dir = wp_upload_dir();
$plugin_upload_dir = $upload_dir['basedir'] . '/muziyeppo-shop';
if (is_dir($plugin_upload_dir)) {
    muziyeppo_delete_directory($plugin_upload_dir);
}

// 디렉토리 재귀적 삭제 함수
function muziyeppo_delete_directory($dir) {
    if (!file_exists($dir)) {
        return true;
    }

    if (!is_dir($dir)) {
        return unlink($dir);
    }

    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }

        if (!muziyeppo_delete_directory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }

    return rmdir($dir);
}

// 퍼머링크 재설정
flush_rewrite_rules();