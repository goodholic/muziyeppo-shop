<?php
/**
 * Plugin Name: 무지예뽀 쇼핑몰
 * Plugin URI: https://example.com/muziyeppo-shop
 * Description: 무지예뽀 쇼핑몰 상품 관리 플러그인
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.2
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: muziyeppo-shop
 * Domain Path: /languages
 */

// 보안을 위해 직접 접근 방지
if (!defined('ABSPATH')) {
    exit;
}

// 플러그인 활성화 시 실행
register_activation_hook(__FILE__, 'muziyeppo_activate');
function muziyeppo_activate() {
    // upgrade.php 파일 한 번만 include
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // 상품 포스트 타입 등록
    muziyeppo_register_product_post_type();
    
    // 데이터베이스 테이블 생성
    muziyeppo_create_tables();
    
    // 추가 테이블 생성
    muziyeppo_create_points_table();
    muziyeppo_create_coupons_table();
    muziyeppo_create_user_coupons_table();
    
    // 퍼머링크 재설정
    flush_rewrite_rules();
}

// 플러그인 비활성화 시 실행
register_deactivation_hook(__FILE__, 'muziyeppo_deactivate');
function muziyeppo_deactivate() {
    flush_rewrite_rules();
}

// 커스텀 포스트 타입 등록
add_action('init', 'muziyeppo_register_product_post_type');
function muziyeppo_register_product_post_type() {
    $labels = array(
        'name'                  => '상품',
        'singular_name'         => '상품',
        'menu_name'             => '무지예뽀 상품',
        'add_new'               => '새 상품 추가',
        'add_new_item'          => '새 상품 추가',
        'edit_item'             => '상품 편집',
        'new_item'              => '새 상품',
        'view_item'             => '상품 보기',
        'view_items'            => '상품 목록',
        'search_items'          => '상품 검색',
        'not_found'             => '상품을 찾을 수 없습니다',
        'not_found_in_trash'    => '휴지통에서 상품을 찾을 수 없습니다',
        'all_items'             => '모든 상품',
        'archives'              => '상품 아카이브',
        'attributes'            => '상품 속성',
        'insert_into_item'      => '상품에 삽입',
        'uploaded_to_this_item' => '이 상품에 업로드됨',
        'featured_image'        => '대표 이미지',
        'set_featured_image'    => '대표 이미지 설정',
        'remove_featured_image' => '대표 이미지 제거',
        'use_featured_image'    => '대표 이미지로 사용',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'product'),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => 20,
        'menu_icon'          => 'dashicons-cart',
        'supports'           => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
        'show_in_rest'       => true,
    );

    register_post_type('muziyeppo_product', $args);
}

// 카테고리 택소노미 등록
add_action('init', 'muziyeppo_register_taxonomies');
function muziyeppo_register_taxonomies() {
    // 상품 카테고리
    $labels = array(
        'name'              => '상품 카테고리',
        'singular_name'     => '카테고리',
        'search_items'      => '카테고리 검색',
        'all_items'         => '모든 카테고리',
        'parent_item'       => '상위 카테고리',
        'parent_item_colon' => '상위 카테고리:',
        'edit_item'         => '카테고리 편집',
        'update_item'       => '카테고리 업데이트',
        'add_new_item'      => '새 카테고리 추가',
        'new_item_name'     => '새 카테고리 이름',
        'menu_name'         => '카테고리',
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'product-category'),
        'show_in_rest'      => true,
    );

    register_taxonomy('product_category', array('muziyeppo_product'), $args);
}

// 위젯 등록
add_action('widgets_init', 'muziyeppo_register_widgets');
function muziyeppo_register_widgets() {
    register_widget('Muziyeppo_Products_Widget');
}

class Muziyeppo_Products_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'muziyeppo_products',
            '무지예뽀 상품 목록',
            array('description' => '상품 목록을 표시합니다')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo do_shortcode('[muziyeppo_products limit="4"]');
        echo $args['after_widget'];
    }
}

// 메타박스 추가
add_action('add_meta_boxes', 'muziyeppo_add_product_meta_boxes');
function muziyeppo_add_product_meta_boxes() {
    add_meta_box(
        'muziyeppo_product_details',
        '상품 정보',
        'muziyeppo_product_details_callback',
        'muziyeppo_product',
        'normal',
        'high'
    );
}

// 메타박스 콜백 함수
function muziyeppo_product_details_callback($post) {
    wp_nonce_field('muziyeppo_save_product_details', 'muziyeppo_product_nonce');
    
    // 기존 값 가져오기
    $brand = get_post_meta($post->ID, '_muziyeppo_brand', true);
    $price = get_post_meta($post->ID, '_muziyeppo_price', true);
    $original_price = get_post_meta($post->ID, '_muziyeppo_original_price', true);
    $discount = get_post_meta($post->ID, '_muziyeppo_discount', true);
    $rating = get_post_meta($post->ID, '_muziyeppo_rating', true);
    $reviews = get_post_meta($post->ID, '_muziyeppo_reviews', true);
    $likes = get_post_meta($post->ID, '_muziyeppo_likes', true);
    $is_new = get_post_meta($post->ID, '_muziyeppo_is_new', true);
    ?>
    
    <style>
        .muziyeppo-field-group {
            margin-bottom: 20px;
        }
        .muziyeppo-field-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .muziyeppo-field-group input[type="text"],
        .muziyeppo-field-group input[type="number"] {
            width: 100%;
            max-width: 400px;
        }
        .muziyeppo-field-group .description {
            color: #666;
            font-style: italic;
            margin-top: 5px;
        }
    </style>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_brand">브랜드</label>
        <input type="text" id="muziyeppo_brand" name="muziyeppo_brand" value="<?php echo esc_attr($brand); ?>" />
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_price">판매가격</label>
        <input type="number" id="muziyeppo_price" name="muziyeppo_price" value="<?php echo esc_attr($price); ?>" />
        <p class="description">원 단위로 입력하세요 (예: 16800)</p>
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_original_price">정가</label>
        <input type="number" id="muziyeppo_original_price" name="muziyeppo_original_price" value="<?php echo esc_attr($original_price); ?>" />
        <p class="description">할인 전 원래 가격</p>
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_discount">할인율 (%)</label>
        <input type="number" id="muziyeppo_discount" name="muziyeppo_discount" value="<?php echo esc_attr($discount); ?>" min="0" max="100" />
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_rating">평점</label>
        <input type="number" id="muziyeppo_rating" name="muziyeppo_rating" value="<?php echo esc_attr($rating); ?>" step="0.1" min="0" max="5" />
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_reviews">리뷰 수</label>
        <input type="number" id="muziyeppo_reviews" name="muziyeppo_reviews" value="<?php echo esc_attr($reviews); ?>" min="0" />
    </div>
    
    <div class="muziyeppo-field-group">
        <label for="muziyeppo_likes">좋아요 수</label>
        <input type="number" id="muziyeppo_likes" name="muziyeppo_likes" value="<?php echo esc_attr($likes); ?>" min="0" />
    </div>
    
    <div class="muziyeppo-field-group">
        <label>
            <input type="checkbox" id="muziyeppo_is_new" name="muziyeppo_is_new" value="1" <?php checked($is_new, '1'); ?> />
            신상품으로 표시
        </label>
    </div>
    <?php
}

// 메타박스 데이터 저장
add_action('save_post_muziyeppo_product', 'muziyeppo_save_product_details');
function muziyeppo_save_product_details($post_id) {
    // 보안 검증
    if (!isset($_POST['muziyeppo_product_nonce']) || !wp_verify_nonce($_POST['muziyeppo_product_nonce'], 'muziyeppo_save_product_details')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // 데이터 저장
    $fields = array(
        'muziyeppo_brand' => '_muziyeppo_brand',
        'muziyeppo_price' => '_muziyeppo_price',
        'muziyeppo_original_price' => '_muziyeppo_original_price',
        'muziyeppo_discount' => '_muziyeppo_discount',
        'muziyeppo_rating' => '_muziyeppo_rating',
        'muziyeppo_reviews' => '_muziyeppo_reviews',
        'muziyeppo_likes' => '_muziyeppo_likes',
    );
    
    foreach ($fields as $field => $meta_key) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
        }
    }
    
    // 체크박스 처리
    $is_new = isset($_POST['muziyeppo_is_new']) ? '1' : '0';
    update_post_meta($post_id, '_muziyeppo_is_new', $is_new);
}

// 관리자 상품 목록에 커스텀 컬럼 추가
add_filter('manage_muziyeppo_product_posts_columns', 'muziyeppo_add_product_columns');
function muziyeppo_add_product_columns($columns) {
    $new_columns = array();
    $new_columns['cb'] = $columns['cb'];
    $new_columns['thumbnail'] = '이미지';
    $new_columns['title'] = $columns['title'];
    $new_columns['brand'] = '브랜드';
    $new_columns['price'] = '가격';
    $new_columns['discount'] = '할인율';
    $new_columns['categories'] = '카테고리';
    $new_columns['date'] = $columns['date'];
    
    return $new_columns;
}

// 커스텀 컬럼 데이터 표시
add_action('manage_muziyeppo_product_posts_custom_column', 'muziyeppo_product_column_content', 10, 2);
function muziyeppo_product_column_content($column, $post_id) {
    switch ($column) {
        case 'thumbnail':
            if (has_post_thumbnail($post_id)) {
                echo get_the_post_thumbnail($post_id, array(50, 50));
            } else {
                echo '—';
            }
            break;
            
        case 'brand':
            $brand = get_post_meta($post_id, '_muziyeppo_brand', true);
            echo $brand ? esc_html($brand) : '—';
            break;
            
        case 'price':
            $price = get_post_meta($post_id, '_muziyeppo_price', true);
            $original_price = get_post_meta($post_id, '_muziyeppo_original_price', true);
            if ($price) {
                echo number_format($price) . '원';
                if ($original_price && $original_price > $price) {
                    echo '<br><del style="color:#999;">' . number_format($original_price) . '원</del>';
                }
            } else {
                echo '—';
            }
            break;
            
        case 'discount':
            $discount = get_post_meta($post_id, '_muziyeppo_discount', true);
            if ($discount) {
                echo '<span style="color:#ff0000; font-weight:bold;">' . $discount . '%</span>';
            } else {
                echo '—';
            }
            break;
            
        case 'categories':
            $terms = get_the_terms($post_id, 'product_category');
            if ($terms && !is_wp_error($terms)) {
                $term_names = wp_list_pluck($terms, 'name');
                echo implode(', ', $term_names);
            } else {
                echo '—';
            }
            break;
    }
}

// 상품 아카이브 페이지 템플릿
add_filter('template_include', 'muziyeppo_product_template');
function muziyeppo_product_template($template) {
    if (is_post_type_archive('muziyeppo_product') || is_tax('product_category')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/archive-product.php';
        if (file_exists($new_template)) {
            return $new_template;
        } else {
            error_log('무지예뽀 쇼핑몰: archive-product.php 템플릿 파일을 찾을 수 없습니다.');
        }
    }
    
    if (is_singular('muziyeppo_product')) {
        $new_template = plugin_dir_path(__FILE__) . 'templates/single-product.php';
        if (file_exists($new_template)) {
            return $new_template;
        } else {
            error_log('무지예뽀 쇼핑몰: single-product.php 템플릿 파일을 찾을 수 없습니다.');
        }
    }
    
    return $template;
}

// 쇼트코드 등록 - 상품 목록 표시
add_shortcode('muziyeppo_products', 'muziyeppo_products_shortcode');
function muziyeppo_products_shortcode($atts) {
    $atts = shortcode_atts(array(
        'category' => '',
        'limit' => 12,
        'orderby' => 'date',
        'order' => 'DESC',
    ), $atts);
    
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => $atts['limit'],
        'orderby' => $atts['orderby'],
        'order' => $atts['order'],
    );
    
    if (!empty($atts['category'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_category',
                'field' => 'slug',
                'terms' => $atts['category'],
            ),
        );
    }
    
    $products = new WP_Query($args);
    
    ob_start();
    ?>
    <style>
        .muziyeppo-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .muziyeppo-product-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
            transition: all 0.3s;
            cursor: pointer;
            border: 1px solid #f0f0f0;
        }
        
        .muziyeppo-product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        .muziyeppo-product-image {
            position: relative;
            aspect-ratio: 1;
            overflow: hidden;
            background: #f8f8f8;
        }
        
        .muziyeppo-product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .muziyeppo-product-card:hover .muziyeppo-product-image img {
            transform: scale(1.05);
        }
        
        .muziyeppo-product-badges {
            position: absolute;
            top: 8px;
            left: 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        
        .muziyeppo-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .muziyeppo-badge-sale {
            background: #000;
            color: #fff;
        }
        
        .muziyeppo-badge-new {
            background: #4CAF50;
            color: #fff;
        }
        
        .muziyeppo-product-info {
            padding: 12px;
        }
        
        .muziyeppo-product-brand {
            font-size: 12px;
            color: #666;
            margin-bottom: 4px;
        }
        
        .muziyeppo-product-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .muziyeppo-product-price {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 8px;
        }
        
        .muziyeppo-discount-rate {
            color: #000;
            font-weight: 700;
            font-size: 16px;
        }
        
        .muziyeppo-price {
            font-size: 16px;
            font-weight: 700;
        }
        
        .muziyeppo-original-price {
            font-size: 13px;
            color: #999;
            text-decoration: line-through;
        }
        
        .muziyeppo-product-stats {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
            color: #666;
        }
    </style>
    
    <div class="muziyeppo-products-grid">
        <?php while ($products->have_posts()) : $products->the_post(); 
            $brand = get_post_meta(get_the_ID(), '_muziyeppo_brand', true);
            $price = get_post_meta(get_the_ID(), '_muziyeppo_price', true);
            $original_price = get_post_meta(get_the_ID(), '_muziyeppo_original_price', true);
            $discount = get_post_meta(get_the_ID(), '_muziyeppo_discount', true);
            $rating = get_post_meta(get_the_ID(), '_muziyeppo_rating', true);
            $reviews = get_post_meta(get_the_ID(), '_muziyeppo_reviews', true);
            $likes = get_post_meta(get_the_ID(), '_muziyeppo_likes', true);
            $is_new = get_post_meta(get_the_ID(), '_muziyeppo_is_new', true);
        ?>
            <div class="muziyeppo-product-card" onclick="location.href='<?php the_permalink(); ?>'">
                <div class="muziyeppo-product-image">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('medium'); ?>
                    <?php else : ?>
                        <img src="https://via.placeholder.com/300x300/f8f8f8/333333?text=No+Image" alt="<?php the_title(); ?>">
                    <?php endif; ?>
                    
                    <div class="muziyeppo-product-badges">
                        <?php if ($discount) : ?>
                            <span class="muziyeppo-badge muziyeppo-badge-sale"><?php echo esc_html($discount); ?>% OFF</span>
                        <?php endif; ?>
                        <?php if ($is_new) : ?>
                            <span class="muziyeppo-badge muziyeppo-badge-new">NEW</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="muziyeppo-product-info">
                    <?php if ($brand) : ?>
                        <p class="muziyeppo-product-brand"><?php echo esc_html($brand); ?></p>
                    <?php endif; ?>
                    
                    <h4 class="muziyeppo-product-name"><?php the_title(); ?></h4>
                    
                    <div class="muziyeppo-product-price">
                        <?php if ($discount) : ?>
                            <span class="muziyeppo-discount-rate"><?php echo esc_html($discount); ?>%</span>
                        <?php endif; ?>
                        <?php if ($price) : ?>
                            <span class="muziyeppo-price"><?php echo number_format($price); ?>원</span>
                        <?php endif; ?>
                        <?php if ($original_price && $original_price > $price) : ?>
                            <span class="muziyeppo-original-price"><?php echo number_format($original_price); ?>원</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="muziyeppo-product-stats">
                        <?php if ($rating) : ?>
                            <span>★ <?php echo esc_html($rating); ?></span>
                        <?php endif; ?>
                        <?php if ($reviews) : ?>
                            <span>리뷰 <?php echo esc_html(number_format($reviews)); ?></span>
                        <?php endif; ?>
                        <?php if ($likes) : ?>
                            <span>♥ <?php echo esc_html($likes > 1000 ? number_format($likes/1000, 1) . 'k' : number_format($likes)); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <?php
    wp_reset_postdata();
    
    return ob_get_clean();
}

// AJAX 핸들러 - 좋아요 기능
add_action('wp_ajax_muziyeppo_toggle_like', 'muziyeppo_toggle_like');
add_action('wp_ajax_nopriv_muziyeppo_toggle_like', 'muziyeppo_toggle_like');
function muziyeppo_toggle_like() {
    $product_id = intval($_POST['product_id']);
    $likes = get_post_meta($product_id, '_muziyeppo_likes', true);
    $likes = $likes ? intval($likes) : 0;
    
    // 사용자별 좋아요 상태 관리 (간단한 예시)
    $user_likes = get_option('muziyeppo_user_likes', array());
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    
    if (!isset($user_likes[$user_id])) {
        $user_likes[$user_id] = array();
    }
    
    if (in_array($product_id, $user_likes[$user_id])) {
        // 좋아요 취소
        $likes--;
        $user_likes[$user_id] = array_diff($user_likes[$user_id], array($product_id));
        $liked = false;
    } else {
        // 좋아요 추가
        $likes++;
        $user_likes[$user_id][] = $product_id;
        $liked = true;
    }
    
    update_post_meta($product_id, '_muziyeppo_likes', $likes);
    update_option('muziyeppo_user_likes', $user_likes);
    
    wp_send_json_success(array(
        'likes' => $likes,
        'liked' => $liked
    ));
}

// 데이터베이스 테이블 생성
function muziyeppo_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // 주문 테이블
    $table_name = $wpdb->prefix . 'muziyeppo_orders';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        quantity int(11) NOT NULL DEFAULT 1,
        price decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_product_id (product_id),
        KEY idx_status (status)
    ) $charset_collate;";
    
    dbDelta($sql);
    
    // 장바구니 테이블
    $table_name = $wpdb->prefix . 'muziyeppo_cart';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id varchar(100) NOT NULL,
        product_id bigint(20) NOT NULL,
        quantity int(11) NOT NULL DEFAULT 1,
        added_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_product_id (product_id)
    ) $charset_collate;";
    
    dbDelta($sql);
}

// 장바구니에 추가
add_action('wp_ajax_muziyeppo_add_to_cart', 'muziyeppo_add_to_cart');
add_action('wp_ajax_nopriv_muziyeppo_add_to_cart', 'muziyeppo_add_to_cart');
function muziyeppo_add_to_cart() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'muziyeppo_cart';
    
    // 이미 장바구니에 있는지 확인
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %s AND product_id = %d",
        $user_id, $product_id
    ));
    
    if ($existing) {
        // 수량 업데이트
        $wpdb->update(
            $table_name,
            array('quantity' => $existing->quantity + $quantity),
            array('id' => $existing->id)
        );
    } else {
        // 새로 추가
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'added_at' => current_time('mysql')
            )
        );
    }
    
    wp_send_json_success();
}

// 더 많은 상품 로드
add_action('wp_ajax_muziyeppo_load_more', 'muziyeppo_load_more');
add_action('wp_ajax_nopriv_muziyeppo_load_more', 'muziyeppo_load_more');
function muziyeppo_load_more() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $page = intval($_POST['page']);
    
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => 12,
        'paged' => $page
    );
    
    $products = new WP_Query($args);
    
    ob_start();
    
    if ($products->have_posts()) {
        while ($products->have_posts()) : $products->the_post();
            // 상품 카드 HTML 출력
            include plugin_dir_path(__FILE__) . 'templates/product-card.php';
        endwhile;
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(array('html' => $html));
}

// 관리자 메뉴 추가
add_action('admin_menu', 'muziyeppo_admin_menu');
function muziyeppo_admin_menu() {
    add_menu_page(
        '무지예뽀 쇼핑몰',
        '무지예뽀',
        'manage_options',
        'muziyeppo-admin',
        'muziyeppo_admin_page',
        'dashicons-cart',
        30
    );
    
    add_submenu_page(
        'muziyeppo-admin',
        '대시보드',
        '대시보드',
        'manage_options',
        'muziyeppo-admin',
        'muziyeppo_admin_page'
    );
    
    add_submenu_page(
        'muziyeppo-admin',
        '설정',
        '설정',
        'manage_options',
        'muziyeppo-settings',
        'muziyeppo_settings_page'
    );
}

// 관리자 페이지
function muziyeppo_admin_page() {
    ?>
    <div class="wrap">
        <h1>무지예뽀 쇼핑몰 대시보드</h1>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>전체 상품</h2>
                <?php
                $count = wp_count_posts('muziyeppo_product');
                echo '<p style="font-size: 36px; margin: 20px 0; color: #000;">' . $count->publish . '</p>';
                ?>
                <a href="<?php echo admin_url('edit.php?post_type=muziyeppo_product'); ?>" class="button">상품 관리</a>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>상품 카테고리</h2>
                <?php
                $terms = get_terms(array('taxonomy' => 'product_category', 'hide_empty' => false));
                echo '<p style="font-size: 36px; margin: 20px 0; color: #000;">' . count($terms) . '</p>';
                ?>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=product_category&post_type=muziyeppo_product'); ?>" class="button">카테고리 관리</a>
            </div>
            
            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2>쇼트코드</h2>
                <p>다음 쇼트코드를 사용하여 상품을 표시하세요:</p>
                <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0;">[muziyeppo_products]</code>
                <p style="font-size: 14px; color: #666;">옵션: category="slug", limit="12", orderby="date", order="DESC"</p>
            </div>
        </div>
        
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
            <h2>최근 등록 상품</h2>
            <?php
            $recent_products = get_posts(array(
                'post_type' => 'muziyeppo_product',
                'posts_per_page' => 5,
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            if ($recent_products) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead><tr><th>상품명</th><th>브랜드</th><th>가격</th><th>등록일</th></tr></thead>';
                echo '<tbody>';
                foreach ($recent_products as $product) {
                    $brand = get_post_meta($product->ID, '_muziyeppo_brand', true);
                    $price = get_post_meta($product->ID, '_muziyeppo_price', true);
                    echo '<tr>';
                    echo '<td><a href="' . esc_url(get_edit_post_link($product->ID)) . '">' . esc_html($product->post_title) . '</a></td>';
                    echo '<td>' . esc_html($brand ?: '—') . '</td>';
                    echo '<td>' . ($price ? number_format($price) . '원' : '—') . '</td>';
                    echo '<td>' . get_the_date('Y-m-d', $product->ID) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>등록된 상품이 없습니다.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

// 설정 페이지
function muziyeppo_settings_page() {
    ?>
    <div class="wrap">
        <h1>무지예뽀 쇼핑몰 설정</h1>
        
        <form method="post" action="options.php">
            <?php settings_fields('muziyeppo_settings'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">통화 단위</th>
                    <td>
                        <input type="text" name="muziyeppo_currency" value="<?php echo esc_attr(get_option('muziyeppo_currency', '원')); ?>" />
                        <p class="description">상품 가격 뒤에 표시될 통화 단위</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">페이지당 상품 수</th>
                    <td>
                        <input type="number" name="muziyeppo_products_per_page" value="<?php echo esc_attr(get_option('muziyeppo_products_per_page', 12)); ?>" />
                        <p class="description">상품 목록 페이지에 표시할 상품 수</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// 설정 등록
add_action('admin_init', 'muziyeppo_register_settings');
function muziyeppo_register_settings() {
    register_setting('muziyeppo_settings', 'muziyeppo_currency');
    register_setting('muziyeppo_settings', 'muziyeppo_products_per_page');
}

// 스타일 및 스크립트 등록
add_action('wp_enqueue_scripts', 'muziyeppo_enqueue_scripts');
function muziyeppo_enqueue_scripts() {
    // 상품 관련 페이지 조건 확장 (카테고리 페이지 추가)
    if (is_singular('muziyeppo_product') || is_post_type_archive('muziyeppo_product') || is_tax('product_category')) {
        wp_enqueue_style('muziyeppo-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_enqueue_script('muziyeppo-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('muziyeppo-script', 'muziyeppo_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('muziyeppo_ajax_nonce')
        ));
    }
}

// ===== 장바구니 기능 강화 =====

// 장바구니에 추가 (수량 포함) - 중복 함수 제거됨
function muziyeppo_add_to_cart_with_quantity() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'muziyeppo_cart';
    
    // 이미 장바구니에 있는지 확인
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %s AND product_id = %d",
        $user_id, $product_id
    ));
    
    if ($existing) {
        // 수량 업데이트
        $wpdb->update(
            $table_name,
            array('quantity' => $existing->quantity + $quantity),
            array('id' => $existing->id)
        );
    } else {
        // 새로 추가
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'product_id' => $product_id,
                'quantity' => $quantity,
                'added_at' => current_time('mysql')
            )
        );
    }
    
    // 장바구니 개수 반환
    $cart_count = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(quantity) FROM $table_name WHERE user_id = %s",
        $user_id
    ));
    
    wp_send_json_success(array(
        'cart_count' => intval($cart_count)
    ));
}

// 장바구니 개수 가져오기
add_action('wp_ajax_muziyeppo_get_cart_count', 'muziyeppo_get_cart_count');
add_action('wp_ajax_nopriv_muziyeppo_get_cart_count', 'muziyeppo_get_cart_count');
function muziyeppo_get_cart_count() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'muziyeppo_cart';
    
    $cart_count = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(quantity) FROM $table_name WHERE user_id = %s",
        $user_id
    ));
    
    wp_send_json_success(array(
        'count' => intval($cart_count)
    ));
}

// ===== 검색 기능 =====

// 상품 검색
add_action('wp_ajax_muziyeppo_search_products', 'muziyeppo_search_products');
add_action('wp_ajax_nopriv_muziyeppo_search_products', 'muziyeppo_search_products');
function muziyeppo_search_products() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $keyword = sanitize_text_field($_POST['keyword']);
    
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => 20,
        's' => $keyword,
        'post_status' => 'publish'
    );
    
    // 메타 쿼리로 브랜드 검색 추가
    $args['meta_query'] = array(
        'relation' => 'OR',
        array(
            'key' => '_muziyeppo_brand',
            'value' => $keyword,
            'compare' => 'LIKE'
        )
    );
    
    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            muziyeppo_render_product_card();
        }
    } else {
        echo '<p class="no-results">검색 결과가 없습니다.</p>';
    }
    
    $html = ob_get_clean();
    wp_reset_postdata();
    
    wp_send_json_success(array('html' => $html));
}

// ===== 사용자 기능 =====

// 포인트 테이블 생성
function muziyeppo_create_points_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'muziyeppo_points';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points int(11) NOT NULL,
        type varchar(20) NOT NULL,
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_type (type)
    ) $charset_collate;";
    
    dbDelta($sql);
}

// 쿠폰 테이블 생성
function muziyeppo_create_coupons_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'muziyeppo_coupons';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        code varchar(50) NOT NULL UNIQUE,
        type varchar(20) NOT NULL,
        value decimal(10,2) NOT NULL,
        min_amount decimal(10,2) DEFAULT 0,
        expiry_date date,
        usage_limit int(11) DEFAULT 1,
        used_count int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_code (code),
        KEY idx_expiry_date (expiry_date)
    ) $charset_collate;";
    
    dbDelta($sql);
}

// 사용자 쿠폰 테이블
function muziyeppo_create_user_coupons_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'muziyeppo_user_coupons';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        coupon_id mediumint(9) NOT NULL,
        used tinyint(1) DEFAULT 0,
        used_at datetime,
        PRIMARY KEY (id),
        KEY idx_user_id (user_id),
        KEY idx_coupon_id (coupon_id),
        KEY idx_used (used)
    ) $charset_collate;";
    
    dbDelta($sql);
}

// 활성화 시 추가 테이블 생성 - 메인 활성화 함수에 통합됨

// ===== 쇼트코드 추가 =====

// 검색 폼 쇼트코드
add_shortcode('muziyeppo_search', 'muziyeppo_search_shortcode');
function muziyeppo_search_shortcode($atts) {
    $atts = shortcode_atts(array(
        'placeholder' => '상품을 검색하세요'
    ), $atts);
    
    ob_start();
    ?>
    <form class="muziyeppo-search-form" onsubmit="return false;">
        <input type="text" 
               class="muziyeppo-search-input" 
               placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
               onkeyup="muziyeppoSearchProducts(this.value)">
        <button type="submit" class="muziyeppo-search-btn">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </button>
    </form>
    <div id="muziyeppo-search-results"></div>
    <?php
    return ob_get_clean();
}

// 장바구니 쇼트코드
add_shortcode('muziyeppo_cart', 'muziyeppo_cart_shortcode');
function muziyeppo_cart_shortcode() {
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'muziyeppo_cart';
    
    $cart_items = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, p.post_title as product_name 
         FROM $table_name c 
         JOIN {$wpdb->posts} p ON c.product_id = p.ID 
         WHERE c.user_id = %s",
        $user_id
    ));
    
    ob_start();
    ?>
    <div class="muziyeppo-cart">
        <?php if ($cart_items) : ?>
            <div class="cart-items">
                <?php foreach ($cart_items as $item) : 
                    $price = get_post_meta($item->product_id, '_muziyeppo_price', true);
                    $subtotal = $price * $item->quantity;
                ?>
                    <div class="cart-item">
                        <div class="cart-item-image">
                            <?php echo get_the_post_thumbnail($item->product_id, 'thumbnail'); ?>
                        </div>
                        <div class="cart-item-info">
                            <h4><?php echo esc_html($item->product_name); ?></h4>
                            <p>수량: <?php echo $item->quantity; ?></p>
                            <p><?php echo number_format($subtotal); ?>원</p>
                        </div>
                        <button class="remove-item" onclick="removeFromCart(<?php echo $item->product_id; ?>)">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="cart-summary">
                <button class="checkout-btn">주문하기</button>
            </div>
        <?php else : ?>
            <p class="empty-cart">장바구니가 비어있습니다.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// ===== 헬퍼 함수 =====

// 상품 카드 렌더링
function muziyeppo_render_product_card() {
    $product_id = get_the_ID();
    $brand = get_post_meta($product_id, '_muziyeppo_brand', true);
    $price = get_post_meta($product_id, '_muziyeppo_price', true);
    $original_price = get_post_meta($product_id, '_muziyeppo_original_price', true);
    $discount = get_post_meta($product_id, '_muziyeppo_discount', true);
    $rating = get_post_meta($product_id, '_muziyeppo_rating', true);
    $reviews = get_post_meta($product_id, '_muziyeppo_reviews', true);
    $likes = get_post_meta($product_id, '_muziyeppo_likes', true);
    $is_new = get_post_meta($product_id, '_muziyeppo_is_new', true);
    ?>
    <div class="product-card" data-product-id="<?php echo $product_id; ?>">
        <a href="<?php the_permalink(); ?>" class="product-link">
            <div class="product-image-container">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium', array('class' => 'product-image')); ?>
                <?php else : ?>
                    <img src="<?php echo plugins_url('assets/no-image.jpg', __FILE__); ?>" alt="<?php the_title(); ?>" class="product-image">
                <?php endif; ?>
                
                <div class="product-badges">
                    <?php if ($discount) : ?>
                        <span class="badge badge-sale"><?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                    <?php if ($is_new) : ?>
                        <span class="badge badge-new">NEW</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="product-info">
                <?php if ($brand) : ?>
                    <p class="product-brand"><?php echo esc_html($brand); ?></p>
                <?php endif; ?>
                
                <h4 class="product-name"><?php the_title(); ?></h4>
                
                <div class="product-price">
                    <?php if ($discount) : ?>
                        <span class="discount-rate"><?php echo $discount; ?>%</span>
                    <?php endif; ?>
                    <?php if ($price) : ?>
                        <span class="price"><?php echo number_format($price); ?>원</span>
                    <?php endif; ?>
                </div>
            </div>
        </a>
    </div>
    <?php
}

// ===== 스크립트 인라인 추가 =====

add_action('wp_footer', 'muziyeppo_inline_scripts');
function muziyeppo_inline_scripts() {
    if (!is_singular('muziyeppo_product') && !is_post_type_archive('muziyeppo_product')) {
        return;
    }
    ?>
    <script>
    // 검색 기능
    let searchTimer;
    function muziyeppoSearchProducts(keyword) {
        clearTimeout(searchTimer);
        
        if (keyword.length < 2) {
            document.getElementById('muziyeppo-search-results').innerHTML = '';
            return;
        }
        
        searchTimer = setTimeout(function() {
            fetch(muziyeppo_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'muziyeppo_search_products',
                    keyword: keyword,
                    nonce: muziyeppo_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('muziyeppo-search-results').innerHTML = data.data.html;
                }
            });
        }, 500);
    }
    
    // 장바구니에서 제거
    function removeFromCart(productId) {
        if (confirm('장바구니에서 제거하시겠습니까?')) {
            // AJAX 요청으로 장바구니에서 제거
            location.reload();
        }
    }
    </script>
    <?php
}

// ===== REST API 엔드포인트 (선택사항) =====

add_action('rest_api_init', 'muziyeppo_register_rest_routes');
function muziyeppo_register_rest_routes() {
    // 상품 목록 API
    register_rest_route('muziyeppo/v1', '/products', array(
        'methods' => 'GET',
        'callback' => 'muziyeppo_get_products_api',
        'permission_callback' => '__return_true'
    ));
    
    // 장바구니 API
    register_rest_route('muziyeppo/v1', '/cart', array(
        'methods' => 'GET',
        'callback' => 'muziyeppo_get_cart_api',
        'permission_callback' => '__return_true'
    ));
}

function muziyeppo_get_products_api($request) {
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => $request->get_param('per_page') ?: 10,
        'paged' => $request->get_param('page') ?: 1
    );
    
    $products = get_posts($args);
    $data = array();
    
    foreach ($products as $product) {
        $data[] = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'price' => get_post_meta($product->ID, '_muziyeppo_price', true),
            'brand' => get_post_meta($product->ID, '_muziyeppo_brand', true),
            'discount' => get_post_meta($product->ID, '_muziyeppo_discount', true),
            'image' => get_the_post_thumbnail_url($product->ID, 'medium')
        );
    }
    
    return new WP_REST_Response($data, 200);
}

// ===== 추가 스타일 =====

add_action('wp_head', 'muziyeppo_additional_styles');
function muziyeppo_additional_styles() {
    ?>
    <style>
    /* 검색 폼 스타일 */
    .muziyeppo-search-form {
        position: relative;
        max-width: 600px;
        margin: 0 auto 30px;
    }
    
    .muziyeppo-search-input {
        width: 100%;
        padding: 12px 48px 12px 20px;
        border: 2px solid #000;
        border-radius: 30px;
        font-size: 16px;
        outline: none;
        transition: all 0.3s;
    }
    
    .muziyeppo-search-input:focus {
        box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
    }
    
    .muziyeppo-search-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: #000;
        color: #fff;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .muziyeppo-search-btn:hover {
        background: #333;
    }
    
    /* 장바구니 스타일 */
    .muziyeppo-cart {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .cart-item {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .cart-item-image {
        width: 80px;
        height: 80px;
        flex-shrink: 0;
    }
    
    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 8px;
    }
    
    .cart-item-info {
        flex: 1;
    }
    
    .cart-item-info h4 {
        margin: 0 0 8px;
        font-size: 16px;
    }
    
    .cart-item-info p {
        margin: 4px 0;
        color: #666;
    }
    
    .remove-item {
        background: none;
        border: none;
        font-size: 24px;
        color: #999;
        cursor: pointer;
        transition: color 0.3s;
    }
    
    .remove-item:hover {
        color: #000;
    }
    
    .cart-summary {
        padding: 20px;
        text-align: right;
    }
    
    .checkout-btn {
        background: #000;
        color: #fff;
        border: none;
        padding: 14px 40px;
        border-radius: 30px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .checkout-btn:hover {
        background: #333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .empty-cart {
        text-align: center;
        padding: 60px 0;
        color: #666;
    }
    </style>
    <?php
}

/**
 * 무지예뽀 쇼핑몰 - HTML 앱 통합
 * 
 * 이 코드를 기존 muziyeppo-shop.php 파일의 맨 아래에 추가하세요
 */

// ===== HTML 앱 통합 =====

// 쇼트코드 등록 - 전체 HTML 앱
add_shortcode('muziyeppo_app', 'muziyeppo_render_html_app');
function muziyeppo_render_html_app($atts) {
    $atts = shortcode_atts(array(
        'mode' => 'full', // full, embedded
        'height' => 'auto',
    ), $atts);
    
    ob_start();
    ?>
    <div id="muziyeppo-app-container" class="muziyeppo-app-<?php echo esc_attr($atts['mode']); ?>">
        <?php muziyeppo_render_app_html(); ?>
    </div>
    <?php
    return ob_get_clean();
}

// HTML 앱 렌더링 함수
function muziyeppo_render_app_html() {
    // 현재 사용자 정보 가져오기
    $current_user = wp_get_current_user();
    $user_data = array(
        'isLoggedIn' => is_user_logged_in(),
        'user' => is_user_logged_in() ? array(
            'id' => $current_user->ID,
            'name' => $current_user->display_name,
            'email' => $current_user->user_email,
            'avatar' => get_avatar_url($current_user->ID)
        ) : null
    );
    
    // 워드프레스 상품 데이터 가져오기
    $products = muziyeppo_get_products_for_app();
    
    ?>
    <!-- 헤더 -->
    <header class="header" id="header">
        <div class="top-banner">
            🎁 신규 회원 가입시 10,000원 쿠폰 즉시 지급!
        </div>
        <div class="main-header">
            <h1 class="logo" onclick="showPage('home')">무지예뽀</h1>
            <div class="header-icons">
                <button class="icon-btn" onclick="toggleSearch()">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
                <button class="icon-btn" onclick="showPage('notifications')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span class="notification-dot" id="notificationDot"></span>
                </button>
                <button class="icon-btn" onclick="showPage('cart')">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </button>
            </div>
        </div>
        
        <!-- 검색바 -->
        <div class="search-bar" id="searchBar">
            <div class="search-input-container">
                <input type="text" class="search-input" id="headerSearchInput" placeholder="✨ 상품명으로 검색" onkeypress="handleHeaderSearchKeyPress(event)">
                <button class="search-submit" onclick="performHeaderSearch()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </header>

    <!-- 홈 페이지 -->
    <div class="page active" id="homePage">
        <?php if (!is_user_logged_in()) : ?>
        <!-- 로그인 섹션 -->
        <div class="login-section" id="loginSection">
            <h2>무지예뽀와 함께하세요!</h2>
            <p>워드프레스 계정으로 간편하게 시작하세요</p>
            <button class="google-login-button" onclick="redirectToLogin()">
                <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    <path fill="none" d="M1 1h22v22H1z"/>
                </svg>
                워드프레스로 로그인하기
            </button>
        </div>
        <?php endif; ?>

        <!-- 메인 배너 -->
        <div class="main-banner">
            <div class="banner-container" id="bannerContainer">
                <div class="banner-item">
                    <div class="banner-content">
                        <h2 class="banner-title">BLACK WEEK</h2>
                        <p class="banner-subtitle">프리미엄 브랜드 최대 80% 할인</p>
                        <button class="banner-btn" onclick="showToast('BLACK WEEK 페이지로 이동')">지금 쇼핑하기</button>
                    </div>
                </div>
                <div class="banner-item">
                    <div class="banner-content">
                        <h2 class="banner-title">신규 회원 혜택</h2>
                        <p class="banner-subtitle">첫 구매시 20% 추가 할인</p>
                        <button class="banner-btn" onclick="showToast('회원가입 페이지로 이동')">회원가입 하기</button>
                    </div>
                </div>
                <div class="banner-item">
                    <div class="banner-content">
                        <h2 class="banner-title">베스트 상품</h2>
                        <p class="banner-subtitle">이번 주 가장 인기있는 상품</p>
                        <button class="banner-btn" onclick="showToast('베스트 상품 페이지로 이동')">베스트 보기</button>
                    </div>
                </div>
            </div>
            
            <div class="banner-indicators">
                <button class="indicator active" onclick="setBannerIndex(0)"></button>
                <button class="indicator" onclick="setBannerIndex(1)"></button>
                <button class="indicator" onclick="setBannerIndex(2)"></button>
            </div>
        </div>

        <!-- 카테고리 네비게이션 -->
        <nav class="category-nav">
            <div class="category-grid">
                <?php muziyeppo_render_category_nav(); ?>
            </div>
        </nav>

        <!-- 서브 카테고리 -->
        <div class="sub-categories" id="subCategories">
            <div class="sub-category-list" id="subCategoryList"></div>
        </div>

        <!-- 실시간 랭킹 섹션 -->
        <section class="ranking-section">
            <div class="section-header">
                <h3 class="section-title">
                    🔥 실시간 인기 상품
                    <span class="live-indicator">
                        <span class="live-dot"></span>
                        LIVE
                    </span>
                </h3>
                <a href="#" class="view-all" onclick="showToast('전체보기 페이지로 이동'); return false;">전체보기 →</a>
            </div>
            
            <div class="product-grid" id="productGrid">
                <!-- 워드프레스 상품 데이터로 채워짐 -->
            </div>

            <div class="loading hidden" id="loading">
                <div class="spinner"></div>
            </div>
        </section>
    </div>

    <!-- 카테고리 페이지 -->
    <div class="page" id="categoryPage">
        <div class="category-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">카테고리</h2>
            <div id="categoryList"></div>
        </div>
    </div>

    <!-- 검색 페이지 -->
    <div class="page" id="searchPage">
        <div class="search-page">
            <div style="position: relative;">
                <input type="text" class="search-page-input" id="searchPageInput" placeholder="상품명을 검색하세요" onkeypress="handleSearchKeyPress(event)">
                <button style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer;" onclick="performSearch()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
            
            <div class="popular-keywords">
                <h3>인기 검색어</h3>
                <div class="keyword-buttons">
                    <button class="keyword-btn" onclick="searchByKeyword('원피스')">원피스</button>
                    <button class="keyword-btn" onclick="searchByKeyword('가방')">가방</button>
                    <button class="keyword-btn" onclick="searchByKeyword('스니커즈')">스니커즈</button>
                    <button class="keyword-btn" onclick="searchByKeyword('화장품')">화장품</button>
                    <button class="keyword-btn" onclick="searchByKeyword('시계')">시계</button>
                </div>
            </div>
            
            <div class="search-results-section hidden" id="searchResultsSection">
                <h3 style="font-weight: 700; margin-bottom: 16px;">검색 결과</h3>
                <div id="searchResults"></div>
            </div>
        </div>
    </div>

    <!-- 찜 페이지 -->
    <div class="page" id="wishlistPage">
        <div class="wishlist-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">찜한 상품</h2>
            <div id="wishlistContent"></div>
        </div>
    </div>

    <!-- 마이페이지 -->
    <div class="page" id="mypagePage">
        <div class="mypage">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">마이페이지</h2>
            
            <div class="profile-card">
                <div class="profile-avatar" id="userAvatar">
                    <?php if (is_user_logged_in()) : ?>
                        <img src="<?php echo get_avatar_url($current_user->ID); ?>" alt="<?php echo esc_attr($current_user->display_name); ?>">
                    <?php else : ?>
                        👤
                    <?php endif; ?>
                </div>
                <p class="profile-name" id="userName"><?php echo is_user_logged_in() ? esc_html($current_user->display_name) : '무지예뽀 회원님'; ?></p>
                <p class="profile-email" id="userEmail"><?php echo is_user_logged_in() ? esc_html($current_user->user_email) : 'mujiyeppo@example.com'; ?></p>
            </div>
            
            <div class="menu-list">
                <button class="menu-btn" onclick="showPage('orderHistory')">
                    <span>주문 내역</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button class="menu-btn" onclick="showPage('coupon')">
                    <span>쿠폰함</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button class="menu-btn" onclick="showPage('point')">
                    <span>포인트</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <button class="menu-btn" onclick="showPage('support')">
                    <span>고객센터</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <?php if (is_user_logged_in()) : ?>
                <button class="menu-btn logout" onclick="logoutWordPress()">
                    <span>로그아웃</span>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 나머지 페이지들 -->
    <div class="page" id="notificationsPage">
        <div class="notifications-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">알림</h2>
            <div id="notificationsList"></div>
        </div>
    </div>

    <div class="page" id="cartPage">
        <div class="cart-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">장바구니</h2>
            <div id="cartContent"></div>
        </div>
    </div>

    <div class="page" id="orderHistoryPage">
        <div class="order-history-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">주문 내역</h2>
            <div id="orderHistoryContent"></div>
        </div>
    </div>

    <div class="page" id="couponPage">
        <div class="coupon-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">쿠폰함</h2>
            <div id="couponContent"></div>
        </div>
    </div>

    <div class="page" id="pointPage">
        <div class="point-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">포인트</h2>
            <div id="pointContent"></div>
        </div>
    </div>

    <div class="page" id="supportPage">
        <div class="support-page">
            <h2 style="font-size: 24px; font-weight: 700; margin-bottom: 24px;">고객센터</h2>
            <div id="supportContent"></div>
        </div>
    </div>

    <!-- 모달 -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">상품 상세</h3>
                <button class="modal-close" onclick="closeModal()">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="modal-body" id="modalBody"></div>
        </div>
    </div>

    <!-- 토스트 메시지 -->
    <div class="toast" id="toast"></div>

    <!-- 맨 위로 가기 버튼 -->
    <button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
        </svg>
    </button>

    <!-- 하단 네비게이션 -->
    <nav class="bottom-nav">
        <div class="nav-grid">
            <button class="nav-item active" onclick="showPage('home')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="nav-label">홈</span>
            </button>
            <button class="nav-item" onclick="showPage('category')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <span class="nav-label">카테고리</span>
            </button>
            <button class="nav-item" onclick="showPage('search')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <span class="nav-label">검색</span>
            </button>
            <button class="nav-item" onclick="showPage('wishlist')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
                <span class="nav-label">찜</span>
            </button>
            <button class="nav-item" onclick="showPage('mypage')">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <span class="nav-label">마이</span>
            </button>
        </div>
    </nav>

    <script>
    // 워드프레스 연동 데이터
    window.wpMuziyeppo = {
        ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
        nonce: '<?php echo esc_js(wp_create_nonce('muziyeppo_ajax_nonce')); ?>',
        userData: <?php echo wp_json_encode($user_data); ?>,
        products: <?php echo wp_json_encode($products); ?>,
        loginUrl: '<?php echo esc_url(wp_login_url(get_permalink())); ?>',
        logoutUrl: '<?php echo esc_url(wp_logout_url(get_permalink())); ?>'
    };

    // 워드프레스 로그인 리다이렉트
    function redirectToLogin() {
        window.location.href = window.wpMuziyeppo.loginUrl;
    }

    // 워드프레스 로그아웃
    function logoutWordPress() {
        if (confirm('로그아웃 하시겠습니까?')) {
            window.location.href = window.wpMuziyeppo.logoutUrl;
        }
    }

    // 나머지 JavaScript 코드
    let currentPage = 'home';
    let selectedCategory = '';
    let selectedSubCategory = '';
    let currentBannerIndex = 0;
    let wishlistItems = [];
    let isLoading = false;
    let products = window.wpMuziyeppo.products;
    let currentUser = window.wpMuziyeppo.userData.isLoggedIn ? window.wpMuziyeppo.userData.user : null;
    let notifications = [
        { id: 1, title: '🎉 환영합니다!', content: '무지예뽀 가입을 축하드립니다.', date: '방금 전', read: false },
        { id: 2, title: '🛍️ 새로운 상품', content: '인기 상품이 입고되었습니다.', date: '1시간 전', read: false }
    ];

    // 초기화
    document.addEventListener('DOMContentLoaded', function() {
        renderProducts();
        renderCategories();
        updateNotificationDot();
        startBannerSlider();
        
        // 스크롤 이벤트
        window.addEventListener('scroll', handleScroll);
        
        // 장바구니 데이터 로드
        loadCartData();
        
        // 찜 목록 로드
        loadWishlistData();
    });

    // 워드프레스 AJAX로 장바구니 데이터 로드
    function loadCartData() {
        fetch(window.wpMuziyeppo.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'muziyeppo_get_cart_count',
                nonce: window.wpMuziyeppo.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateCartBadge(data.data.count);
            }
        });
    }

    // 찜 목록 로드
    function loadWishlistData() {
        // 로컬 스토리지에서 로드 (또는 AJAX로 서버에서 로드)
        const saved = localStorage.getItem('muziyeppo_wishlist');
        if (saved) {
            wishlistItems = JSON.parse(saved);
        }
    }

    // 찜하기 토글 (워드프레스 연동)
    function toggleWishlist(productId) {
        if (!currentUser) {
            showToast('로그인이 필요한 기능입니다');
            showPage('mypage');
            return;
        }
        
        fetch(window.wpMuziyeppo.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'muziyeppo_toggle_like',
                product_id: productId,
                nonce: window.wpMuziyeppo.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const index = wishlistItems.indexOf(productId);
                if (index > -1) {
                    wishlistItems.splice(index, 1);
                    showToast('찜 목록에서 제거되었습니다');
                } else {
                    wishlistItems.push(productId);
                    showToast('찜 목록에 추가되었습니다');
                }
                
                // 로컬 스토리지에 저장
                localStorage.setItem('muziyeppo_wishlist', JSON.stringify(wishlistItems));
                
                renderProducts();
                updateWishlistBadge();
            }
        });
    }

    // 장바구니 추가 (워드프레스 연동)
    function addToCart(productId) {
        const quantity = document.getElementById('quantity') ? 
            parseInt(document.getElementById('quantity').value) : 1;
        
        fetch(window.wpMuziyeppo.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'muziyeppo_add_to_cart',
                product_id: productId,
                quantity: quantity,
                nonce: window.wpMuziyeppo.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('장바구니에 추가되었습니다');
                updateCartBadge(data.data.cart_count);
            }
        });
    }

    // 검색 기능 (워드프레스 연동)
    function searchProducts(keyword) {
        const searchResultsSection = document.getElementById('searchResultsSection');
        const searchResults = document.getElementById('searchResults');
        
        searchResultsSection.classList.remove('hidden');
        searchResults.innerHTML = '<div class="search-loading"><div class="spinner"></div></div>';
        
        fetch(window.wpMuziyeppo.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'muziyeppo_search_products',
                keyword: keyword,
                nonce: window.wpMuziyeppo.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                searchResults.innerHTML = data.data.html;
            } else {
                searchResults.innerHTML = '<div class="no-results">검색 결과가 없습니다</div>';
            }
        });
    }

    // 나머지 모든 JavaScript 함수들...
    // (기존 HTML의 모든 JavaScript 함수들을 여기에 포함)
    <?php muziyeppo_output_app_javascript(); ?>
    </script>
    <?php
}

// JavaScript 출력 함수
function muziyeppo_output_app_javascript() {
    ?>
    // 상품 렌더링
    function renderProducts(filteredProducts = null) {
        const productGrid = document.getElementById('productGrid');
        const productsToRender = filteredProducts || products;
        
        productGrid.innerHTML = productsToRender.map(product => `
            <div class="product-card" onclick="openProduct(${product.id})">
                <div class="product-image-container">
                    <img src="${product.image}" alt="${product.name}" class="product-image">
                    <div class="product-badges">
                        ${product.discount ? `<span class="badge badge-sale">${product.discount}% OFF</span>` : ''}
                        ${product.isNew ? '<span class="badge badge-new">NEW</span>' : ''}
                    </div>
                    <button class="wishlist-btn ${wishlistItems.includes(product.id) ? 'active' : ''}" onclick="event.stopPropagation(); toggleWishlist(${product.id})">
                        <svg fill="${wishlistItems.includes(product.id) ? 'currentColor' : 'none'}" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
                <div class="product-info">
                    <p class="product-brand">${product.brand}</p>
                    <h4 class="product-name">${product.name}</h4>
                    <div class="product-price">
                        ${product.discount ? `<span class="discount-rate">${product.discount}%</span>` : ''}
                        <span class="price">${product.price.toLocaleString()}원</span>
                        ${product.originalPrice ? `<span class="original-price">${product.originalPrice.toLocaleString()}원</span>` : ''}
                    </div>
                    <div class="product-stats">
                        ${product.rating ? `<span class="stat-item"><span class="rating">★</span> ${product.rating}</span>` : ''}
                        ${product.reviews ? `<span class="stat-item">리뷰 ${product.reviews}</span>` : ''}
                        ${product.likes ? `<span class="stat-item">♥ ${product.likes > 1000 ? (product.likes/1000).toFixed(1) + 'k' : product.likes}</span>` : ''}
                    </div>
                </div>
            </div>
        `).join('');
    }

    // 페이지 전환
    function showPage(pageName) {
        document.querySelectorAll('.page').forEach(page => {
            page.classList.remove('active');
        });
        
        const page = document.getElementById(pageName + 'Page');
        if (page) {
            page.classList.add('active');
            currentPage = pageName;
        }
        
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const pageIndex = ['home', 'category', 'search', 'wishlist', 'mypage'].indexOf(pageName);
        if (pageIndex !== -1) {
            document.querySelectorAll('.nav-item')[pageIndex].classList.add('active');
        }
        
        if (pageName === 'wishlist') {
            renderWishlist();
        } else if (pageName === 'notifications') {
            renderNotifications();
            markNotificationsAsRead();
        } else if (pageName === 'cart') {
            renderCart();
        }
        
        window.scrollTo(0, 0);
    }

    // 나머지 모든 함수들 구현...
    function toggleSearch() {
        const searchBar = document.getElementById('searchBar');
        searchBar.classList.toggle('show');
        
        if (searchBar.classList.contains('show')) {
            document.getElementById('headerSearchInput').focus();
        }
    }

    function performSearch() {
        const keyword = document.getElementById('searchPageInput').value.trim();
        if (keyword) {
            searchProducts(keyword);
        }
    }

    function performHeaderSearch() {
        const keyword = document.getElementById('headerSearchInput').value.trim();
        if (keyword) {
            showPage('search');
            document.getElementById('searchPageInput').value = keyword;
            searchProducts(keyword);
            toggleSearch();
        }
    }

    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            performSearch();
        }
    }

    function handleHeaderSearchKeyPress(event) {
        if (event.key === 'Enter') {
            performHeaderSearch();
        }
    }

    function searchByKeyword(keyword) {
        document.getElementById('searchPageInput').value = keyword;
        searchProducts(keyword);
    }

    function startBannerSlider() {
        setInterval(() => {
            currentBannerIndex = (currentBannerIndex + 1) % 3;
            updateBanner();
        }, 5000);
    }

    function setBannerIndex(index) {
        currentBannerIndex = index;
        updateBanner();
    }

    function updateBanner() {
        const bannerContainer = document.getElementById('bannerContainer');
        bannerContainer.style.transform = `translateX(-${currentBannerIndex * 100}%)`;
        
        document.querySelectorAll('.indicator').forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentBannerIndex);
        });
    }

    function showToast(message) {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    function openProduct(productId) {
        const product = products.find(p => p.id === productId);
        if (!product) return;
        
        document.getElementById('modalTitle').textContent = product.name;
        document.getElementById('modalBody').innerHTML = `
            <img src="${product.image}" alt="${product.name}" style="width: 100%; border-radius: 8px; margin-bottom: 16px;">
            <p style="color: #666; margin-bottom: 8px;">${product.brand}</p>
            <h3 style="font-size: 20px; margin-bottom: 16px;">${product.name}</h3>
            <div style="display: flex; align-items: baseline; gap: 12px; margin-bottom: 16px;">
                ${product.discount ? `<span style="font-size: 24px; font-weight: 700; color: #000;">${product.discount}%</span>` : ''}
                <span style="font-size: 24px; font-weight: 700;">${product.price.toLocaleString()}원</span>
                ${product.originalPrice ? `<span style="font-size: 16px; color: #999; text-decoration: line-through;">${product.originalPrice.toLocaleString()}원</span>` : ''}
            </div>
            <div style="display: flex; gap: 16px; margin-bottom: 24px; color: #666;">
                ${product.rating ? `<span>⭐ ${product.rating}</span>` : ''}
                ${product.reviews ? `<span>리뷰 ${product.reviews}</span>` : ''}
                ${product.likes ? `<span>♥ ${product.likes}</span>` : ''}
            </div>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">수량</label>
                <input type="number" id="quantity" value="1" min="1" max="99" style="width: 100px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <button class="primary-btn" style="width: 100%; margin-bottom: 12px;" onclick="addToCart(${product.id})">장바구니 담기</button>
            <button class="primary-btn" style="width: 100%; background: #fff; color: #000; border: 2px solid #000;" onclick="showToast('구매 페이지로 이동')">바로 구매</button>
        `;
        
        document.getElementById('productModal').classList.add('show');
    }

    function closeModal() {
        document.getElementById('productModal').classList.remove('show');
    }

    let lastScrollTop = 0;
    function handleScroll() {
        const header = document.getElementById('header');
        const st = window.pageYOffset || document.documentElement.scrollTop;
        const scrollToTopBtn = document.getElementById('scrollToTop');
        
        if (st > lastScrollTop && st > 100) {
            header.style.transform = 'translateY(-100%)';
        } else {
            header.style.transform = 'translateY(0)';
        }
        
        if (st > 300) {
            scrollToTopBtn.classList.add('show');
        } else {
            scrollToTopBtn.classList.remove('show');
        }
        
        lastScrollTop = st <= 0 ? 0 : st;
    }

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    function renderWishlist() {
        const wishlistContent = document.getElementById('wishlistContent');
        
        if (!currentUser) {
            wishlistContent.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="empty-text">로그인 후 이용해주세요</p>
                    <button class="primary-btn" onclick="redirectToLogin()">로그인하기</button>
                </div>
            `;
            return;
        }
        
        const wishlistProducts = products.filter(p => wishlistItems.includes(p.id));
        
        if (wishlistProducts.length === 0) {
            wishlistContent.innerHTML = `
                <div class="empty-state">
                    <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <p class="empty-text">찜한 상품이 없습니다</p>
                    <button class="primary-btn" onclick="showPage('home')">상품 둘러보기</button>
                </div>
            `;
        } else {
            wishlistContent.innerHTML = `
                <div class="product-grid">
                    ${wishlistProducts.map(product => `
                        <div class="product-card" onclick="openProduct(${product.id})">
                            <div class="product-image-container">
                                <img src="${product.image}" alt="${product.name}" class="product-image">
                                <div class="product-badges">
                                    ${product.discount ? `<span class="badge badge-sale">${product.discount}% OFF</span>` : ''}
                                </div>
                                <button class="wishlist-btn active" onclick="event.stopPropagation(); toggleWishlist(${product.id})">
                                    <svg fill="currentColor" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                </button>
                            </div>
                            <div class="product-info">
                                <p class="product-brand">${product.brand}</p>
                                <h4 class="product-name">${product.name}</h4>
                                <div class="product-price">
                                    ${product.discount ? `<span class="discount-rate">${product.discount}%</span>` : ''}
                                    <span class="price">${product.price.toLocaleString()}원</span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
    }

    function updateWishlistBadge() {
        // 하단 네비게이션 배지 업데이트
    }

    function updateCartBadge(count) {
        // 장바구니 배지 업데이트
    }

    function renderCategories() {
        // 카테고리 렌더링
    }

    function renderNotifications() {
        const notificationsList = document.getElementById('notificationsList');
        
        notificationsList.innerHTML = notifications.map(notif => `
            <div class="notification-item ${!notif.read ? 'unread' : ''}" onclick="readNotification(${notif.id})">
                <h4 class="notification-title">${notif.title}</h4>
                <p class="notification-content">${notif.content}</p>
                <p class="notification-date">${notif.date}</p>
            </div>
        `).join('');
    }

    function readNotification(id) {
        const notif = notifications.find(n => n.id === id);
        if (notif) {
            notif.read = true;
            renderNotifications();
            updateNotificationDot();
        }
    }

    function markNotificationsAsRead() {
        notifications.forEach(n => n.read = true);
        updateNotificationDot();
    }

    function updateNotificationDot() {
        const unreadCount = notifications.filter(n => !n.read).length;
        const dot = document.getElementById('notificationDot');
        dot.style.display = unreadCount > 0 ? 'block' : 'none';
    }

    function renderCart() {
        const cartContent = document.getElementById('cartContent');
        cartContent.innerHTML = `
            <div class="empty-state">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <p class="empty-text">장바구니가 비어있습니다</p>
                <button class="primary-btn" onclick="showPage('home')">쇼핑 계속하기</button>
            </div>
        `;
    }

    // 모달 외부 클릭시 닫기
    document.getElementById('productModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    <?php
}

// 워드프레스 상품 데이터 변환
function muziyeppo_get_products_for_app() {
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => 20,
        'post_status' => 'publish'
    );
    
    $products = get_posts($args);
    $product_data = array();
    
    foreach ($products as $product) {
        $product_id = $product->ID;
        $brand = get_post_meta($product_id, '_muziyeppo_brand', true);
        $price = get_post_meta($product_id, '_muziyeppo_price', true);
        $original_price = get_post_meta($product_id, '_muziyeppo_original_price', true);
        $discount = get_post_meta($product_id, '_muziyeppo_discount', true);
        $rating = get_post_meta($product_id, '_muziyeppo_rating', true);
        $reviews = get_post_meta($product_id, '_muziyeppo_reviews', true);
        $likes = get_post_meta($product_id, '_muziyeppo_likes', true);
        $is_new = get_post_meta($product_id, '_muziyeppo_is_new', true);
        
        $categories = wp_get_post_terms($product_id, 'product_category');
        $category_name = !empty($categories) ? $categories[0]->name : '';
        
        $product_data[] = array(
            'id' => $product_id,
            'name' => $product->post_title,
            'brand' => $brand ?: '무지예뽀',
            'price' => intval($price),
            'originalPrice' => intval($original_price),
            'discount' => intval($discount),
            'image' => get_the_post_thumbnail_url($product_id, 'medium') ?: 'https://via.placeholder.com/300x300/f8f8f8/333333?text=No+Image',
            'rating' => floatval($rating),
            'reviews' => intval($reviews),
            'likes' => intval($likes),
            'isNew' => $is_new === '1',
            'category' => $category_name,
            'subCategory' => ''
        );
    }
    
    return $product_data;
}

// 카테고리 네비게이션 렌더링
function muziyeppo_render_category_nav() {
    $categories = get_terms(array(
        'taxonomy' => 'product_category',
        'hide_empty' => false,
        'parent' => 0
    ));
    
    $icons = array(
        'default' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>',
        '의류' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        '가방' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        '신발' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
        '뷰티' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        '주얼리' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>'
    );
    
    foreach ($categories as $category) {
        $icon = isset($icons[$category->name]) ? $icons[$category->name] : $icons['default'];
        ?>
        <div class="category-item" onclick="selectCategory('<?php echo esc_js($category->name); ?>')">
            <div class="category-icon">
                <?php echo $icon; ?>
            </div>
            <span class="category-name"><?php echo esc_html($category->name); ?></span>
        </div>
        <?php
    }
}

// HTML 앱용 스타일 등록
add_action('wp_enqueue_scripts', 'muziyeppo_enqueue_app_styles');
function muziyeppo_enqueue_app_styles() {
    if (has_shortcode(get_post()->post_content, 'muziyeppo_app')) {
        wp_enqueue_style('muziyeppo-app-style', plugin_dir_url(__FILE__) . 'assets/app-style.css', array(), '1.0.0');
    }
}

// app-style.css 파일 생성 (assets/app-style.css)
add_action('init', 'muziyeppo_create_app_style');
function muziyeppo_create_app_style() {
    $css_content = '
/* 무지예뽀 HTML 앱 스타일 */
.muziyeppo-app-full {
    width: 100%;
    max-width: 100%;
    margin: 0 auto;
}

.muziyeppo-app-embedded {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}

/* 기본 리셋 */
#muziyeppo-app-container * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

#muziyeppo-app-container {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background-color: #ffffff;
    color: #000000;
    min-height: 100vh;
    padding-bottom: 70px;
}

/* 스크롤바 스타일링 */
#muziyeppo-app-container ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

#muziyeppo-app-container ::-webkit-scrollbar-track {
    background: #f1f1f1;
}

#muziyeppo-app-container ::-webkit-scrollbar-thumb {
    background: #333;
    border-radius: 4px;
}

#muziyeppo-app-container ::-webkit-scrollbar-thumb:hover {
    background: #000;
}

/* 헤더 */
#muziyeppo-app-container .header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

#muziyeppo-app-container .header.scrolled {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

#muziyeppo-app-container .top-banner {
    background: #000000;
    color: white;
    text-align: center;
    padding: 8px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

#muziyeppo-app-container .main-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #ffffff;
}

#muziyeppo-app-container .logo {
    font-size: 24px;
    font-weight: 900;
    color: #000000;
    letter-spacing: -1px;
    cursor: pointer;
}

#muziyeppo-app-container .header-icons {
    display: flex;
    gap: 16px;
}

#muziyeppo-app-container .icon-btn {
    background: none;
    border: none;
    color: #000;
    cursor: pointer;
    padding: 8px;
    border-radius: 50%;
    transition: all 0.3s;
    position: relative;
}

#muziyeppo-app-container .icon-btn:hover {
    background: #f5f5f5;
    transform: scale(1.1);
}

#muziyeppo-app-container .icon-btn svg {
    width: 20px;
    height: 20px;
}

#muziyeppo-app-container .notification-dot {
    position: absolute;
    top: 6px;
    right: 6px;
    width: 6px;
    height: 6px;
    background: #ff0000;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.2); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}

/* 검색바 */
#muziyeppo-app-container .search-bar {
    overflow: hidden;
    transition: max-height 0.3s ease;
    max-height: 0;
    background: #f8f8f8;
}

#muziyeppo-app-container .search-bar.show {
    max-height: 80px;
}

#muziyeppo-app-container .search-input-container {
    padding: 12px 16px;
    position: relative;
}

#muziyeppo-app-container .search-input {
    width: 100%;
    background: #ffffff;
    border-radius: 24px;
    padding: 10px 40px 10px 16px;
    border: 2px solid #000000;
    font-size: 14px;
    outline: none;
    transition: all 0.3s;
}

#muziyeppo-app-container .search-input:focus {
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

#muziyeppo-app-container .search-submit {
    position: absolute;
    right: 24px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
}

/* 페이지 컨테이너 */
#muziyeppo-app-container .page {
    display: none;
    min-height: calc(100vh - 70px);
    padding-top: 94px;
}

#muziyeppo-app-container .page.active {
    display: block;
}

/* 로그인 섹션 */
#muziyeppo-app-container .login-section {
    text-align: center;
    padding: 32px 16px;
    background: #f8f8f8;
    border-radius: 12px;
    margin: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

#muziyeppo-app-container .login-section h2 {
    font-size: 28px;
    font-weight: 900;
    color: #000000;
    margin-bottom: 16px;
    letter-spacing: -1px;
}

#muziyeppo-app-container .login-section p {
    color: #666666;
    margin-bottom: 24px;
    font-size: 16px;
}

#muziyeppo-app-container .google-login-button {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background-color: #ffffff;
    border: 2px solid #000000;
    border-radius: 24px;
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    color: #000000;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

#muziyeppo-app-container .google-login-button:hover {
    background-color: #000000;
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

#muziyeppo-app-container .google-icon {
    width: 20px;
    height: 20px;
}

/* 메인 배너 슬라이더 */
#muziyeppo-app-container .main-banner {
    height: 350px;
    background: #000000;
    position: relative;
    overflow: hidden;
}

#muziyeppo-app-container .banner-container {
    display: flex;
    transition: transform 0.5s ease-in-out;
    height: 100%;
}

#muziyeppo-app-container .banner-item {
    width: 100%;
    flex-shrink: 0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

#muziyeppo-app-container .banner-content {
    position: relative;
    text-align: center;
    z-index: 2;
    color: #ffffff;
}

#muziyeppo-app-container .banner-title {
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 16px;
    animation: fadeInUp 0.8s ease;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

#muziyeppo-app-container .banner-subtitle {
    font-size: 18px;
    color: #cccccc;
    margin-bottom: 24px;
}

#muziyeppo-app-container .banner-btn {
    background: #ffffff;
    color: #000000;
    border: none;
    padding: 12px 32px;
    border-radius: 25px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

#muziyeppo-app-container .banner-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

#muziyeppo-app-container .banner-indicators {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
}

#muziyeppo-app-container .indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

#muziyeppo-app-container .indicator.active {
    background: #ffffff;
    width: 24px;
    border-radius: 4px;
}

/* 카테고리 네비게이션 */
#muziyeppo-app-container .category-nav {
    background: #f8f8f8;
    padding: 20px 16px;
    position: sticky;
    top: 94px;
    z-index: 100;
    border-bottom: 1px solid #e0e0e0;
}

#muziyeppo-app-container .category-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 16px;
    max-width: 600px;
    margin: 0 auto;
}

#muziyeppo-app-container .category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

#muziyeppo-app-container .category-icon {
    width: 50px;
    height: 50px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    border: 2px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

#muziyeppo-app-container .category-item:hover .category-icon {
    background: #000000;
    transform: scale(1.1);
    border-color: #000000;
}

#muziyeppo-app-container .category-item:hover .category-icon svg {
    color: #ffffff;
}

#muziyeppo-app-container .category-icon svg {
    width: 24px;
    height: 24px;
    color: #333;
    transition: color 0.3s;
}

#muziyeppo-app-container .category-name {
    font-size: 12px;
    color: #666;
    transition: color 0.3s;
}

#muziyeppo-app-container .category-item:hover .category-name {
    color: #000000;
    font-weight: 500;
}

/* 서브 카테고리 */
#muziyeppo-app-container .sub-categories {
    background: #f0f0f0;
    padding: 12px 16px;
    display: none;
    border-bottom: 1px solid #e0e0e0;
}

#muziyeppo-app-container .sub-categories.show {
    display: block;
}

#muziyeppo-app-container .sub-category-list {
    display: flex;
    gap: 12px;
    overflow-x: auto;
    padding-bottom: 4px;
}

#muziyeppo-app-container .sub-category-list::-webkit-scrollbar {
    height: 0;
}

#muziyeppo-app-container .sub-category-btn {
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s;
    white-space: nowrap;
    border: 1px solid #ddd;
    cursor: pointer;
    background: #ffffff;
    color: #666;
}

#muziyeppo-app-container .sub-category-btn:hover {
    background: #f0f0f0;
    border-color: #999;
}

#muziyeppo-app-container .sub-category-btn.active {
    background: #000000;
    color: #ffffff;
    border-color: #000000;
}

/* 실시간 랭킹 섹션 */
#muziyeppo-app-container .ranking-section {
    padding: 32px 16px;
    background: #ffffff;
}

#muziyeppo-app-container .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
}

#muziyeppo-app-container .section-title {
    font-size: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 8px;
}

#muziyeppo-app-container .live-indicator {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(255, 0, 0, 0.1);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    color: #ff0000;
}

#muziyeppo-app-container .live-dot {
    width: 6px;
    height: 6px;
    background: #ff0000;
    border-radius: 50%;
    animation: blink 1s infinite;
}

@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}

#muziyeppo-app-container .view-all {
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s;
}

#muziyeppo-app-container .view-all:hover {
    color: #000000;
}

/* 상품 그리드 */
#muziyeppo-app-container .product-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

#muziyeppo-app-container .product-card {
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

#muziyeppo-app-container .product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #000000;
}

#muziyeppo-app-container .product-image-container {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    background: #f8f8f8;
}

#muziyeppo-app-container .product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

#muziyeppo-app-container .product-card:hover .product-image {
    transform: scale(1.05);
}

#muziyeppo-app-container .product-badges {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

#muziyeppo-app-container .badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

#muziyeppo-app-container .badge-sale {
    background: #000000;
    color: white;
}

#muziyeppo-app-container .badge-new {
    background: #4CAF50;
    color: white;
}

#muziyeppo-app-container .wishlist-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e0e0e0;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

#muziyeppo-app-container .wishlist-btn:hover {
    background: #000000;
    transform: scale(1.1);
}

#muziyeppo-app-container .wishlist-btn:hover svg {
    color: #ffffff;
}

#muziyeppo-app-container .wishlist-btn svg {
    width: 16px;
    height: 16px;
    color: #333;
}

#muziyeppo-app-container .wishlist-btn.active {
    background: #000000;
}

#muziyeppo-app-container .wishlist-btn.active svg {
    fill: #ffffff;
    color: #ffffff;
}

#muziyeppo-app-container .product-info {
    padding: 12px;
}

#muziyeppo-app-container .product-brand {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

#muziyeppo-app-container .product-name {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

#muziyeppo-app-container .product-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 8px;
}

#muziyeppo-app-container .discount-rate {
    color: #000000;
    font-weight: 700;
    font-size: 16px;
}

#muziyeppo-app-container .price {
    font-size: 16px;
    font-weight: 700;
}

#muziyeppo-app-container .original-price {
    font-size: 13px;
    color: #999;
    text-decoration: line-through;
}

#muziyeppo-app-container .product-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #666;
}

#muziyeppo-app-container .stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

#muziyeppo-app-container .rating {
    color: #ffd700;
}

/* 맨 위로 가기 버튼 */
#muziyeppo-app-container .scroll-to-top {
    position: fixed;
    bottom: 90px;
    right: 20px;
    width: 48px;
    height: 48px;
    background: #000000;
    color: #ffffff;
    border: none;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transition: all 0.3s;
    opacity: 0;
    visibility: hidden;
    z-index: 999;
}

#muziyeppo-app-container .scroll-to-top.show {
    opacity: 1;
    visibility: visible;
}

#muziyeppo-app-container .scroll-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

#muziyeppo-app-container .scroll-to-top svg {
    width: 24px;
    height: 24px;
}

/* 모달 */
#muziyeppo-app-container .modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
}

#muziyeppo-app-container .modal.show {
    display: flex;
}

#muziyeppo-app-container .modal-content {
    background: #ffffff;
    width: 90%;
    max-width: 500px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

#muziyeppo-app-container .modal-header {
    background: #000000;
    color: #ffffff;
    padding: 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

#muziyeppo-app-container .modal-close {
    background: none;
    border: none;
    color: #ffffff;
    cursor: pointer;
    padding: 4px;
}

#muziyeppo-app-container .modal-body {
    padding: 20px;
}

/* 하단 네비게이션 */
#muziyeppo-app-container .bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: #ffffff;
    border-top: 1px solid #e0e0e0;
    padding: 8px 0;
    z-index: 1000;
    box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08);
}

#muziyeppo-app-container .nav-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
}

#muziyeppo-app-container .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 8px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    background: none;
    color: #999;
}

#muziyeppo-app-container .nav-item:hover {
    color: #666;
}

#muziyeppo-app-container .nav-item.active {
    color: #000000;
}

#muziyeppo-app-container .nav-item svg {
    width: 20px;
    height: 20px;
}

#muziyeppo-app-container .nav-label {
    font-size: 11px;
    font-weight: 500;
}

/* 로딩 애니메이션 */
#muziyeppo-app-container .loading {
    display: flex;
    justify-content: center;
    padding: 32px;
}

#muziyeppo-app-container .spinner {
    width: 40px;
    height: 40px;
    border: 3px solid #f0f0f0;
    border-top-color: #000000;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* 토스트 메시지 */
#muziyeppo-app-container .toast {
    position: fixed;
    bottom: 100px;
    left: 50%;
    transform: translateX(-50%) translateY(100px);
    background: #000000;
    color: #ffffff;
    padding: 12px 24px;
    border-radius: 24px;
    font-size: 14px;
    opacity: 0;
    transition: all 0.3s;
    z-index: 3000;
}

#muziyeppo-app-container .toast.show {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
}

/* 빈 상태 */
#muziyeppo-app-container .empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px 0;
}

#muziyeppo-app-container .empty-icon {
    width: 64px;
    height: 64px;
    color: #ddd;
    margin-bottom: 16px;
}

#muziyeppo-app-container .empty-text {
    color: #666;
    margin-bottom: 16px;
}

#muziyeppo-app-container .primary-btn {
    background: #000000;
    color: #ffffff;
    padding: 12px 24px;
    border-radius: 24px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s;
}

#muziyeppo-app-container .primary-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

/* 프로필 카드 */
#muziyeppo-app-container .profile-card {
    background: #f8f8f8;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 24px;
    text-align: center;
}

#muziyeppo-app-container .profile-avatar {
    width: 80px;
    height: 80px;
    background: #000000;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
    font-size: 32px;
    color: #ffffff;
    overflow: hidden;
}

#muziyeppo-app-container .profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

#muziyeppo-app-container .profile-name {
    font-weight: 700;
    font-size: 18px;
    margin-bottom: 4px;
}

#muziyeppo-app-container .profile-email {
    color: #666;
    font-size: 14px;
}

/* 메뉴 리스트 */
#muziyeppo-app-container .menu-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

#muziyeppo-app-container .menu-btn {
    width: 100%;
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 16px;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    cursor: pointer;
    transition: all 0.3s;
}

#muziyeppo-app-container .menu-btn:hover {
    background: #f8f8f8;
    border-color: #000000;
}

#muziyeppo-app-container .menu-btn.logout {
    border-color: #ff0000;
    color: #ff0000;
}

#muziyeppo-app-container .menu-btn.logout:hover {
    background: #fff5f5;
}

/* 알림 아이템 */
#muziyeppo-app-container .notification-item {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: all 0.3s;
}

#muziyeppo-app-container .notification-item.unread {
    background: #f8f8f8;
    border-color: #000000;
}

#muziyeppo-app-container .notification-item:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

#muziyeppo-app-container .notification-title {
    font-weight: 600;
    margin-bottom: 4px;
}

#muziyeppo-app-container .notification-content {
    color: #666;
    font-size: 14px;
    margin-bottom: 4px;
}

#muziyeppo-app-container .notification-date {
    color: #999;
    font-size: 12px;
}

/* 검색 페이지 */
#muziyeppo-app-container .search-page {
    padding: 16px;
}

#muziyeppo-app-container .search-page-input {
    width: 100%;
    background: #ffffff;
    border: 2px solid #000000;
    border-radius: 24px;
    padding: 12px 48px 12px 16px;
    font-size: 16px;
    outline: none;
    margin-bottom: 24px;
}

#muziyeppo-app-container .search-page-input:focus {
    box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
}

#muziyeppo-app-container .popular-keywords {
    margin-bottom: 32px;
}

#muziyeppo-app-container .popular-keywords h3 {
    font-weight: 700;
    margin-bottom: 12px;
    color: #000000;
}

#muziyeppo-app-container .keyword-buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

#muziyeppo-app-container .keyword-btn {
    background: #ffffff;
    border: 1px solid #000000;
    border-radius: 20px;
    padding: 8px 16px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    font-weight: 500;
}

#muziyeppo-app-container .keyword-btn:hover {
    background: #000000;
    color: #ffffff;
}

/* 숨김 클래스 */
#muziyeppo-app-container .hidden {
    display: none !important;
}

/* 반응형 */
@media (min-width: 768px) {
    #muziyeppo-app-container .product-grid {
        grid-template-columns: repeat(3, 1fr);
    }
    
    #muziyeppo-app-container .category-grid {
        grid-template-columns: repeat(10, 1fr);
        max-width: none;
    }
}

@media (min-width: 1024px) {
    #muziyeppo-app-container .product-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
    }
    
    #muziyeppo-app-container .ranking-section {
        padding: 48px 32px;
    }
    
    #muziyeppo-app-container .main-banner {
        height: 450px;
    }
    
    #muziyeppo-app-container .banner-title {
        font-size: 48px;
    }
}
';
    
    // 플러그인 디렉토리의 assets 폴더에 파일 생성
    $plugin_dir = plugin_dir_path(__FILE__);
    $assets_dir = $plugin_dir . 'assets/';
    
    // assets 폴더가 없으면 생성
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    
    // CSS 파일을 플러그인의 assets 폴더에 생성
    $css_file = $assets_dir . 'app-style.css';
    
    // 파일이 이미 존재하지 않을 때만 생성
    if (!file_exists($css_file)) {
        file_put_contents($css_file, $css_content);
    }
}

// AJAX 핸들러 추가 (검색 기능)
add_action('wp_ajax_muziyeppo_search_products', 'muziyeppo_search_products_ajax');
add_action('wp_ajax_nopriv_muziyeppo_search_products', 'muziyeppo_search_products_ajax');
function muziyeppo_search_products_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'muziyeppo_ajax_nonce')) {
        wp_die('보안 검증 실패');
    }
    
    $keyword = sanitize_text_field($_POST['keyword']);
    
    $args = array(
        'post_type' => 'muziyeppo_product',
        'posts_per_page' => 20,
        's' => $keyword,
        'post_status' => 'publish'
    );
    
    $query = new WP_Query($args);
    
    ob_start();
    
    if ($query->have_posts()) {
        echo '<div class="product-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            muziyeppo_render_product_card_html();
        }
        echo '</div>';
    } else {
        echo '<div class="no-results">검색 결과가 없습니다</div>';
    }
    
    $html = ob_get_clean();
    wp_reset_postdata();
    
    wp_send_json_success(array('html' => $html));
}

// HTML 앱용 상품 카드 렌더링
function muziyeppo_render_product_card_html() {
    $product_id = get_the_ID();
    $brand = get_post_meta($product_id, '_muziyeppo_brand', true);
    $price = get_post_meta($product_id, '_muziyeppo_price', true);
    $original_price = get_post_meta($product_id, '_muziyeppo_original_price', true);
    $discount = get_post_meta($product_id, '_muziyeppo_discount', true);
    $rating = get_post_meta($product_id, '_muziyeppo_rating', true);
    $reviews = get_post_meta($product_id, '_muziyeppo_reviews', true);
    $likes = get_post_meta($product_id, '_muziyeppo_likes', true);
    $is_new = get_post_meta($product_id, '_muziyeppo_is_new', true);
    ?>
    <div class="product-card" onclick="openProduct(<?php echo $product_id; ?>)">
        <div class="product-image-container">
            <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium', array('class' => 'product-image')); ?>
            <?php else : ?>
                <img src="https://via.placeholder.com/300x300/f8f8f8/333333?text=No+Image" alt="<?php the_title(); ?>" class="product-image">
            <?php endif; ?>
            <div class="product-badges">
                <?php if ($discount) : ?>
                    <span class="badge badge-sale"><?php echo $discount; ?>% OFF</span>
                <?php endif; ?>
                <?php if ($is_new) : ?>
                    <span class="badge badge-new">NEW</span>
                <?php endif; ?>
            </div>
            <button class="wishlist-btn" onclick="event.stopPropagation(); toggleWishlist(<?php echo $product_id; ?>)">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </button>
        </div>
        <div class="product-info">
            <?php if ($brand) : ?>
                <p class="product-brand"><?php echo esc_html($brand); ?></p>
            <?php endif; ?>
            <h4 class="product-name"><?php the_title(); ?></h4>
            <div class="product-price">
                <?php if ($discount) : ?>
                    <span class="discount-rate"><?php echo $discount; ?>%</span>
                <?php endif; ?>
                <?php if ($price) : ?>
                    <span class="price"><?php echo number_format($price); ?>원</span>
                <?php endif; ?>
                <?php if ($original_price && $original_price > $price) : ?>
                    <span class="original-price"><?php echo number_format($original_price); ?>원</span>
                <?php endif; ?>
            </div>
            <div class="product-stats">
                <?php if ($rating) : ?>
                    <span class="stat-item">
                        <span class="rating">★</span> <?php echo $rating; ?>
                    </span>
                <?php endif; ?>
                <?php if ($reviews) : ?>
                    <span class="stat-item">리뷰 <?php echo number_format($reviews); ?></span>
                <?php endif; ?>
                <?php if ($likes) : ?>
                    <span class="stat-item">
                        ♥ <?php echo $likes > 1000 ? number_format($likes/1000, 1) . 'k' : number_format($likes); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * 무지예뽀 쇼핑몰 - CSV 상품 등록 기능
 * 
 * 이 코드를 기존 muziyeppo-shop.php 파일의 맨 아래에 추가하세요
 */

// ===== CSV 업로드 메뉴 추가 =====
add_action('admin_menu', 'muziyeppo_add_csv_import_menu');
function muziyeppo_add_csv_import_menu() {
    add_submenu_page(
        'muziyeppo-admin',
        'CSV 상품 등록',
        'CSV 상품 등록',
        'manage_options',
        'muziyeppo-csv-import',
        'muziyeppo_csv_import_page'
    );
}

// CSV 업로드 페이지
function muziyeppo_csv_import_page() {
    ?>
    <div class="wrap">
        <h1>CSV로 상품 대량 등록</h1>
        
        <!-- 업로드 폼 -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
            <h2>CSV 파일 업로드</h2>
            <form method="post" enctype="multipart/form-data" action="">
                <?php wp_nonce_field('muziyeppo_csv_import', 'muziyeppo_csv_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">CSV 파일 선택</th>
                        <td>
                            <input type="file" name="csv_file" accept=".csv" required />
                            <p class="description">UTF-8 인코딩된 CSV 파일만 지원됩니다.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">상품 카테고리</th>
                        <td>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'product_category',
                                'hide_empty' => false
                            ));
                            ?>
                            <select name="default_category">
                                <option value="">카테고리 선택 (선택사항)</option>
                                <?php foreach ($categories as $category) : ?>
                                    <option value="<?php echo $category->term_id; ?>">
                                        <?php echo esc_html($category->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">모든 상품에 적용될 기본 카테고리입니다.</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="submit_csv" class="button-primary" value="CSV 업로드 및 상품 등록" />
                </p>
            </form>
        </div>
        
        <!-- CSV 형식 안내 -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
            <h2>CSV 파일 형식</h2>
            <p>CSV 파일은 다음과 같은 형식이어야 합니다:</p>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>컬럼명</th>
                        <th>설명</th>
                        <th>필수여부</th>
                        <th>예시</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>title</strong></td>
                        <td>상품명</td>
                        <td>필수</td>
                        <td>무지예뽀 티셔츠</td>
                    </tr>
                    <tr>
                        <td><strong>description</strong></td>
                        <td>상품 설명</td>
                        <td>선택</td>
                        <td>편안한 착용감의 프리미엄 티셔츠</td>
                    </tr>
                    <tr>
                        <td><strong>brand</strong></td>
                        <td>브랜드명</td>
                        <td>선택</td>
                        <td>무지예뽀</td>
                    </tr>
                    <tr>
                        <td><strong>price</strong></td>
                        <td>판매가격</td>
                        <td>필수</td>
                        <td>29900</td>
                    </tr>
                    <tr>
                        <td><strong>original_price</strong></td>
                        <td>정가 (할인 전 가격)</td>
                        <td>선택</td>
                        <td>39900</td>
                    </tr>
                    <tr>
                        <td><strong>discount</strong></td>
                        <td>할인율 (%)</td>
                        <td>선택</td>
                        <td>25</td>
                    </tr>
                    <tr>
                        <td><strong>rating</strong></td>
                        <td>평점 (0~5)</td>
                        <td>선택</td>
                        <td>4.5</td>
                    </tr>
                    <tr>
                        <td><strong>reviews</strong></td>
                        <td>리뷰 수</td>
                        <td>선택</td>
                        <td>128</td>
                    </tr>
                    <tr>
                        <td><strong>likes</strong></td>
                        <td>좋아요 수</td>
                        <td>선택</td>
                        <td>1250</td>
                    </tr>
                    <tr>
                        <td><strong>is_new</strong></td>
                        <td>신상품 여부 (1 또는 0)</td>
                        <td>선택</td>
                        <td>1</td>
                    </tr>
                    <tr>
                        <td><strong>category</strong></td>
                        <td>카테고리명</td>
                        <td>선택</td>
                        <td>의류</td>
                    </tr>
                    <tr>
                        <td><strong>image_url</strong></td>
                        <td>상품 이미지 URL</td>
                        <td>선택</td>
                        <td>https://example.com/image.jpg</td>
                    </tr>
                </tbody>
            </table>
            
            <div style="margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 5px;">
                <h3>샘플 CSV 다운로드</h3>
                <p>아래 버튼을 클릭하여 샘플 CSV 파일을 다운로드할 수 있습니다.</p>
                <a href="<?php echo admin_url('admin-ajax.php?action=muziyeppo_download_sample_csv'); ?>" class="button">샘플 CSV 다운로드</a>
            </div>
        </div>
        
        <?php
        // CSV 업로드 처리
        if (isset($_POST['submit_csv']) && isset($_FILES['csv_file'])) {
            muziyeppo_process_csv_import();
        }
        ?>
    </div>
    <?php
}

// CSV 업로드 처리 함수
function muziyeppo_process_csv_import() {
    // 보안 검증
    if (!wp_verify_nonce($_POST['muziyeppo_csv_nonce'], 'muziyeppo_csv_import')) {
        echo '<div class="notice notice-error"><p>보안 검증에 실패했습니다.</p></div>';
        return;
    }
    
    $uploaded_file = $_FILES['csv_file'];
    
    // 파일 검증
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>파일 업로드에 실패했습니다.</p></div>';
        return;
    }
    
    $file_extension = pathinfo($uploaded_file['name'], PATHINFO_EXTENSION);
    if (strtolower($file_extension) !== 'csv') {
        echo '<div class="notice notice-error"><p>CSV 파일만 업로드 가능합니다.</p></div>';
        return;
    }
    
    // MIME 타입 검증 추가
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $uploaded_file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mime_types = array('text/csv', 'text/plain', 'application/csv', 'application/x-csv', 'text/x-csv', 'text/comma-separated-values', 'text/x-comma-separated-values');
    if (!in_array($mime_type, $allowed_mime_types)) {
        echo '<div class="notice notice-error"><p>유효한 CSV 파일이 아닙니다.</p></div>';
        return;
    }
    
    // CSV 파일 읽기
    $file_content = file_get_contents($uploaded_file['tmp_name']);
    
    // BOM 제거 (UTF-8 BOM이 있는 경우)
    $file_content = str_replace("\xEF\xBB\xBF", '', $file_content);
    
    // 임시 파일 생성
    $temp_file = tmpfile();
    fwrite($temp_file, $file_content);
    rewind($temp_file);
    
    $success_count = 0;
    $error_count = 0;
    $errors = array();
    $row_number = 0;
    
    // CSV 파싱
    $header = null;
    while (($data = fgetcsv($temp_file)) !== FALSE) {
        $row_number++;
        
        // 첫 번째 행은 헤더
        if ($header === null) {
            $header = array_map('trim', $data);
            continue;
        }
        
        // 데이터 행 처리
        $product_data = array_combine($header, $data);
        
        // 필수 필드 검증
        if (empty($product_data['title']) || empty($product_data['price'])) {
            $errors[] = "행 {$row_number}: 상품명과 가격은 필수입니다.";
            $error_count++;
            continue;
        }
        
        // 상품 등록
        $result = muziyeppo_create_product_from_csv($product_data, $_POST['default_category']);
        
        if ($result) {
            $success_count++;
        } else {
            $errors[] = "행 {$row_number}: 상품 등록에 실패했습니다.";
            $error_count++;
        }
    }
    
    fclose($temp_file);
    
    // 결과 표시
    echo '<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">';
    echo '<h2>CSV 업로드 결과</h2>';
    
    if ($success_count > 0) {
        echo '<div class="notice notice-success"><p>' . $success_count . '개의 상품이 성공적으로 등록되었습니다.</p></div>';
    }
    
    if ($error_count > 0) {
        echo '<div class="notice notice-error"><p>' . $error_count . '개의 상품 등록에 실패했습니다.</p></div>';
        
        if (!empty($errors)) {
            echo '<h3>오류 상세:</h3>';
            echo '<ul style="list-style: disc; margin-left: 20px;">';
            foreach ($errors as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul>';
        }
    }
    
    echo '<p><a href="' . admin_url('edit.php?post_type=muziyeppo_product') . '" class="button">상품 목록 보기</a></p>';
    echo '</div>';
}

// CSV 데이터로 상품 생성
function muziyeppo_create_product_from_csv($data, $default_category_id = null) {
    // 상품 포스트 생성
    $post_data = array(
        'post_title'    => sanitize_text_field($data['title']),
        'post_content'  => isset($data['description']) ? wp_kses_post($data['description']) : '',
        'post_status'   => 'publish',
        'post_type'     => 'muziyeppo_product',
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (is_wp_error($post_id)) {
        return false;
    }
    
    // 메타 데이터 저장
    if (isset($data['brand'])) {
        update_post_meta($post_id, '_muziyeppo_brand', sanitize_text_field($data['brand']));
    }
    
    update_post_meta($post_id, '_muziyeppo_price', intval($data['price']));
    
    if (isset($data['original_price'])) {
        update_post_meta($post_id, '_muziyeppo_original_price', intval($data['original_price']));
    }
    
    if (isset($data['discount'])) {
        update_post_meta($post_id, '_muziyeppo_discount', intval($data['discount']));
    }
    
    if (isset($data['rating'])) {
        update_post_meta($post_id, '_muziyeppo_rating', floatval($data['rating']));
    }
    
    if (isset($data['reviews'])) {
        update_post_meta($post_id, '_muziyeppo_reviews', intval($data['reviews']));
    }
    
    if (isset($data['likes'])) {
        update_post_meta($post_id, '_muziyeppo_likes', intval($data['likes']));
    }
    
    if (isset($data['is_new'])) {
        update_post_meta($post_id, '_muziyeppo_is_new', $data['is_new'] == '1' ? '1' : '0');
    }
    
    // 카테고리 설정
    if (isset($data['category']) && !empty($data['category'])) {
        $category_name = trim($data['category']);
        $category = get_term_by('name', $category_name, 'product_category');
        
        if (!$category) {
            // 카테고리가 없으면 생성
            $category_data = wp_insert_term($category_name, 'product_category');
            if (!is_wp_error($category_data)) {
                $category_id = $category_data['term_id'];
            } else {
                $category_id = $default_category_id;
            }
        } else {
            $category_id = $category->term_id;
        }
        
        if ($category_id) {
            wp_set_post_terms($post_id, array($category_id), 'product_category');
        }
    } elseif ($default_category_id) {
        wp_set_post_terms($post_id, array($default_category_id), 'product_category');
    }
    
    // 이미지 처리
    if (isset($data['image_url']) && !empty($data['image_url'])) {
        muziyeppo_set_product_image_from_url($post_id, $data['image_url']);
    }
    
    return $post_id;
}

// URL로부터 상품 이미지 설정
function muziyeppo_set_product_image_from_url($post_id, $image_url) {
    $image_url = trim($image_url);
    
    if (empty($image_url)) {
        return false;
    }
    
    // 이미지 다운로드
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
        return false;
    }
    
    // 파일 정보 설정
    $file_array = array(
        'name' => basename($image_url),
        'tmp_name' => $tmp
    );
    
    // 미디어 라이브러리에 추가
    $attachment_id = media_handle_sideload($file_array, $post_id);
    
    // 임시 파일 삭제
    @unlink($tmp);
    
    if (is_wp_error($attachment_id)) {
        return false;
    }
    
    // 대표 이미지로 설정
    set_post_thumbnail($post_id, $attachment_id);
    
    return true;
}

// 샘플 CSV 다운로드
add_action('wp_ajax_muziyeppo_download_sample_csv', 'muziyeppo_download_sample_csv');
function muziyeppo_download_sample_csv() {
    // 권한 검증 - 관리자만 다운로드 가능
    if (!current_user_can('manage_options')) {
        wp_die('권한이 없습니다');
    }
    // CSV 헤더
    $headers = array(
        'title',
        'description',
        'brand',
        'price',
        'original_price',
        'discount',
        'rating',
        'reviews',
        'likes',
        'is_new',
        'category',
        'image_url'
    );
    
    // 샘플 데이터
    $sample_data = array(
        array(
            '무지예뽀 프리미엄 티셔츠',
            '편안한 착용감과 우수한 통기성을 자랑하는 프리미엄 코튼 티셔츠입니다.',
            '무지예뽀',
            '29900',
            '39900',
            '25',
            '4.5',
            '128',
            '1250',
            '1',
            '의류',
            'https://example.com/tshirt1.jpg'
        ),
        array(
            '무지예뽀 데일리 백팩',
            '실용적인 수납공간과 세련된 디자인의 데일리 백팩',
            '무지예뽀',
            '59900',
            '79900',
            '25',
            '4.8',
            '256',
            '2100',
            '0',
            '가방',
            'https://example.com/backpack1.jpg'
        ),
        array(
            '무지예뽀 스니커즈',
            '가벼운 착용감과 쿠셔닝이 뛰어난 데일리 스니커즈',
            '무지예뽀',
            '89900',
            '119900',
            '25',
            '4.6',
            '89',
            '890',
            '1',
            '신발',
            'https://example.com/sneakers1.jpg'
        )
    );
    
    // CSV 파일 생성 및 다운로드
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="muziyeppo_sample_products.csv"');
    
    // BOM 추가 (Excel에서 UTF-8 인식을 위해)
    echo "\xEF\xBB\xBF";
    
    $output = fopen('php://output', 'w');
    
    // 헤더 쓰기
    fputcsv($output, $headers);
    
    // 샘플 데이터 쓰기
    foreach ($sample_data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// CSV 업로드 페이지 스타일 추가
add_action('admin_head', 'muziyeppo_csv_import_styles');
function muziyeppo_csv_import_styles() {
    $screen = get_current_screen();
    if ($screen->id !== 'muziyeppo_page_muziyeppo-csv-import') {
        return;
    }
    ?>
    <style>
        .muziyeppo-csv-import-wrap {
            max-width: 1200px;
        }
        
        .muziyeppo-csv-import-wrap h1 {
            color: #000;
            font-weight: 700;
        }
        
        .muziyeppo-csv-import-wrap .form-table th {
            width: 200px;
        }
        
        .muziyeppo-csv-import-wrap .wp-list-table th {
            font-weight: 600;
            background: #f8f8f8;
        }
        
        .muziyeppo-csv-import-wrap .notice {
            margin: 10px 0;
        }
        
        .muziyeppo-csv-import-wrap code {
            background: #f0f0f0;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
    <?php
}

// 대량 등록 시 메모리 제한 증가
add_action('admin_init', 'muziyeppo_increase_memory_limit');
function muziyeppo_increase_memory_limit() {
    if (isset($_POST['submit_csv'])) {
        @ini_set('memory_limit', '256M');
        @ini_set('max_execution_time', '300');
    }
}

// 사용자가 상품을 좋아하는지 확인하는 함수
function muziyeppo_is_product_liked($product_id, $user_id) {
    $user_likes = get_option('muziyeppo_user_likes', array());
    
    if (!isset($user_likes[$user_id])) {
        return false;
    }
    
    return in_array($product_id, $user_likes[$user_id]);
}

// 카테고리별 아이콘 반환 함수
function muziyeppo_get_category_icon($slug) {
    $icons = array(
        'clothing' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>',
        'bags' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
        'shoes' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
        'beauty' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>',
        'jewelry' => '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>'
    );
    
    // 기본 아이콘
    $default = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>';
    
    return isset($icons[$slug]) ? $icons[$slug] : $default;
}

