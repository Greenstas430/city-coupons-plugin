jQuery(document).ready(function($) {
    // === ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ===
    let currentModal = null;
    
    // === ОТКРЫТИЕ МОДАЛЬНОГО ОКНА ЗАГРУЗКИ ФОТО ===
    $(document).on('click', '#pgc-open-photo-upload-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeAllModals();
        $('#pgc-photo-upload-modal').fadeIn(300);
        currentModal = '#pgc-photo-upload-modal';
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

    // Закрытие по клику на крестик
    $(document).on('click', '.pgc-close-modal', function() {
        closeAllModals();
    });

    // Закрытие по клику вне модалки
    $(document).on('click', function(e) {
        if ($(e.target).hasClass('pgc-upload-modal')) {
            closeAllModals();
        }
    });

    // === ЛАЙКИ ===
    $(document).on('click', '.pgc-like-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const $btn = $(this);
        const photoId = $btn.data('photo-id');
        const $heart = $btn.find('.pgc-heart');
        const $count = $btn.find('.pgc-likes-count');
        
        $.post(city_photos_ajax.ajax_url, {
            action: 'pgc_toggle_like',
            photo_id: photoId,
            nonce: city_photos_ajax.nonce
        }, function(response) {
            if (response.success) {
                const liked = response.data.liked;
                const newCount = response.data.likes_count;
                
                $btn.toggleClass('liked', liked);
                $heart.toggleClass('liked', liked);
                $count.text(newCount);
                
                if (liked) {
                    $heart.addClass('animate');
                    setTimeout(() => {
                        $heart.removeClass('animate');
                    }, 800);
                }
            } else {
                console.error('Ошибка лайка:', response.data);
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
        });
    });

    // === ОТПРАВКА ФОРМЫ ЗАГРУЗКИ ФОТО ===
    $(document).on('submit', '#pgc-photo-upload-form', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(this);
        formData.append('action', 'city_photos_upload');
        const $btn = $('.pgc-submit-btn', this);
        const $msg = $('#pgc-photo-response-message', this);
        
        // Проверка обязательных полей
        const photographerName = $('#pgc-photographer-name').val().trim();
        const image = $('#pgc-photo-image')[0].files[0];
        
        if (!photographerName || !image) {
            $msg.removeClass('success').addClass('error')
                .html('❌ Заполните все обязательные поля').show();
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner"></span> Отправка...');
        
        $.ajax({
            url: city_photos_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    const editUrl = response.data.edit_url;
                    
                    let message = `
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🎉</div>
                            <h3 style="margin: 0 0 10px; color: #155724;">Фото отправлено на модерацию!</h3>
                            <p style="margin-bottom: 20px;">После проверки оно появится в галерее.</p>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                                <p style="margin: 0 0 10px; font-weight: 600;">📌 Сохраните эту ссылку:</p>
                                <input type="text" value="${editUrl}" readonly 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: white;"
                                    onclick="this.select()">
                                <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                                    По ней вы сможете редактировать или удалить фото
                                </p>
                            </div>
                        </div>
                    `;
                    
                    $msg.removeClass('error').addClass('success').html(message).show();
                    document.getElementById('pgc-photo-upload-form').reset();
                    
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
            error: function() {
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

    // === ПРОСМОТР ФОТО В МОДАЛЬНОМ ОКНЕ ===
    $(document).on('click', '.pgc-photo-item img', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if ($(e.target).closest('.pgc-like-btn').length) {
            return; // Не открываем модалку при клике на лайк
        }
        
        const $photoItem = $(this).closest('.pgc-photo-item');
        const imgSrc = $(this).attr('src');
        const photographer = $photoItem.find('.pgc-photo-info h4').text().trim();
        const description = $photoItem.find('.pgc-photo-description').text().trim() || '';
        const likesCount = $photoItem.find('.pgc-likes-count').text().trim();
        const photoId = $photoItem.data('photo-id');
        const currentIp = '<?php echo (new CityCoupons_Photos())->get_client_ip(); ?>';
        
        closeAllModals();
        
        const modalHtml = `
            <div class="pgc-upload-modal" id="pgc-view-photo-modal">
                <div class="pgc-modal-content">
                    <span class="pgc-close-modal">&times;</span>
                    <div class="pgc-view-photo-content">
                        <img src="${imgSrc}" alt="Фото">
                        <div class="pgc-view-photo-info">
                            <h4>${photographer}</h4>
                            ${description ? `<p class="pgc-view-photo-description">${description}</p>` : ''}
                            <div class="pgc-view-photo-stats">
                                <button class="pgc-like-btn" data-photo-id="${photoId}">
                                    <span class="pgc-heart"></span>
                                    <span class="pgc-likes-count">${likesCount}</span>
                                </button>
                                <button class="pgc-submit-btn" style="background: #718096; padding: 8px 16px;" onclick="closeAllModals()">
                                    Закрыть
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#pgc-view-photo-modal').fadeIn(300);
        currentModal = '#pgc-view-photo-modal';
        lockBodyScroll(true);
        
        // Инициализируем лайк в модалке
        setTimeout(() => {
            const $modalLikeBtn = $('#pgc-view-photo-modal .pgc-like-btn');
            if ($modalLikeBtn.length) {
                // Здесь можно добавить проверку, лайкнул ли уже пользователь это фото
            }
        }, 100);
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
            .pgc-heart.animate {
                animation: heartBeat 0.8s ease;
            }
        </style>
    `);
    
    // Делаем функции глобальными для доступа из других скриптов
    window.currentModal = null;
    window.closeAllModals = closeAllModals;
    window.lockBodyScroll = lockBodyScroll;
});