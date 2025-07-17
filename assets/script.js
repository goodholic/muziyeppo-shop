/* ===== assets/script.js ===== */
// 무지예뽀 쇼핑몰 JavaScript

jQuery(document).ready(function($) {
    
    // 찜하기 기능
    $('.muziyeppo-wishlist-btn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var productId = $btn.data('product-id');
        
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_toggle_like',
                product_id: productId,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $btn.toggleClass('active');
                    
                    // 좋아요 수 업데이트
                    var $likesSpan = $btn.closest('.muziyeppo-product-card').find('.muziyeppo-likes-count');
                    if ($likesSpan.length) {
                        var likes = response.data.likes;
                        var likesText = likes > 1000 ? (likes/1000).toFixed(1) + 'k' : likes;
                        $likesSpan.text('♥ ' + likesText);
                    }
                    
                    showToast(response.data.liked ? '찜 목록에 추가되었습니다' : '찜 목록에서 제거되었습니다');
                }
            }
        });
    });
    
    // 장바구니 추가
    window.addToCart = function(productId) {
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_add_to_cart',
                product_id: productId,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showToast('장바구니에 추가되었습니다');
                    
                    // 장바구니 카운트 업데이트
                    updateCartCount();
                } else {
                    showToast('오류가 발생했습니다');
                }
            }
        });
    };
    
    // 토스트 메시지 표시
    function showToast(message) {
        var $toast = $('<div class="muziyeppo-toast">' + message + '</div>');
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
    // 장바구니 카운트 업데이트
    function updateCartCount() {
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_get_cart_count',
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.muziyeppo-cart-count').text(response.data.count);
                }
            }
        });
    }
    
    // 무한 스크롤
    var loading = false;
    var page = 2;
    
    $(window).scroll(function() {
        if ($(window).scrollTop() + $(window).height() > $(document).height() - 500) {
            if (!loading && $('.muziyeppo-products-grid').length) {
                loading = true;
                loadMoreProducts();
            }
        }
    });
    
    function loadMoreProducts() {
        var $grid = $('.muziyeppo-products-grid');
        var $loader = $('<div class="muziyeppo-loading"></div>');
        
        $grid.after($loader);
        
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_load_more',
                page: page,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $grid.append(response.data.html);
                    page++;
                    loading = false;
                } else {
                    // 더 이상 상품이 없음
                    $(window).off('scroll');
                }
                $loader.remove();
            }
        });
    }
    
    // 이미지 지연 로딩
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var image = entry.target;
                    image.src = image.dataset.src;
                    image.classList.remove('lazy');
                    imageObserver.unobserve(image);
                }
            });
        });
        
        var lazyImages = document.querySelectorAll('img.lazy');
        lazyImages.forEach(function(image) {
            imageObserver.observe(image);
        });
    }
    
    // 상품 필터링
    $('.muziyeppo-filter').on('change', function() {
        var filters = {};
        
        $('.muziyeppo-filter').each(function() {
            var $this = $(this);
            var filterType = $this.data('filter');
            var value = $this.val();
            
            if (value) {
                filters[filterType] = value;
            }
        });
        
        // AJAX로 필터링된 상품 로드
        loadFilteredProducts(filters);
    });
    
    function loadFilteredProducts(filters) {
        var $grid = $('.muziyeppo-products-grid');
        $grid.html('<div class="muziyeppo-loading"></div>');
        
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_filter_products',
                filters: filters,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $grid.html(response.data.html);
                }
            }
        });
    }
    
    // 상품 빠른 보기
    $('.muziyeppo-quick-view').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var productId = $(this).data('product-id');
        
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_quick_view',
                product_id: productId,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 모달로 표시
                    showQuickViewModal(response.data.html);
                }
            }
        });
    });
    
    function showQuickViewModal(content) {
        var $modal = $('<div class="muziyeppo-modal"><div class="muziyeppo-modal-content">' + content + '</div></div>');
        $('body').append($modal);
        
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.remove();
            }
        });
    }
    
    // 상품 검색
    var searchTimer;
    $('#muziyeppo-search').on('input', function() {
        clearTimeout(searchTimer);
        var query = $(this).val();
        
        if (query.length > 2) {
            searchTimer = setTimeout(function() {
                searchProducts(query);
            }, 500);
        }
    });
    
    function searchProducts(query) {
        $.ajax({
            url: muziyeppo_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'muziyeppo_search',
                query: query,
                nonce: muziyeppo_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // 검색 결과 표시
                    $('#muziyeppo-search-results').html(response.data.html);
                }
            }
        });
    }
    
});