jQuery(document).ready(function($) {
    // === ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ===
    let currentModal = null;
    
    // === ОТКРЫТИЕ МОДАЛЬНОГО ОКНА ПОДАЧИ ОБЪЯВЛЕНИЯ ===
    $(document).on('click', '#pgc-open-ad-upload-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeAllModals();
        $('#pgc-ad-upload-modal').fadeIn(300);
        currentModal = '#pgc-ad-upload-modal';
        lockBodyScroll(true);
    });

    // === ФУНКЦИИ УПРАВЛЕНИЯ МОДАЛКАМИ ===
    function closeAllModals() {
        $('.pgc-upload-modal').fadeOut(300);
        currentModal = null;
        lockBodyScroll(false);
    }

    function lockBodyScroll(lock) {
        if (lock) {
            $('body').css('overflow', 'hidden');
        } else {
            $('body').css('overflow', '');
        }
    }

    $(document).on('click', '.pgc-close-modal', function() {
        closeAllModals();
    });

    $(document).on('click', function(e) {
        if ($(e.target).hasClass('pgc-upload-modal')) {
            closeAllModals();
        }
    });

    // === ПРЕДПРОСМОТР ФОТОГРАФИЙ ===
    $(document).on('change', '#pgc-ad-photos', function(e) {
        const files = e.target.files;
        const preview = $('#pgc-photos-preview .pgc-photos-grid');
        preview.empty();
        
        if (files.length > 10) {
            alert('Максимум 10 фотографий! Первые 10 будут загружены.');
        }
        
        const fileCount = Math.min(files.length, 10);
        $('#pgc-photos-preview').show();
        
        for (let i = 0; i < fileCount; i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const img = $(`<img src="${e.target.result}" 
                    style="width: 100%; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">`);
                const item = $('<div></div>').append(img);
                preview.append(item);
            };
            
            reader.readAsDataURL(file);
        }
    });

    // === ИЗБРАННОЕ ===
    $(document).on('click', '.pgc-favorite-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const adId = $btn.data('ad-id');
        const $star = $btn.find('.pgc-star');
        const $count = $btn.find('.pgc-favorites-count');
        
        console.log('Toggling favorite for ad ID:', adId);
        console.log('Nonce:', city_ads_ajax.nonce);
        
        $.post(city_ads_ajax.ajax_url, {
            action: 'pgc_toggle_favorite',
            ad_id: adId,
            nonce: city_ads_ajax.nonce
        }, function(response) {
            if (response.success) {
                const favorited = response.data.favorited;
                const newCount = response.data.favorites_count;
                
                $btn.toggleClass('favorited', favorited);
                $star.toggleClass('favorited', favorited);
                $count.text(newCount);
                
                // Анимация звезды
                if (favorited) {
                    $star.css('transform', 'scale(1.3)');
                    setTimeout(() => {
                        $star.css('transform', 'scale(1)');
                    }, 300);
                }
                
                // Обновляем текст кнопки
                if ($btn.find('span').length > 2) {
                    $btn.find('span').eq(1).text(favorited ? 'В избранном' : 'В избранное');
                }
            } else {
                console.error('Ошибка избранного:', response.data);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX Error:', textStatus, errorThrown);
            alert('Ошибка сети. Попробуйте еще раз.');
        });
    });

    // === ОТПРАВКА ФОРМЫ ПОДАЧИ ОБЪЯВЛЕНИЯ ===
    $(document).on('submit', '#pgc-ad-upload-form', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Form submitted');
        console.log('AJAX URL:', city_ads_ajax.ajax_url);
        console.log('Nonce:', city_ads_ajax.nonce);
        
        const formData = new FormData(this);
        formData.append('action', 'city_ads_upload');
        formData.append('nonce', city_ads_ajax.nonce); // Добавлено!
        
        const $btn = $('.pgc-submit-btn', this);
        const $msg = $('#pgc-ad-response-message', this);
        
        // Проверка обязательных полей
        const title = $('#pgc-ad-title').val().trim();
        const category = $('#pgc-ad-category').val();
        const contactName = $('#pgc-ad-contact-name').val().trim();
        const contactPhone = $('#pgc-ad-contact-phone').val().trim();
        const photos = $('#pgc-ad-photos')[0].files;
        
        if (!title || !category || !contactName || !contactPhone) {
            $msg.removeClass('success').addClass('error')
                .html('❌ Заполните все обязательные поля').show();
            return;
        }
        
        if (!photos || photos.length === 0) {
            $msg.removeClass('success').addClass('error')
                .html('❌ Добавьте хотя бы одну фотографию').show();
            return;
        }
        
        if (photos.length > 10) {
            $msg.removeClass('success').addClass('error')
                .html('❌ Максимум 10 фотографий').show();
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner"></span> Отправка...');
        
        $.ajax({
            url: city_ads_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    const editUrl = response.data.edit_url;
                    const photosCount = response.data.photos_count;
                    
                    let message = `
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🎉</div>
                            <h3 style="margin: 0 0 10px; color: #155724;">Объявление отправлено на модерацию!</h3>
                            <p style="margin-bottom: 20px;">Загружено ${photosCount} фотографий. После проверки объявление появится на сайте.</p>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                                <p style="margin: 0 0 10px; font-weight: 600;">📌 Сохраните эту ссылку:</p>
                                <input type="text" value="${editUrl}" readonly 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: white;"
                                    onclick="this.select()">
                                <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                                    По ней вы сможете редактировать или удалить объявление
                                </p>
                            </div>
                        </div>
                    `;
                    
                    $msg.removeClass('error').addClass('success').html(message).show();
                    document.getElementById('pgc-ad-upload-form').reset();
                    $('#pgc-photos-preview').hide().find('.pgc-photos-grid').empty();
                    
                    setTimeout(function() {
                        closeAllModals();
                    }, 8000);
                    
                    let seconds = 8;
                    const timerInterval = setInterval(function() {
                        seconds--;
                        $btn.html(`<span class="spinner"></span> Закроется через ${seconds}с`);
                        if (seconds <= 0) {
                            clearInterval(timerInterval);
                        }
                    }, 1000);
                    
                } else {
                    $msg.removeClass('success').addClass('error')
                        .html(`❌ Ошибка: ${response.data || 'Неизвестная ошибка'}`).show();
                    $btn.prop('disabled', false).text('Отправить на модерацию');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response:', xhr.responseText);
                
                $msg.removeClass('success').addClass('error')
                    .html('❌ Ошибка соединения. Проверьте интернет и попробуйте снова.').show();
                $btn.prop('disabled', false).text('Отправить на модерацию');
            }
        });
    });

    // === ESC ДЛЯ ЗАКРЫТИЯ МОДАЛКИ ===
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeAllModals();
        }
    });

    // === СПИННЕР И СТИЛИ ===
    $('head').append(`
        <style>
            .spinner {
                display: inline-block;
                width: 16px;
                height: 16px;
                border: 2px solid rgba(255,255,255,.3);
                border-radius: 50%;
                border-top-color: white;
                animation: spin 1s ease-in-out infinite;
                margin-right: 8px;
                vertical-align: middle;
            }
            @keyframes spin {
                to { transform: rotate(360deg); }
            }
            .pgc-star {
                width: 20px;
                height: 20px;
                display: inline-block;
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                transition: all 0.3s ease;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23999' d='M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z'/%3E%3C/svg%3E");
            }
            .pgc-star.favorited {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='%23f6ad55' d='M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z'/%3E%3C/svg%3E");
            }
            .pgc-favorite-btn.favorited {
                border-color: #f6ad55 !important;
                background: #fffaf0 !important;
                color: #dd6b20 !important;
            }
        </style>
    `);
    
    // Делаем функции глобальными
    window.currentModal = null;
    window.closeAllModals = closeAllModals;
    window.lockBodyScroll = lockBodyScroll;
    
    // Инициализация при загрузке
    console.log('ADS.JS loaded successfully');
});