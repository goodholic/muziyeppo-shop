<?php
/**
 * 무지예뽀 쇼핑몰 - 상품 상세 페이지 템플릿
 * 
 * @package MuziyeppoShop
 */

get_header(); ?>

<?php while (have_posts()) : the_post(); 
    $product_id = get_the_ID();
    $brand = get_post_meta($product_id, '_muziyeppo_brand', true);
    $price = get_post_meta($product_id, '_muziyeppo_price', true);
    $original_price = get_post_meta($product_id, '_muziyeppo_original_price', true);
    $discount = get_post_meta($product_id, '_muziyeppo_discount', true);
    $rating = get_post_meta($product_id, '_muziyeppo_rating', true);
    $reviews = get_post_meta($product_id, '_muziyeppo_reviews', true);
    $likes = get_post_meta($product_id, '_muziyeppo_likes', true);
    $is_new = get_post_meta($product_id, '_muziyeppo_is_new', true);
    
    // 찜하기 상태 확인
    $user_id = get_current_user_id() ?: sanitize_text_field($_SERVER['REMOTE_ADDR']);
    $is_liked = muziyeppo_is_product_liked($product_id, $user_id);
?>

<div class="muziyeppo-single-product">
    <!-- 빵부스러기 네비게이션 -->
    <nav class="breadcrumb">
        <a href="<?php echo home_url(); ?>">홈</a>
        <span>/</span>
        <a href="<?php echo get_post_type_archive_link('muziyeppo_product'); ?>">상품</a>
        <?php
        $terms = get_the_terms($product_id, 'product_category');
        if ($terms && !is_wp_error($terms)) :
            $term = $terms[0];
        ?>
            <span>/</span>
            <a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
        <?php endif; ?>
        <span>/</span>
        <span><?php the_title(); ?></span>
    </nav>

    <div class="product-detail-container">
        <!-- 상품 갤러리 -->
        <div class="product-gallery">
            <div class="main-image-container">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('large', array('class' => 'main-product-image')); ?>
                <?php else : ?>
                    <img src="<?php echo plugins_url('assets/no-image.jpg', dirname(__FILE__)); ?>" alt="<?php the_title(); ?>" class="main-product-image">
                <?php endif; ?>
                
                <div class="product-badges">
                    <?php if ($discount) : ?>
                        <span class="badge badge-sale"><?php echo esc_html($discount); ?>% OFF</span>
                    <?php endif; ?>
                    <?php if ($is_new) : ?>
                        <span class="badge badge-new">NEW</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 추가 이미지 갤러리 (있는 경우) -->
            <?php
            $gallery_ids = get_post_meta($product_id, '_product_image_gallery', true);
            if ($gallery_ids) :
                $gallery_ids = explode(',', $gallery_ids);
            ?>
                <div class="thumbnail-gallery">
                    <?php foreach ($gallery_ids as $attachment_id) : ?>
                        <div class="thumbnail-item">
                            <?php echo wp_get_attachment_image($attachment_id, 'thumbnail'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 상품 정보 -->
        <div class="product-info-section">
            <?php if ($brand) : ?>
                <p class="product-brand-large"><?php echo esc_html($brand); ?></p>
            <?php endif; ?>
            
            <h1 class="product-title"><?php the_title(); ?></h1>
            
            <!-- 평점 및 리뷰 -->
            <div class="product-rating-info">
                <?php if ($rating) : ?>
                    <div class="rating-stars">
                        <span class="stars">★★★★★</span>
                        <span class="rating-value"><?php echo esc_html($rating); ?></span>
                    </div>
                <?php endif; ?>
                <?php if ($reviews) : ?>
                    <span class="review-count">(리뷰 <?php echo number_format($reviews); ?>개)</span>
                <?php endif; ?>
                <?php if ($likes) : ?>
                    <span class="likes-count">♥ <?php echo number_format($likes); ?></span>
                <?php endif; ?>
            </div>
            
            <!-- 가격 정보 -->
            <div class="price-section">
                <?php if ($discount) : ?>
                    <div class="discount-info">
                        <span class="discount-rate-large"><?php echo esc_html($discount); ?>%</span>
                        <span class="discount-label">할인</span>
                    </div>
                <?php endif; ?>
                
                <div class="price-info">
                    <?php if ($original_price && $original_price > $price) : ?>
                        <p class="original-price-large"><?php echo number_format($original_price); ?>원</p>
                    <?php endif; ?>
                    <?php if ($price) : ?>
                        <p class="final-price-large"><?php echo number_format($price); ?>원</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 배송 정보 -->
            <div class="shipping-info">
                <div class="shipping-item">
                    <span class="label">배송비</span>
                    <span class="value">무료배송</span>
                </div>
                <div class="shipping-item">
                    <span class="label">배송예정</span>
                    <span class="value">내일(토) 도착 예정</span>
                </div>
            </div>
            
            <!-- 옵션 선택 (예시) -->
            <div class="product-options">
                <div class="option-group">
                    <label class="option-label">색상</label>
                    <div class="option-buttons">
                        <button class="option-btn active">블랙</button>
                        <button class="option-btn">화이트</button>
                        <button class="option-btn">네이비</button>
                    </div>
                </div>
                
                <div class="option-group">
                    <label class="option-label">사이즈</label>
                    <div class="option-buttons">
                        <button class="option-btn">S</button>
                        <button class="option-btn active">M</button>
                        <button class="option-btn">L</button>
                        <button class="option-btn">XL</button>
                    </div>
                </div>
            </div>
            
            <!-- 수량 선택 -->
            <div class="quantity-section">
                <label class="quantity-label">수량</label>
                <div class="quantity-selector">
                    <button class="quantity-btn minus" onclick="changeQuantity(-1)">-</button>
                    <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="99">
                    <button class="quantity-btn plus" onclick="changeQuantity(1)">+</button>
                </div>
            </div>
            
            <!-- 총 금액 -->
            <div class="total-price-section">
                <span class="total-label">총 상품금액</span>
                <span class="total-price" id="totalPrice"><?php echo number_format($price); ?>원</span>
            </div>
            
            <!-- 액션 버튼 -->
            <div class="product-actions">
                <button class="action-btn btn-cart" onclick="addToCart(<?php echo $product_id; ?>)">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                    장바구니
                </button>
                <button class="action-btn btn-buy">바로구매</button>
                <button class="action-btn btn-wishlist <?php echo $is_liked ? 'active' : ''; ?>" 
                        onclick="toggleWishlist(<?php echo $product_id; ?>)">
                    <svg width="20" height="20" fill="<?php echo $is_liked ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- 상품 상세 정보 탭 -->
    <div class="product-detail-tabs">
        <div class="tab-buttons">
            <button class="tab-btn active" onclick="showTab('description')">상품정보</button>
            <button class="tab-btn" onclick="showTab('reviews')">리뷰 (<?php echo $reviews ?: 0; ?>)</button>
            <button class="tab-btn" onclick="showTab('qna')">Q&A</button>
            <button class="tab-btn" onclick="showTab('shipping')">배송/교환/반품</button>
        </div>
        
        <div class="tab-content">
            <!-- 상품정보 탭 -->
            <div id="tab-description" class="tab-pane active">
                <div class="product-description">
                    <?php the_content(); ?>
                </div>
            </div>
            
            <!-- 리뷰 탭 -->
            <div id="tab-reviews" class="tab-pane">
                <div class="reviews-section">
                    <div class="reviews-summary">
                        <div class="average-rating">
                            <span class="rating-number"><?php echo esc_html($rating ?: '0.0'); ?></span>
                            <div class="star-rating">★★★★★</div>
                            <p class="review-count-text"><?php echo number_format($reviews ?: 0); ?>개의 리뷰</p>
                        </div>
                    </div>
                    
                    <div class="review-list">
                        <!-- 리뷰 아이템 예시 -->
                        <div class="review-item">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <span class="reviewer-name">김**</span>
                                    <span class="review-date">2024.01.15</span>
                                </div>
                                <div class="review-rating">★★★★★</div>
                            </div>
                            <p class="review-content">정말 만족스러운 상품입니다. 품질도 좋고 배송도 빨라요!</p>
                        </div>
                    </div>
                    
                    <button class="load-more-btn">리뷰 더보기</button>
                </div>
            </div>
            
            <!-- Q&A 탭 -->
            <div id="tab-qna" class="tab-pane">
                <div class="qna-section">
                    <button class="write-qna-btn">상품 문의하기</button>
                    <div class="qna-list">
                        <p class="empty-message">등록된 문의가 없습니다.</p>
                    </div>
                </div>
            </div>
            
            <!-- 배송/교환/반품 탭 -->
            <div id="tab-shipping" class="tab-pane">
                <div class="shipping-policy">
                    <h3>배송 안내</h3>
                    <ul>
                        <li>배송비 : 무료배송</li>
                        <li>배송기간 : 결제완료 후 1~3일 이내</li>
                        <li>배송업체 : CJ대한통운</li>
                    </ul>
                    
                    <h3>교환/반품 안내</h3>
                    <ul>
                        <li>교환/반품 기간 : 상품 수령 후 7일 이내</li>
                        <li>교환/반품 비용 : 단순변심시 왕복 배송비 구매자 부담</li>
                        <li>교환/반품이 불가능한 경우
                            <ul>
                                <li>상품 훼손 또는 구성품 분실</li>
                                <li>상품 택 제거 또는 세탁</li>
                                <li>착용 흔적이 있는 경우</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- 관련 상품 -->
    <div class="related-products">
        <h2>함께 보면 좋은 상품</h2>
        <div class="related-products-grid">
            <?php
            $related_args = array(
                'post_type' => 'muziyeppo_product',
                'posts_per_page' => 4,
                'post__not_in' => array($product_id),
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_category',
                        'field' => 'term_id',
                        'terms' => wp_get_post_terms($product_id, 'product_category', array('fields' => 'ids')),
                    ),
                ),
            );
            
            $related_products = new WP_Query($related_args);
            
            if ($related_products->have_posts()) :
                while ($related_products->have_posts()) : $related_products->the_post();
                    $rel_price = get_post_meta(get_the_ID(), '_muziyeppo_price', true);
                    $rel_discount = get_post_meta(get_the_ID(), '_muziyeppo_discount', true);
                    ?>
                    <div class="related-product-card">
                        <a href="<?php the_permalink(); ?>">
                            <div class="related-product-image">
                                <?php if (has_post_thumbnail()) : ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php else : ?>
                                    <img src="<?php echo plugins_url('assets/no-image.jpg', dirname(__FILE__)); ?>" alt="<?php the_title(); ?>">
                                <?php endif; ?>
                                
                                <?php if ($rel_discount) : ?>
                                    <span class="badge badge-sale"><?php echo $rel_discount; ?>% OFF</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="related-product-info">
                                <h4 class="related-product-name"><?php the_title(); ?></h4>
                                <?php if ($rel_price) : ?>
                                    <p class="related-product-price"><?php echo number_format($rel_price); ?>원</p>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php
                endwhile;
                wp_reset_postdata();
            endif;
            ?>
        </div>
    </div>
</div>

<style>
/* 상품 상세 페이지 스타일 */
.muziyeppo-single-product {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

/* 빵부스러기 네비게이션 */
.breadcrumb {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 30px;
    font-size: 14px;
    color: #666;
}

.breadcrumb a {
    color: #666;
    text-decoration: none;
    transition: color 0.3s;
}

.breadcrumb a:hover {
    color: #000;
}

/* 상품 상세 컨테이너 */
.product-detail-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-bottom: 80px;
}

/* 상품 갤러리 */
.product-gallery {
    position: sticky;
    top: 20px;
    align-self: start;
}

.main-image-container {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 12px;
    background: #f8f8f8;
    margin-bottom: 16px;
}

.main-product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumbnail-gallery {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.thumbnail-item {
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 8px;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.thumbnail-item:hover {
    border-color: #000;
}

.thumbnail-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 상품 정보 섹션 */
.product-info-section {
    padding: 20px 0;
}

.product-brand-large {
    font-size: 16px;
    color: #666;
    margin-bottom: 12px;
}

.product-title {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 20px;
    line-height: 1.3;
}

/* 평점 정보 */
.product-rating-info {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.rating-stars {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stars {
    color: #ffd700;
    font-size: 18px;
}

.rating-value {
    font-weight: 600;
    font-size: 18px;
}

.review-count, .likes-count {
    color: #666;
    font-size: 16px;
}

/* 가격 섹션 */
.price-section {
    background: #f8f8f8;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.discount-info {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 12px;
}

.discount-rate-large {
    font-size: 36px;
    font-weight: 900;
    color: #ff0000;
}

.discount-label {
    font-size: 16px;
    color: #666;
}

.price-info {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.original-price-large {
    font-size: 18px;
    color: #999;
    text-decoration: line-through;
}

.final-price-large {
    font-size: 32px;
    font-weight: 700;
    color: #000;
}

/* 배송 정보 */
.shipping-info {
    background: #ffffff;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 30px;
}

.shipping-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.shipping-item:not(:last-child) {
    border-bottom: 1px solid #f0f0f0;
}

.shipping-item .label {
    color: #666;
    font-size: 14px;
}

.shipping-item .value {
    font-weight: 500;
}

/* 옵션 선택 */
.product-options {
    margin-bottom: 30px;
}

.option-group {
    margin-bottom: 20px;
}

.option-label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
}

.option-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.option-btn {
    padding: 10px 20px;
    border: 2px solid #e0e0e0;
    background: #fff;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
}

.option-btn:hover {
    border-color: #666;
}

.option-btn.active {
    background: #000;
    color: #fff;
    border-color: #000;
}

/* 수량 선택 */
.quantity-section {
    margin-bottom: 30px;
}

.quantity-label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    width: fit-content;
}

.quantity-btn {
    width: 40px;
    height: 40px;
    border: none;
    background: none;
    cursor: pointer;
    font-size: 18px;
    transition: all 0.3s;
}

.quantity-btn:hover {
    background: #f8f8f8;
}

.quantity-input {
    width: 60px;
    text-align: center;
    border: none;
    font-size: 16px;
    font-weight: 500;
}

.quantity-input::-webkit-inner-spin-button,
.quantity-input::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

/* 총 금액 */
.total-price-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 0;
    margin-bottom: 30px;
    border-top: 2px solid #000;
}

.total-label {
    font-size: 18px;
    font-weight: 600;
}

.total-price {
    font-size: 28px;
    font-weight: 700;
    color: #000;
}

/* 액션 버튼 */
.product-actions {
    display: grid;
    grid-template-columns: 1fr 2fr 60px;
    gap: 12px;
}

.action-btn {
    padding: 16px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-cart {
    background: #fff;
    border: 2px solid #000;
    color: #000;
}

.btn-cart:hover {
    background: #f8f8f8;
}

.btn-buy {
    background: #000;
    color: #fff;
}

.btn-buy:hover {
    background: #333;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.btn-wishlist {
    background: #fff;
    border: 2px solid #e0e0e0;
}

.btn-wishlist:hover {
    border-color: #000;
}

.btn-wishlist.active {
    background: #000;
    border-color: #000;
}

.btn-wishlist.active svg {
    color: #fff;
}

/* 탭 */
.product-detail-tabs {
    margin-top: 80px;
}

.tab-buttons {
    display: flex;
    border-bottom: 2px solid #e0e0e0;
}

.tab-btn {
    padding: 16px 24px;
    background: none;
    border: none;
    font-size: 16px;
    font-weight: 500;
    color: #666;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
}

.tab-btn:hover {
    color: #000;
}

.tab-btn.active {
    color: #000;
    font-weight: 600;
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 2px;
    background: #000;
}

.tab-content {
    padding: 40px 0;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

/* 상품 설명 */
.product-description {
    line-height: 1.8;
    font-size: 16px;
}

.product-description img {
    max-width: 100%;
    height: auto;
    margin: 20px 0;
}

/* 리뷰 섹션 */
.reviews-summary {
    background: #f8f8f8;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 40px;
    text-align: center;
}

.average-rating {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.rating-number {
    font-size: 48px;
    font-weight: 700;
}

.star-rating {
    font-size: 24px;
    color: #ffd700;
}

.review-count-text {
    color: #666;
}

.review-item {
    padding: 20px 0;
    border-bottom: 1px solid #e0e0e0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.reviewer-info {
    display: flex;
    gap: 12px;
}

.reviewer-name {
    font-weight: 600;
}

.review-date {
    color: #666;
    font-size: 14px;
}

.review-rating {
    color: #ffd700;
}

.review-content {
    line-height: 1.6;
}

.load-more-btn {
    width: 100%;
    padding: 12px;
    background: #fff;
    border: 2px solid #000;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 20px;
}

.load-more-btn:hover {
    background: #000;
    color: #fff;
}

/* Q&A 섹션 */
.write-qna-btn {
    padding: 12px 24px;
    background: #000;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 20px;
}

.write-qna-btn:hover {
    background: #333;
}

.empty-message {
    text-align: center;
    color: #666;
    padding: 40px 0;
}

/* 배송 정책 */
.shipping-policy h3 {
    font-size: 18px;
    font-weight: 600;
    margin: 30px 0 16px;
}

.shipping-policy h3:first-child {
    margin-top: 0;
}

.shipping-policy ul {
    list-style: none;
    padding: 0;
}

.shipping-policy li {
    padding: 8px 0;
    padding-left: 20px;
    position: relative;
}

.shipping-policy li::before {
    content: '•';
    position: absolute;
    left: 0;
}

.shipping-policy ul ul {
    margin-top: 8px;
}

/* 관련 상품 */
.related-products {
    margin-top: 100px;
    padding-top: 40px;
    border-top: 1px solid #e0e0e0;
}

.related-products h2 {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 30px;
    text-align: center;
}

.related-products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 20px;
}

.related-product-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    border: 1px solid #f0f0f0;
}

.related-product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #000;
}

.related-product-card a {
    text-decoration: none;
    color: inherit;
}

.related-product-image {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.related-product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.related-product-card:hover .related-product-image img {
    transform: scale(1.05);
}

.related-product-info {
    padding: 16px;
}

.related-product-name {
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.related-product-price {
    font-size: 16px;
    font-weight: 700;
}

/* 배지 공통 스타일 */
.product-badges {
    position: absolute;
    top: 12px;
    left: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
    z-index: 1;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-sale {
    background: #000;
    color: #fff;
}

.badge-new {
    background: #4CAF50;
    color: #fff;
}

/* 반응형 */
@media (max-width: 768px) {
    .product-detail-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .product-gallery {
        position: static;
    }
    
    .product-actions {
        grid-template-columns: 60px 1fr 1fr;
    }
    
    .product-title {
        font-size: 24px;
    }
    
    .related-products-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .tab-buttons {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tab-btn {
        white-space: nowrap;
    }
}
</style>

<script>
// 전역 변수
const productId = <?php echo $product_id; ?>;
const productPrice = <?php echo $price ?: 0; ?>;

// 수량 변경
function changeQuantity(change) {
    const quantityInput = document.getElementById('quantity');
    let currentValue = parseInt(quantityInput.value) || 1;
    let newValue = currentValue + change;
    
    if (newValue < 1) newValue = 1;
    if (newValue > 99) newValue = 99;
    
    quantityInput.value = newValue;
    updateTotalPrice();
}

// 총 금액 업데이트
function updateTotalPrice() {
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    const totalPrice = productPrice * quantity;
    document.getElementById('totalPrice').textContent = totalPrice.toLocaleString() + '원';
}

// 탭 표시
function showTab(tabName) {
    // 모든 탭 버튼과 콘텐츠 숨기기
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // 선택된 탭 표시
    event.target.classList.add('active');
    document.getElementById('tab-' + tabName).classList.add('active');
}

// 장바구니 추가
function addToCart(productId) {
    // muziyeppo_ajax 존재 여부 확인
    if (typeof muziyeppo_ajax === 'undefined') {
        console.warn('muziyeppo_ajax가 정의되지 않았습니다. AJAX 기능이 제한될 수 있습니다.');
        // 기본값 설정
        window.muziyeppo_ajax = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: ''
        };
    }
    
    const quantity = parseInt(document.getElementById('quantity').value) || 1;
    
    fetch(muziyeppo_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'muziyeppo_add_to_cart',
            product_id: productId,
            quantity: quantity,
            nonce: muziyeppo_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('장바구니에 추가되었습니다');
        } else {
            showToast('오류가 발생했습니다');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('오류가 발생했습니다');
    });
}

// 찜하기 토글
function toggleWishlist(productId) {
    const btn = document.querySelector('.btn-wishlist');
    
    fetch(muziyeppo_ajax.ajax_url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'muziyeppo_toggle_like',
            product_id: productId,
            nonce: muziyeppo_ajax.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            btn.classList.toggle('active', data.data.liked);
            const svg = btn.querySelector('svg');
            svg.setAttribute('fill', data.data.liked ? 'currentColor' : 'none');
            
            showToast(data.data.liked ? '찜 목록에 추가되었습니다' : '찜 목록에서 제거되었습니다');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('오류가 발생했습니다');
    });
}

// 토스트 메시지
function showToast(message) {
    const toast = document.createElement('div');
    toast.className = 'muziyeppo-toast';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    // muziyeppo_ajax 존재 여부 확인
    if (typeof muziyeppo_ajax === 'undefined') {
        console.warn('muziyeppo_ajax가 정의되지 않았습니다. AJAX 기능이 제한될 수 있습니다.');
        // 기본값 설정
        window.muziyeppo_ajax = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: ''
        };
    }
    
    // 수량 입력 이벤트
    document.getElementById('quantity').addEventListener('input', updateTotalPrice);
});

// 토스트 스타일
const toastStyle = document.createElement('style');
toastStyle.textContent = `
    .muziyeppo-toast {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%) translateY(100px);
        background: #000000;
        color: #ffffff;
        padding: 12px 24px;
        border-radius: 24px;
        font-size: 14px;
        opacity: 0;
        transition: all 0.3s;
        z-index: 9999;
    }
    
    .muziyeppo-toast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
`;
document.head.appendChild(toastStyle);
</script>

<?php endwhile; ?>

<?php get_footer(); ?>