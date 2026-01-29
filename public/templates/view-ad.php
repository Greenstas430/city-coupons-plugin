<?php
if (!defined('ABSPATH')) exit;
$ad_id = get_query_var('pgc_ad_view');
$ad = (new CityCoupons_Ads())->get_ad_by_id($ad_id);
$photos = (new CityCoupons_Ads())->get_ad_photos($ad_id);
$current_ip = (new CityCoupons_Ads())->get_client_ip();
$is_favorited = (new CityCoupons_Ads())->is_favorited_by_ip($ad_id, $current_ip);
if (!$ad || $ad->status !== 'approved') {
	wp_die('Объявление не найдено или не одобрено.', 'Ошибка', ['response' => 404]);
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html($ad->title); ?> | <?php bloginfo('name'); ?></title>
<?php wp_head(); ?>
<link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/style.css?v=<?php echo CITY_COUPONS_VERSION; ?>">
<link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/slick.css?v=1.8.1">
<link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/slick-theme.css?v=1.8.1">
</head>
<body <?php body_class(); ?>>
<?php get_header(); ?>

<div class="pgc-ad-view-page">
	<!-- Кнопка "Назад" (видна на всех устройствах) -->
	<a href="javascript:history.back()" class="pgc-back-btn">
		<span class="dashicons dashicons-arrow-left-alt2"></span>
		<span class="pgc-back-text">Назад</span>
	</a>
	
	<!-- Для компьютеров -->
	<div class="pgc-ad-view-desktop">
		<div class="pgc-ad-view-header">
			<h1 style="margin: 0; font-size: 24px; color: #2d3748;"><?php echo esc_html($ad->title); ?></h1>
			<button class="pgc-favorite-btn <?php echo $is_favorited ? 'favorited' : ''; ?>"
				data-ad-id="<?php echo esc_attr($ad->id); ?>">
				<span class="pgc-star <?php echo $is_favorited ? 'favorited' : ''; ?>"></span>
				<span class="pgc-favorites-count"><?php echo esc_html($ad->favorites_count); ?></span>
			</button>
		</div>
		
		<div class="pgc-ad-view-content">
			<!-- Левая часть: фото с возможностью прокрутки -->
			<div class="pgc-ad-gallery-section">
				<div class="pgc-ad-gallery-scrollable">
					<div class="pgc-ad-gallery">
						<?php if ($photos): ?>
							<div class="pgc-ad-main-slider">
								<?php foreach ($photos as $photo): ?>
									<div class="pgc-ad-slide">
										<img src="<?php echo esc_url($photo->image_url); ?>"
											alt="<?php echo esc_attr($ad->title); ?>"
											loading="lazy">
									</div>
								<?php endforeach; ?>
							</div>
							
							<?php if (count($photos) > 1): ?>
								<div class="pgc-ad-thumbnails" style="margin-top: 15px; display: flex; gap: 8px; flex-wrap: wrap;">
									<?php foreach ($photos as $index => $photo): ?>
										<div class="pgc-ad-thumb" style="cursor: pointer;">
											<img src="<?php echo esc_url($photo->image_url); ?>" 
												data-slide="<?php echo $index; ?>"
												style="width: 60px; height: 45px; object-fit: cover; border-radius: 4px; border: 2px solid #e2e8f0;">
										</div>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						<?php else: ?>
							<div style="text-align: center; padding: 60px 20px; background: #f8fafc; color: #718096; font-size: 16px; border-radius: 12px;">
								🖼️ Нет фотографий
							</div>
						<?php endif; ?>
					</div>
					
					<!-- Блок с адресом (под галереей) -->
					<?php if ($ad->address): ?>
						<div class="pgc-ad-meta-card">
							<h3 style="margin: 0 0 12px; font-size: 16px; color: #2d3748;">📍 Адрес</h3>
							<p style="margin: 0; color: #4a5568; font-size: 15px;"><?php echo esc_html($ad->address); ?></p>
						</div>
					<?php endif; ?>
				</div>
			</div>
			
			<!-- Правая часть: фиксированный блок с ценой и контактами + описание -->
			<div class="pgc-ad-info-section">
				<!-- Фиксированный блок цены и контактов -->
				<div class="pgc-ad-price-contact-card">
					<?php if ($ad->price): ?>
						<div class="pgc-ad-price-main">
							<?php echo number_format($ad->price, 0, ',', ' '); ?>
							<span class="currency"><?php echo esc_html($ad->currency); ?></span>
						</div>
					<?php else: ?>
						<div class="pgc-ad-price-main" style="color: #718096; font-size: 24px;">
							Договорная
						</div>
					<?php endif; ?>
					
					<div class="pgc-contact-seller">
						<h3>Контакт продавца</h3>
						<div class="pgc-seller-name">
							<span class="dashicons dashicons-businessperson" style="font-size: 20px; color: #667eea;"></span>
							<span style="font-weight: 600; color: #2d3748; font-size: 16px;"><?php echo esc_html($ad->contact_name); ?></span>
						</div>
						
						<button class="pgc-phone-btn" onclick="pgcCallPhone('<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $ad->contact_phone)); ?>')">
							<span class="dashicons dashicons-phone"></span>
							<?php echo esc_html($ad->contact_phone); ?>
						</button>
						
						<button class="pgc-copy-phone-btn" data-phone="<?php echo esc_attr($ad->contact_phone); ?>">
							📋 Копировать телефон
						</button>
						
						<?php if ($ad->contact_email): ?>
							<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eef2f7; color: #64748b; font-size: 14px;">
								📧 Email: <?php echo esc_html($ad->contact_email); ?>
							</div>
						<?php endif; ?>
					</div>
				</div>
				
				<!-- Описание товара -->
				<?php if ($ad->description): ?>
					<div class="pgc-ad-description-card">
						<h3 style="margin: 0 0 15px; font-size: 18px; color: #2d3748;">Описание</h3>
						<div class="pgc-ad-description-full">
							<?php echo nl2br(esc_html($ad->description)); ?>
						</div>
					</div>
				<?php endif; ?>
				
				<!-- Дополнительная информация -->
				<div class="pgc-ad-meta-card">
					<h3 style="margin: 0 0 15px; font-size: 18px; color: #2d3748;">Дополнительная информация</h3>
					<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
						<div>
							<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Категория</div>
							<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->category_name); ?></div>
						</div>
						<div>
							<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Просмотров</div>
							<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->views_count); ?></div>
						</div>
						<div>
							<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">В избранном</div>
							<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->favorites_count); ?></div>
						</div>
						<div>
							<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Обновлено</div>
							<div style="font-weight: 600; color: #2d3748;"><?php echo date_i18n('d.m.Y', strtotime($ad->created_at)); ?></div>
						</div>
					</div>
				</div>
				
				<!-- Дата публикации (аккуратно внизу) -->
				<div class="pgc-ad-date">
					Опубликовано: <?php echo date_i18n('d.m.Y в H:i', strtotime($ad->created_at)); ?>
				</div>
			</div>
		</div>
	</div>
	
	<!-- Для мобильных устройств -->
	<div class="pgc-ad-view-mobile">
		<!-- Слайдер с частичным отображением -->
		<div class="pgc-ad-mobile-slider">
			<div class="pgc-ad-mobile-slides" id="pgcMobileSlides">
				<?php if ($photos): ?>
					<?php foreach ($photos as $index => $photo): ?>
						<div class="pgc-ad-mobile-slide <?php echo $index === count($photos) - 1 ? 'full-width' : ''; ?>"
							data-index="<?php echo $index; ?>">
							<img src="<?php echo esc_url($photo->image_url); ?>"
								alt="<?php echo esc_attr($ad->title); ?>"
								loading="lazy"
								data-fullscreen="<?php echo esc_url($photo->image_url); ?>">
						</div>
					<?php endforeach; ?>
				<?php else: ?>
					<div class="pgc-ad-mobile-slide full-width">
						<img src="<?php echo CITY_COUPONS_URL; ?>public/images/no-photo.jpg" 
							alt="Нет фото"
							style="border-radius: 0;">
					</div>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="pgc-ad-mobile-content">
			<!-- Заголовок -->
			<h1 style="margin: 0 0 15px; font-size: 20px; color: #2d3748; line-height: 1.3;">
				<?php echo esc_html($ad->title); ?>
			</h1>
			
			<!-- Цена -->
			<?php if ($ad->price): ?>
				<div class="pgc-ad-price-mobile">
					<?php echo number_format($ad->price, 0, ',', ' '); ?> <?php echo esc_html($ad->currency); ?>
				</div>
			<?php else: ?>
				<div class="pgc-ad-price-mobile" style="color: #718096;">
					Договорная
				</div>
			<?php endif; ?>
			
			<!-- Кнопка телефона -->
			<button class="pgc-phone-btn-mobile" onclick="pgcCallPhone('<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $ad->contact_phone)); ?>')">
				<span class="dashicons dashicons-phone"></span>
				Позвонить: <?php echo esc_html($ad->contact_phone); ?>
			</button>
			
			<button class="pgc-copy-phone-btn" data-phone="<?php echo esc_attr($ad->contact_phone); ?>" style="width: 100%; margin-bottom: 20px;">
				📋 Копировать телефон
			</button>
			
			<!-- Продавец -->
			<div class="pgc-ad-mobile-section">
				<h3>Продавец</h3>
				<div style="display: flex; align-items: center; gap: 10px;">
					<span class="dashicons dashicons-businessperson" style="font-size: 24px; color: #667eea;"></span>
					<span style="font-weight: 600; color: #2d3748; font-size: 16px;"><?php echo esc_html($ad->contact_name); ?></span>
				</div>
				<?php if ($ad->contact_email): ?>
					<div style="margin-top: 10px; color: #64748b; font-size: 14px;">
						📧 <?php echo esc_html($ad->contact_email); ?>
					</div>
				<?php endif; ?>
			</div>
			
			<!-- Адрес -->
			<?php if ($ad->address): ?>
				<div class="pgc-ad-mobile-section">
					<h3>📍 Адрес</h3>
					<p style="margin: 0; color: #4a5568; font-size: 15px; line-height: 1.5;"><?php echo esc_html($ad->address); ?></p>
				</div>
			<?php endif; ?>
			
			<!-- Описание -->
			<?php if ($ad->description): ?>
				<div class="pgc-ad-mobile-section">
					<h3>Описание</h3>
					<div class="pgc-ad-description-mobile">
						<?php echo nl2br(esc_html($ad->description)); ?>
					</div>
				</div>
			<?php endif; ?>
			
			<!-- Категория и статистика -->
			<div class="pgc-ad-mobile-section">
				<h3>Информация</h3>
				<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
					<div>
						<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Категория</div>
						<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->category_name); ?></div>
					</div>
					<div>
						<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Просмотры</div>
						<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->views_count); ?></div>
					</div>
					<div>
						<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">В избранном</div>
						<div style="font-weight: 600; color: #2d3748;"><?php echo esc_html($ad->favorites_count); ?></div>
					</div>
					<div>
						<div style="font-size: 12px; color: #64748b; margin-bottom: 4px;">Опубликовано</div>
						<div style="font-weight: 600; color: #2d3748;"><?php echo date_i18n('d.m.Y', strtotime($ad->created_at)); ?></div>
					</div>
				</div>
			</div>
			
			<!-- Избранное (для мобильных) -->
			<button class="pgc-favorite-btn <?php echo $is_favorited ? 'favorited' : ''; ?>"
				data-ad-id="<?php echo esc_attr($ad->id); ?>"
				style="width: 100%; justify-content: center; margin-top: 20px;">
				<span class="pgc-star <?php echo $is_favorited ? 'favorited' : ''; ?>"></span>
				<span>В избранное</span>
				<span class="pgc-favorites-count">(<?php echo esc_html($ad->favorites_count); ?>)</span>
			</button>
			
			<!-- Дата публикации -->
			<div class="pgc-ad-date-mobile">
				Опубликовано: <?php echo date_i18n('d.m.Y в H:i', strtotime($ad->created_at)); ?>
			</div>
		</div>
	</div>
	
	<!-- Полноэкранный просмотр фото для мобильных -->
	<div class="pgc-fullscreen-gallery" id="pgcFullscreenGallery">
		<button class="pgc-fullscreen-close" onclick="pgcCloseFullscreen()">×</button>
		<div class="pgc-fullscreen-slider" id="pgcFullscreenSlider">
			<?php if ($photos): ?>
				<?php foreach ($photos as $photo): ?>
					<div class="pgc-fullscreen-slide">
						<img src="<?php echo esc_url($photo->image_url); ?>" 
							alt="<?php echo esc_attr($ad->title); ?>"
							loading="lazy">
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<script src="<?php echo CITY_COUPONS_URL; ?>public/js/slick.min.js?v=1.8.1"></script>
<script src="<?php echo CITY_COUPONS_URL; ?>public/js/ads.js?v=<?php echo CITY_COUPONS_VERSION; ?>"></script>
<script>
jQuery(document).ready(function($) {
	// Определяем устройство
	const isMobile = window.innerWidth <= 768;
	
	if (!isMobile) {
		// Для компьютера
		$('.pgc-ad-view-mobile').hide();
		
		// Инициализация слайдера для десктопа
		$('.pgc-ad-main-slider').slick({
			dots: true,
			arrows: true,
			infinite: true,
			speed: 300,
			slidesToShow: 1,
			adaptiveHeight: false,
			prevArrow: '<button type="button" class="slick-prev"><span class="dashicons dashicons-arrow-left-alt2"></span></button>',
			nextArrow: '<button type="button" class="slick-next"><span class="dashicons dashicons-arrow-right-alt2"></span></button>'
		});
		
		$('.pgc-ad-thumb img').on('click', function() {
			const slideIndex = $(this).data('slide');
			$('.pgc-ad-main-slider').slick('slickGoTo', slideIndex);
		});
	} else {
		// Для мобильных
		$('.pgc-ad-view-desktop').hide();
		$('.pgc-back-btn .pgc-back-text').hide();
		
		// Инициализация мобильного слайдера
		let currentSlide = 0;
		const slideCount = <?php echo count($photos); ?>;
		const $slides = $('#pgcMobileSlides');
		const slideWidth = 75; // 75% ширины экрана
		
		// Функция для перехода к слайду
		function goToSlide(index) {
			currentSlide = Math.max(0, Math.min(index, slideCount - 1));
			const translateX = -currentSlide * slideWidth;
			$slides.css('transform', `translateX(${translateX}%)`);
			
			// Если последний слайд, делаем его на всю ширину
			$('.pgc-ad-mobile-slide').removeClass('full-width');
			if (currentSlide === slideCount - 1) {
				$('.pgc-ad-mobile-slide:last-child').addClass('full-width');
			}
		}
		
		// Свайпы
		let startX = 0;
		let isDragging = false;
		
		$slides.on('touchstart', function(e) {
			startX = e.touches[0].clientX;
			isDragging = true;
		});
		
		$slides.on('touchmove', function(e) {
			if (!isDragging) return;
			const currentX = e.touches[0].clientX;
			const diff = startX - currentX;
			
			// Прокрутка слайдов
			if (Math.abs(diff) > 50) {
				if (diff > 0 && currentSlide < slideCount - 1) {
					goToSlide(currentSlide + 1);
					isDragging = false;
				} else if (diff < 0 && currentSlide > 0) {
					goToSlide(currentSlide - 1);
					isDragging = false;
				}
			}
		});
		
		$slides.on('touchend', function() {
			isDragging = false;
		});
		
		// Клик на фото для полноэкранного просмотра
		$('.pgc-ad-mobile-slide img').on('click', function() {
			const fullscreenUrl = $(this).data('fullscreen');
			if (fullscreenUrl) {
				$('#pgcFullscreenGallery').addClass('active');
				// Инициализируем слайдер для полноэкранного просмотра
				$('#pgcFullscreenSlider').slick({
					dots: true,
					arrows: true,
					infinite: true,
					speed: 300,
					slidesToShow: 1,
					adaptiveHeight: false
				});
				// Переходим к соответствующему слайду
				const slideIndex = $(this).closest('.pgc-ad-mobile-slide').data('index');
				$('#pgcFullscreenSlider').slick('slickGoTo', slideIndex);
			}
		});
	}
	
	// Копирование телефона
	$('.pgc-copy-phone-btn').on('click', function() {
		const phone = $(this).data('phone');
		if (navigator.clipboard) {
			navigator.clipboard.writeText(phone).then(function() {
				const $btn = $(this);
				const originalText = $btn.text();
				$btn.text('✅ Скопировано!');
				setTimeout(() => $btn.text(originalText), 2000);
			}.bind(this));
		} else {
			// Fallback для старых браузеров
			const textArea = document.createElement('textarea');
			textArea.value = phone;
			document.body.appendChild(textArea);
			textArea.select();
			document.execCommand('copy');
			document.body.removeChild(textArea);
			
			const $btn = $(this);
			const originalText = $btn.text();
			$btn.text('✅ Скопировано!');
			setTimeout(() => $btn.text(originalText), 2000);
		}
	});
	
	// Функция для звонка
	window.pgcCallPhone = function(phone) {
		window.location.href = 'tel:' + phone;
	};
	
	// Функция закрытия полноэкранного просмотра
	window.pgcCloseFullscreen = function() {
		$('#pgcFullscreenGallery').removeClass('active');
		$('#pgcFullscreenSlider').slick('unslick');
	};
	
	// Инициализация кнопок избранного
	if (typeof initFavoriteButtons === 'function') {
		initFavoriteButtons();
	}
	
	// Скрываем текст "Назад" на мобильных
	if (window.innerWidth <= 768) {
		$('.pgc-back-btn .pgc-back-text').hide();
	}
	
	// Адаптация при изменении размера окна
	$(window).on('resize', function() {
		if (window.innerWidth <= 768) {
			$('.pgc-ad-view-desktop').hide();
			$('.pgc-ad-view-mobile').show();
			$('.pgc-back-btn .pgc-back-text').hide();
		} else {
			$('.pgc-ad-view-mobile').hide();
			$('.pgc-ad-view-desktop').show();
			$('.pgc-back-btn .pgc-back-text').show();
		}
	});
});
</script>
<?php get_footer(); ?>
</body>
</html>