<?php
/**
 * 무지예뽀 쇼핑몰 - 상품 목록 페이지 템플릿
 * 
 * @package MuziyeppoShop
 */

get_header(); ?>

<div class="muziyeppo-shop-wrapper">
    <!-- 상단 배너 -->
    <div class="top-banner">
        🎁 신규 회원 가입시 10,000원 쿠폰 즉시 지급!
    </div>

    <!-- 메인 배너 슬라이더 -->
    <div class="main-banner">
        <div class="banner-container" id="bannerContainer">
            <div class="banner-item">
                <div class="banner-content">
                    <h2 class="banner-title">BLACK WEEK</h2>
                    <p class="banner-subtitle">프리미엄 브랜드 최대 80% 할인</p>
                    <button class="banner-btn">지금 쇼핑하기</button>
                </div>
            </div>
            <div class="banner-item">
                <div class="banner-content">
                    <h2 class="banner-title">신규 회원 혜택</h2>
                    <p class="banner-subtitle">첫 구매시 20% 추가 할인</p>
                    <button class="banner-btn">회원가입 하기</button>
                </div>
            </div>
            <div class="banner-item">
                <div class="banner-content">
                    <h2 class="banner-title">베스트 상품</h2>
                    <p class="banner-subtitle">이번 주 가장 인기있는 상품</p>
                    <button class="banner-btn">베스트 보기</button>
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
            <?php
            $product_categories = get_terms(array(
                'taxonomy' => 'product_category',
                'hide_empty' => false,
                'parent' => 0
            ));
            
            foreach ($product_categories as $category) : ?>
                <div class="category-item" onclick="filterByCategory('<?php echo esc_attr($category->slug); ?>')">
                    <div class="category-icon">
                        <?php echo muziyeppo_get_category_icon($category->slug); ?>
                    </div>
                    <span class="category-name"><?php echo esc_html($category->name); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </nav>

    <!-- 현재 카테고리 표시 -->
    <?php if (is_tax('product_category')) : ?>
        <div class="current-category-header">
            <h1 class="category-title"><?php single_term_title(); ?></h1>
            <p class="category-description"><?php echo wp_kses_post(term_description()); ?></p>
        </div>
    <?php else : ?>
        <div class="current-category-header">
            <h1 class="category-title">전체 상품</h1>
        </div>
    <?php endif; ?>

    <!-- 정렬 옵션 -->
    <div class="sort-options">
        <select id="sortSelect" onchange="sortProducts(this.value)">
            <option value="date">최신순</option>
            <option value="price_low">낮은 가격순</option>
            <option value="price_high">높은 가격순</option>
            <option value="discount">할인율순</option>
            <option value="popular">인기순</option>
        </select>
    </div>

    <!-- 상품 그리드 -->
    <div class="product-grid" id="productGrid">
        <?php if (have_posts()) : ?>
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
                <div class="product-card" data-product-id="<?php echo $product_id; ?>">
                    <a href="<?php the_permalink(); ?>" class="product-link">
                        <div class="product-image-container">
                            <?php if (has_post_thumbnail()) : ?>
                                <?php the_post_thumbnail('medium', array('class' => 'product-image')); ?>
                            <?php else : ?>
                                <img src="<?php echo plugins_url('assets/no-image.jpg', dirname(__FILE__)); ?>" alt="<?php the_title(); ?>" class="product-image">
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
                        
                        <div class="product-info">
                            <?php if ($brand) : ?>
                                <p class="product-brand"><?php echo esc_html($brand); ?></p>
                            <?php endif; ?>
                            
                            <h4 class="product-name"><?php the_title(); ?></h4>
                            
                            <div class="product-price">
                                <?php if ($discount) : ?>
                                    <span class="discount-rate"><?php echo esc_html($discount); ?>%</span>
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
                                        <span class="rating">★</span> <?php echo esc_html($rating); ?>
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
                    </a>
                    
                    <button class="wishlist-btn <?php echo $is_liked ? 'active' : ''; ?>" 
                            data-product-id="<?php echo $product_id; ?>"
                            onclick="toggleWishlist(<?php echo $product_id; ?>)">
                        <svg fill="<?php echo $is_liked ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <div class="no-products">
                <p>현재 등록된 상품이 없습니다.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <div class="muziyeppo-pagination">
        <?php
        echo paginate_links(array(
            'prev_text' => '이전',
            'next_text' => '다음',
            'type' => 'list'
        ));
        ?>
    </div>

    <!-- 로딩 인디케이터 -->
    <div class="loading hidden" id="loading">
        <div class="spinner"></div>
    </div>
</div>

<!-- 맨 위로 가기 버튼 -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
    </svg>
</button>

<style>
/* 메인 스타일 */
.muziyeppo-shop-wrapper {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    color: #000000;
    background: #ffffff;
}

/* 상단 배너 */
.top-banner {
    background: #000000;
    color: white;
    text-align: center;
    padding: 8px;
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.5px;
}

/* 메인 배너 */
.main-banner {
    height: 350px;
    background: #000000;
    position: relative;
    overflow: hidden;
    margin-bottom: 40px;
}

.banner-container {
    display: flex;
    transition: transform 0.5s ease-in-out;
    height: 100%;
}

.banner-item {
    width: 100%;
    flex-shrink: 0;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
}

.banner-content {
    position: relative;
    text-align: center;
    z-index: 2;
    color: #ffffff;
}

.banner-title {
    font-size: 36px;
    font-weight: 900;
    margin-bottom: 16px;
    animation: fadeInUp 0.8s ease;
}

.banner-subtitle {
    font-size: 18px;
    color: #cccccc;
    margin-bottom: 24px;
}

.banner-btn {
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

.banner-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.banner-indicators {
    position: absolute;
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 8px;
}

.indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: all 0.3s;
    border: none;
}

.indicator.active {
    background: #ffffff;
    width: 24px;
    border-radius: 4px;
}

/* 카테고리 네비게이션 */
.category-nav {
    background: #f8f8f8;
    padding: 20px 16px;
    margin-bottom: 40px;
    border-radius: 12px;
}

.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 16px;
    max-width: 1200px;
    margin: 0 auto;
}

.category-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.category-icon {
    width: 60px;
    height: 60px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    border: 2px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.category-item:hover .category-icon {
    background: #000000;
    transform: scale(1.1);
    border-color: #000000;
}

.category-item:hover .category-icon svg {
    color: #ffffff;
}

.category-icon svg {
    width: 24px;
    height: 24px;
    color: #333;
    transition: color 0.3s;
}

.category-name {
    font-size: 14px;
    color: #666;
    transition: color 0.3s;
    font-weight: 500;
}

.category-item:hover .category-name {
    color: #000000;
    font-weight: 600;
}

/* 현재 카테고리 헤더 */
.current-category-header {
    text-align: center;
    margin-bottom: 40px;
    padding: 0 20px;
}

.category-title {
    font-size: 32px;
    font-weight: 900;
    color: #000000;
    margin-bottom: 12px;
}

.category-description {
    color: #666;
    font-size: 16px;
}

/* 정렬 옵션 */
.sort-options {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 24px;
    padding: 0 20px;
}

#sortSelect {
    background: #ffffff;
    border: 2px solid #000000;
    border-radius: 24px;
    padding: 8px 20px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.3s;
}

#sortSelect:hover {
    background: #000000;
    color: #ffffff;
}

/* 상품 그리드 */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 24px;
    padding: 0 20px;
    margin-bottom: 60px;
}

.product-card {
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.3s;
    cursor: pointer;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    position: relative;
}

.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border-color: #000000;
}

.product-link {
    text-decoration: none;
    color: inherit;
}

.product-image-container {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    background: #f8f8f8;
}

.product-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-image {
    transform: scale(1.05);
}

.product-badges {
    position: absolute;
    top: 8px;
    left: 8px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 600;
}

.badge-sale {
    background: #000000;
    color: white;
}

.badge-new {
    background: #4CAF50;
    color: white;
}

.wishlist-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255, 255, 255, 0.9);
    border: 1px solid #e0e0e0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
}

.wishlist-btn:hover {
    background: #000000;
    transform: scale(1.1);
}

.wishlist-btn:hover svg {
    color: #ffffff;
}

.wishlist-btn svg {
    width: 18px;
    height: 18px;
    color: #333;
}

.wishlist-btn.active {
    background: #000000;
}

.wishlist-btn.active svg {
    fill: #ffffff;
    color: #ffffff;
}

.product-info {
    padding: 16px;
}

.product-brand {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.product-name {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.product-price {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 12px;
}

.discount-rate {
    color: #000000;
    font-weight: 700;
    font-size: 18px;
}

.price {
    font-size: 18px;
    font-weight: 700;
}

.original-price {
    font-size: 14px;
    color: #999;
    text-decoration: line-through;
}

.product-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 13px;
    color: #666;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.rating {
    color: #ffd700;
}

/* 페이지네이션 */
.muziyeppo-pagination {
    display: flex;
    justify-content: center;
    margin: 60px 0;
}

.muziyeppo-pagination ul {
    display: flex;
    gap: 8px;
    list-style: none;
    padding: 0;
    margin: 0;
}

.muziyeppo-pagination li {
    display: inline-block;
}

.muziyeppo-pagination a,
.muziyeppo-pagination span {
    display: block;
    padding: 10px 16px;
    background: #f5f5f5;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-weight: 500;
    transition: all 0.3s;
}

.muziyeppo-pagination a:hover,
.muziyeppo-pagination .current {
    background: #000000;
    color: #ffffff;
}

/* 로딩 */
.loading {
    display: flex;
    justify-content: center;
    padding: 40px;
}

.spinner {
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

.hidden {
    display: none !important;
}

/* 스크롤 탑 버튼 */
.scroll-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
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

.scroll-to-top.show {
    opacity: 1;
    visibility: visible;
}

.scroll-to-top:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.scroll-to-top svg {
    width: 24px;
    height: 24px;
}

/* 애니메이션 */
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

/* 반응형 */
@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
        padding: 0 12px;
    }
    
    .category-grid {
        grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
        gap: 12px;
    }
    
    .banner-title {
        font-size: 28px;
    }
    
    .main-banner {
        height: 250px;
    }
}
</style>

<script>
// 전역 변수
let currentBannerIndex = 0;

// 배너 슬라이더
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
    
    // 배너 자동 슬라이드
    setInterval(() => {
        currentBannerIndex = (currentBannerIndex + 1) % 3;
        updateBanner();
    }, 5000);
    
    // 스크롤 이벤트
    window.addEventListener('scroll', handleScroll);
});

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

// 카테고리 필터
function filterByCategory(categorySlug) {
    window.location.href = '<?php echo home_url('/product-category/'); ?>' + categorySlug;
}

// 정렬 기능
function sortProducts(sortBy) {
    const currentUrl = new URL(window.location.href);
    currentUrl.searchParams.set('orderby', sortBy);
    window.location.href = currentUrl.toString();
}

// 찜하기 토글
function toggleWishlist(productId) {
    // muziyeppo_ajax 존재 여부 확인
    if (typeof muziyeppo_ajax === 'undefined' || !muziyeppo_ajax.nonce) {
        console.warn('muziyeppo_ajax가 완전히 구성되지 않았습니다.');
        showToast('로그인이 필요합니다');
        return;
    }
    
    const btn = document.querySelector(`.wishlist-btn[data-product-id="${productId}"]`);
    
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
            
            // 좋아요 수 업데이트
            const likesElement = btn.closest('.product-card').querySelector('.stat-item:last-child');
            if (likesElement && data.data.likes !== undefined) {
                const likesText = data.data.likes > 1000 ? 
                    `♥ ${(data.data.likes/1000).toFixed(1)}k` : 
                    `♥ ${data.data.likes}`;
                likesElement.textContent = likesText;
            }
            
            showToast(data.data.liked ? '찜 목록에 추가되었습니다' : '찜 목록에서 제거되었습니다');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('오류가 발생했습니다');
    });
}

// 스크롤 처리
function handleScroll() {
    const scrollToTopBtn = document.getElementById('scrollToTop');
    
    if (window.pageYOffset > 300) {
        scrollToTopBtn.classList.add('show');
    } else {
        scrollToTopBtn.classList.remove('show');
    }
}

// 맨 위로 가기
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
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

// 토스트 스타일 추가
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

<?php get_footer(); ?>