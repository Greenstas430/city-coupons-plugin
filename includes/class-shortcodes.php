<?php
class CityCoupons_Shortcodes {
    public function render_upload_form() {
        ob_start();
        ?>
        <div id="pgc-upload-form-shortcode">
            <?php include CITY_COUPONS_DIR . 'public/templates/upload-form.php'; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_coupons_list($atts) {
        $atts = shortcode_atts(['count' => 20], $atts);
        $coupons = (new CityCoupons_Coupons())->get_approved_coupons((int)$atts['count']);

        ob_start();
        ?>
        <div class="city-coupons-list-container">
            <div class="pgc-gallery-header">
    <h2 style="display: none;">Купоны и Акции</h2>
    <button class="pgc-upload-btn" id="pgc-open-upload-modal">
        Добавить купон или акцию
    </button>
</div>

            <?php if (empty($coupons)): ?>
                <div class="pgc-empty-state" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                    <h3 style="color: #6c757d; margin-bottom: 10px;">Пока нет активных купонов</h3>
                    <p style="color: #adb5bd; margin-bottom: 30px;">Будьте первым, кто добавит купон!</p>
                    <button class="pgc-upload-btn" id="pgc-open-upload-modal-2" style="font-size: 16px;">
                        Добавить купон
                    </button>
                </div>
            <?php else: ?>
                <div class="city-coupons-list">
                    <?php foreach ($coupons as $coupon): 
                        $is_coupon = ($coupon->coupon_type === 'coupon');
                        $type_class = $is_coupon ? 'coupon-type' : 'promotion-type';
                        ?>
                        <div class="pgc-coupon-item <?php echo $type_class; ?>">
                            <img src="<?php echo esc_url($coupon->image_url); ?>" 
                                 alt="<?php echo esc_attr($coupon->title); ?>"
                                 loading="lazy">
                            <div class="pgc-coupon-info">
                                <h4><?php echo esc_html($coupon->store_name); ?></h4>
                                <p><strong><?php echo esc_html($coupon->title); ?></strong></p>
                                <?php if (!empty($coupon->description)): ?>
                                    <p class="pgc-description"><?php echo esc_html($coupon->description); ?></p>
                                <?php endif; ?>
                                <?php if ($is_coupon): ?>
                                    <p style="color: #667eea; font-size: 13px; margin-top: 10px; font-weight: 600;">
                                        <span class="dashicons dashicons-download"></span> Нажмите на карточку для скачивания
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Модальное окно загрузки -->
            <?php include CITY_COUPONS_DIR . 'public/templates/upload-form.php'; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#pgc-open-upload-modal-2').on('click', function() {
                $('#pgc-open-upload-modal').click();
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_photo_gallery($atts) {
        $atts = shortcode_atts(['count' => 20], $atts);
        $photos = (new CityCoupons_Photos())->get_approved_photos((int)$atts['count']);

        ob_start();
        ?>
        <div class="city-photos-gallery-container">
            <div class="pgc-gallery-header">
    <h2 style="display: none;">Фотогалерея</h2>
    <button class="pgc-upload-btn" id="pgc-open-photo-upload-modal">
        Добавить фото
    </button>
</div>

            <?php if (empty($photos)): ?>
                <div class="pgc-empty-state" style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📷</div>
                    <h3 style="color: #6c757d; margin-bottom: 10px;">Пока нет фотографий</h3>
                    <p style="color: #adb5bd; margin-bottom: 30px;">Будьте первым, кто добавит фото!</p>
                    <button class="pgc-upload-btn" id="pgc-open-photo-upload-modal-2">
                        Добавить фото
                    </button>
                </div>
            <?php else: ?>
                <div class="city-photos-gallery">
                    <?php foreach ($photos as $photo): 
                        $current_ip = (new CityCoupons_Photos())->get_client_ip();
                        $is_liked = (new CityCoupons_Photos())->is_liked_by_ip($photo->id, $current_ip);
                        ?>
                        <div class="pgc-photo-item" data-photo-id="<?php echo esc_attr($photo->id); ?>">
                            <img src="<?php echo esc_url($photo->image_url); ?>" 
                                 alt="<?php echo esc_attr($photo->description ?: 'Фото'); ?>"
                                 loading="lazy"
                                 style="cursor: pointer;">
                            <div class="pgc-photo-info">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <h4><?php echo esc_html($photo->photographer_name); ?></h4>
                                    <button class="pgc-like-btn <?php echo $is_liked ? 'liked' : ''; ?>"
                                            data-photo-id="<?php echo esc_attr($photo->id); ?>">
                                        <span class="pgc-heart <?php echo $is_liked ? 'liked' : ''; ?>"></span>
                                        <span class="pgc-likes-count"><?php echo esc_html($photo->likes_count); ?></span>
                                    </button>
                                </div>
                                <?php if (!empty($photo->description)): ?>
                                    <p class="pgc-photo-description"><?php echo esc_html($photo->description); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Модальное окно загрузки фото -->
            <?php include CITY_COUPONS_DIR . 'public/templates/upload-photo.php'; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#pgc-open-photo-upload-modal-2').on('click', function() {
                $('#pgc-open-photo-upload-modal').click();
            });
        });
        </script>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_photo_of_the_day() {
        $photo = (new CityCoupons_Photos())->get_photo_of_the_day();
        
        if (!$photo) {
            // Если нет фото дня, показываем случайное популярное фото
            global $wpdb;
            $table = $wpdb->prefix . 'city_photos';
            $photo = $wpdb->get_row(
                "SELECT * FROM $table 
                WHERE status = 'approved' 
                ORDER BY likes_count DESC 
                LIMIT 1"
            );
            
            if (!$photo) {
                return '<div class="pgc-photo-of-day-empty">Сегодня нет фото дня</div>';
            }
        }
        
        ob_start();
        ?>
        <div class="pgc-photo-of-day">
            <h3 style="text-align: center; margin-bottom: 15px; color: #2d3748;">
                <span style="margin-right: 8px;">🌟</span> Фото дня
            </h3>
            <div class="pgc-photo-of-day-card" onclick="window.open('<?php echo esc_url($photo->image_url); ?>', '_blank')"
                 style="cursor: pointer;">
                <img src="<?php echo esc_url($photo->image_url); ?>" 
                     alt="<?php echo esc_attr($photo->description ?: 'Фото дня'); ?>"
                     loading="lazy">
                <div class="pgc-photo-of-day-info">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 600;"><?php echo esc_html($photo->photographer_name); ?></span>
                        <div style="display: flex; align-items: center;">
                            <span class="pgc-heart liked" style="margin-right: 5px;"></span>
                            <span><?php echo esc_html($photo->likes_count); ?></span>
                        </div>
                    </div>
                    <?php if (!empty($photo->description)): ?>
                        <p style="margin-top: 10px; font-size: 14px; color: #666;"><?php echo esc_html(mb_substr($photo->description, 0, 100)); ?>...</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_ads_list($atts) {
    $atts = shortcode_atts([
        'count' => 20,
        'category' => null,
        'show_filter' => 'yes',
        'compact' => 'yes'
    ], $atts);
    
    $category_id = null;
    if ($atts['category']) {
        $category = get_term_by('slug', $atts['category'], 'category');
        if ($category) {
            $category_id = $category->term_id;
        }
    }
    
    $ads = (new CityCoupons_Ads())->get_approved_ads((int)$atts['count'], $category_id);
    $categories = (new CityCoupons_Ads())->get_categories();
    
    // Получаем только категории, в которых есть объявления
    $active_categories = [];
    if ($ads) {
        $active_category_ids = array_unique(array_column($ads, 'category_id'));
        foreach ($categories as $cat) {
            if (in_array($cat->id, $active_category_ids)) {
                $active_categories[] = $cat;
            }
        }
    }
    
    // Определяем классы
    $list_class = ($atts['compact'] === 'yes') ? 'city-ads-list compact' : 'city-ads-list';
    $item_class = ($atts['compact'] === 'yes') ? 'pgc-ad-item compact' : 'pgc-ad-item';

    ob_start();
    ?>
    <div class="city-ads-container">
        <div class="pgc-gallery-header">
            <!-- Заголовок скрыт, оставляем только кнопку -->
            <h2 style="display: none;">Доска объявлений</h2>
            <button class="pgc-upload-btn" id="pgc-open-ad-upload-modal">
                Подать объявление
            </button>
        </div>

        <?php if ($atts['show_filter'] === 'yes' && !empty($active_categories)): ?>
        <div class="pgc-ads-filter">
            <div class="pgc-ads-filter-top">
                <div class="pgc-filter-categories">
                    <a href="#all" class="pgc-filter-category active" data-category="all">Все</a>
                    <?php foreach ($active_categories as $cat): ?>
                        <a href="#<?php echo esc_attr($cat->slug); ?>" 
                           class="pgc-filter-category" 
                           data-category="<?php echo esc_attr($cat->id); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="pgc-filter-sort">
                <span class="pgc-filter-sort-label">
                    <span class="dashicons dashicons-sort"></span>
                    Сортировка:
                </span>
                <select class="pgc-filter-sort-select" id="pgc-ads-sort">
                    <option value="newest">По дате</option>
                    <option value="price_asc">Дешевле</option>
                    <option value="price_desc">Дороже</option>
                    <option value="popular">Популярные</option>
                </select>
            </div>
        </div>
        <?php endif; ?>

        <?php if (empty($ads)): ?>
            <div class="pgc-empty-state" style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                <h3 style="color: #6c757d; margin-bottom: 10px;">Пока нет объявлений</h3>
                <p style="color: #adb5bd; margin-bottom: 30px;">Будьте первым, кто добавит объявление!</p>
                <button class="pgc-upload-btn" id="pgc-open-ad-upload-modal-2">
                    Подать объявление
                </button>
            </div>
        <?php else: ?>
            <div class="<?php echo esc_attr($list_class); ?>">
                <?php foreach ($ads as $ad): 
                    $photos = (new CityCoupons_Ads())->get_ad_photos($ad->id);
                    $current_ip = (new CityCoupons_Ads())->get_client_ip();
                    $is_favorited = (new CityCoupons_Ads())->is_favorited_by_ip($ad->id, $current_ip);
                    $main_photo = $photos ? $photos[0]->image_url : CITY_COUPONS_URL . 'public/images/no-photo.jpg';
                    ?>
                    <a href="<?php echo home_url("/ad/view/{$ad->id}"); ?>" 
                       class="<?php echo esc_attr($item_class); ?>"
                       data-ad-id="<?php echo esc_attr($ad->id); ?>" 
                       data-category="<?php echo esc_attr($ad->category_id); ?>"
                       data-price="<?php echo esc_attr($ad->price ?: 0); ?>"
                       data-views="<?php echo esc_attr($ad->views_count); ?>"
                       data-favorites="<?php echo esc_attr($ad->favorites_count); ?>"
                       style="text-decoration: none; display: block;">
                        
                        <div class="pgc-ad-slider">
                            <?php if ($photos): ?>
                                <?php foreach ($photos as $photo): ?>
                                    <div class="pgc-ad-slide">
                                        <img src="<?php echo esc_url($photo->image_url); ?>" 
                                             alt="<?php echo esc_attr($ad->title); ?>"
                                             loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="pgc-ad-slide">
                                    <img src="<?php echo CITY_COUPONS_URL; ?>public/images/no-photo.jpg" 
                                         alt="Нет фото">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Бейдж категории на фото -->
                        <div class="pgc-ad-category-badge">
                            <?php echo esc_html($ad->category_name); ?>
                        </div>
                        
                        <!-- Кнопка избранного -->
                        <button class="pgc-favorite-btn <?php echo $is_favorited ? 'favorited' : ''; ?>"
                                data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                onclick="event.preventDefault(); event.stopPropagation(); pgcToggleFavorite(<?php echo esc_attr($ad->id); ?>, this);">
                            <span class="pgc-star <?php echo $is_favorited ? 'favorited' : ''; ?>"></span>
                        </button>
                        
                        <div class="pgc-ad-info">
                            <div class="pgc-ad-header">
                                <h4><?php echo esc_html($ad->title); ?></h4>
                            </div>
                            
                            <?php if ($ad->price): ?>
                                <div class="pgc-ad-price">
                                    <strong><?php echo number_format($ad->price, 0, ',', ' '); ?> <?php echo esc_html($ad->currency); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($ad->address)): ?>
                                <div class="pgc-ad-address">
                                    <?php echo esc_html($ad->address); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($ad->description)): ?>
                                <p class="pgc-ad-description"><?php echo esc_html(mb_substr($ad->description, 0, 80)); ?>...</p>
                            <?php endif; ?>
                            
                            <div class="pgc-ad-meta">
                                <span class="pgc-ad-views">
                                    <span class="dashicons dashicons-visibility" style="font-size: 10px;"></span>
                                    <?php echo esc_html($ad->views_count); ?>
                                </span>
                                <span class="pgc-ad-date">
                                    <?php echo human_time_diff(strtotime($ad->created_at)); ?> назад
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Модальное окно подачи объявления -->
        <?php include CITY_COUPONS_DIR . 'public/templates/upload-ad.php'; ?>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#pgc-open-ad-upload-modal-2').on('click', function() {
            $('#pgc-open-ad-upload-modal').click();
        });
        
        // Инициализация слайдеров
        $('.pgc-ad-slider').slick({
            dots: true,
            arrows: true,
            infinite: true,
            speed: 300,
            slidesToShow: 1,
            adaptiveHeight: false,
            prevArrow: '<button type="button" class="slick-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button>',
            nextArrow: '<button type="button" class="slick-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button>'
        });
        
        // Фильтрация по категориям
        $('.pgc-filter-category').on('click', function(e) {
            e.preventDefault();
            $('.pgc-filter-category').removeClass('active');
            $(this).addClass('active');
            
            const category = $(this).data('category');
            if (category === 'all') {
                $('.pgc-ad-item').show();
            } else {
                $('.pgc-ad-item').hide();
                $(`.pgc-ad-item[data-category="${category}"]`).show();
            }
        });
        
        // Сортировка
        $('#pgc-ads-sort').on('change', function() {
            const sortType = $(this).val();
            const $container = $('.city-ads-list');
            const $items = $('.pgc-ad-item');
            
            $items.sort(function(a, b) {
                const $a = $(a);
                const $b = $(b);
                
                switch(sortType) {
                    case 'price_asc':
                        return parseFloat($a.data('price')) - parseFloat($b.data('price'));
                    case 'price_desc':
                        return parseFloat($b.data('price')) - parseFloat($a.data('price'));
                    case 'popular':
                        const scoreA = ($a.data('views') * 0.3 + $a.data('favorites') * 0.7);
                        const scoreB = ($b.data('views') * 0.3 + $b.data('favorites') * 0.7);
                        return scoreB - scoreA;
                    default: // newest (по дате)
                        return 0; // Уже отсортировано по новизне
                }
            }).appendTo($container);
        });
    });
    
    // Функция для добавления в избранное
    function pgcToggleFavorite(adId, button) {
        const $button = jQuery(button);
        const nonce = '<?php echo wp_create_nonce("city_ads_ajax_nonce"); ?>';
        
        jQuery.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: {
                action: 'pgc_toggle_favorite',
                ad_id: adId,
                nonce: nonce
            },
            beforeSend: function() {
                $button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    $button.toggleClass('favorited');
                    $button.find('.pgc-star').toggleClass('favorited');
                    // Обновляем счетчик если он есть
                    const $count = $button.find('.pgc-favorites-count');
                    if ($count.length) {
                        $count.text(response.data.favorites_count);
                    }
                }
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    }
    </script>
    <?php
    
    return ob_get_clean();
}
    
    public function render_ads_categories() {
        $categories = (new CityCoupons_Ads())->get_categories();
        
        ob_start();
        ?>
        <div class="pgc-ads-categories-widget">
            <h3 style="margin-bottom: 15px; color: #2d3748;">Категории объявлений</h3>
            <div class="pgc-categories-list">
                <?php foreach ($categories as $cat): ?>
                    <a href="<?php echo home_url("/ads/category/{$cat->slug}"); ?>" 
                       class="pgc-category-item">
                        <span class="pgc-category-name"><?php echo esc_html($cat->name); ?></span>
                        <?php if ($cat->description): ?>
                            <span class="pgc-category-desc"><?php echo esc_html($cat->description); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    public function render_ad_of_the_day() {
        $ad = (new CityCoupons_Ads())->get_ad_of_the_day();
        
        if (!$ad) {
            // Если нет объявления дня, показываем случайное популярное
            $ads = (new CityCoupons_Ads())->get_approved_ads(1);
            $ad = $ads[0] ?? null;
            
            if (!$ad) {
                return '<div class="pgc-ad-of-day-empty">Сегодня нет объявления дня</div>';
            }
        }
        
        $photos = (new CityCoupons_Ads())->get_ad_photos($ad->id);
        $main_photo = $photos ? $photos[0]->image_url : CITY_COUPONS_URL . 'public/images/no-photo.jpg';
        
        ob_start();
        ?>
        <div class="pgc-ad-of-day">
            <h3 style="text-align: center; margin-bottom: 15px; color: #2d3748;">
                <span style="margin-right: 8px;">🏆</span> Объявление дня
            </h3>
            <div class="pgc-ad-of-day-card">
                <div class="pgc-ad-of-day-image">
                    <img src="<?php echo esc_url($main_photo); ?>" 
                         alt="<?php echo esc_attr($ad->title); ?>"
                         loading="lazy">
                </div>
                <div class="pgc-ad-of-day-info">
                    <h4 style="margin: 0 0 10px; font-size: 16px;">
                        <a href="<?php echo home_url("/ad/view/{$ad->id}"); ?>">
                            <?php echo esc_html(mb_substr($ad->title, 0, 50)); ?>...
                        </a>
                    </h4>
                    
                    <?php if ($ad->price): ?>
                        <div style="font-weight: bold; color: #2d3748; margin-bottom: 8px;">
                            <?php echo number_format($ad->price, 0, ',', ' '); ?> <?php echo esc_html($ad->currency); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                        <span style="font-size: 12px; color: #718096;">
                            <span class="dashicons dashicons-visibility" style="font-size: 12px;"></span>
                            <?php echo esc_html($ad->views_count); ?>
                        </span>
                        <span style="font-size: 12px; color: #718096;">
                            <?php echo human_time_diff(strtotime($ad->created_at)); ?> назад
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }
}
?>