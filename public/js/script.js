jQuery(document).ready(function($) {
    // === ГЛОБАЛЬНЫЕ ПЕРЕМЕННЫЕ ===
    let currentModal = null;
    
    // === ОТКРЫТИЕ МОДАЛЬНОГО ОКНА ЗАГРУЗКИ ===
    $(document).on('click', '#pgc-open-upload-modal', function(e) {
        e.preventDefault();
        e.stopPropagation();
        closeAllModals();
        $('#pgc-coupon-upload-modal').fadeIn(300);
        currentModal = '#pgc-coupon-upload-modal';
        lockBodyScroll(true);
    });
    
    // === ФУНКЦИЯ ЗАКРЫТИЯ ВСЕХ МОДАЛОК ===
    function closeAllModals() {
        $('.pgc-upload-modal').fadeOut(300);
        currentModal = null;
        lockBodyScroll(false);
    }
    
    // === БЛОКИРОВКА СКРОЛЛА ===
    function lockBodyScroll(lock) {
        if (lock) {
            $('body').css('overflow', 'hidden');
        } else {
            $('body').css('overflow', '');
        }
    }
    
    // === ОТКРЫТИЕ МОДАЛЬНОГО ОКНА ПРОСМОТРА КУПОНА ===
    $(document).on('click', '.pgc-coupon-item.coupon-type', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if ($(e.target).closest('a').length || $(e.target).is('a')) {
            return;
        }
        
        const $item = $(this);
        const imgSrc = $item.find('img').attr('src');
        const storeName = $item.find('.pgc-coupon-info h4').text().trim();
        const title = $item.find('.pgc-coupon-info p strong').text().trim();
        const description = $item.find('.pgc-description').text().trim() || '';
        const filename = 'купон_' + storeName.replace(/[^a-zа-яё0-9]/gi, '_') + '.jpg';
        
        closeAllModals();
        
        const modalHtml = `
            <div class="pgc-upload-modal" id="pgc-coupon-view-modal">
                <div class="pgc-modal-content">
                    <span class="pgc-close-modal">&times;</span>
                    <h2>Ваш купон</h2>
                    <div class="pgc-view-coupon-content">
                        <img src="${imgSrc}" alt="Купон">
                        <div style="text-align: left; margin-bottom: 20px;">
                            <p style="font-size: 18px; font-weight: 600; color: #2d3748; margin-bottom: 8px;">${storeName}</p>
                            <p style="color: #4a5568; margin-bottom: 15px;">${title}</p>
                            ${description ? `<p style="color: #718096; font-size: 15px;">${description}</p>` : ''}
                        </div>
                        <p class="pgc-instruction">✅ Сохраните купон, чтобы показать в магазине</p>
                        <button class="pgc-download-btn" data-url="${imgSrc}" data-filename="${filename}">
                            📥 Скачать купон
                        </button>
                        <button class="pgc-submit-btn" style="background: #718096; margin-top: 10px;" onclick="closeAllModals()">
                            Закрыть
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#pgc-coupon-view-modal').fadeIn(300);
        currentModal = '#pgc-coupon-view-modal';
        lockBodyScroll(true);
    });
    
    // === ЗАКРЫТИЕ ЛЮБЫХ МОДАЛОК ===
    $(document).on('click', function(e) {
        // Закрытие по клику на крестик
        if ($(e.target).hasClass('pgc-close-modal')) {
            closeAllModals();
            return;
        }
        
        // Закрытие по клику вне модалки
        if ($(e.target).hasClass('pgc-upload-modal')) {
            closeAllModals();
        }
    });
    
    // === СКАЧИВАНИЕ ИЗ МОДАЛЬНОГО ПРОСМОТРА ===
    $(document).on('click', '.pgc-download-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const url = $(this).data('url');
        const filename = $(this).data('filename') || 'coupon.jpg';
        
        // Создаем временную ссылку для скачивания
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        // Показываем сообщение об успехе
        $(this).html('✅ Скачано!');
        $(this).css('background', '#4CAF50');
        
        setTimeout(() => {
            $(this).html('📥 Скачать купон');
            $(this).css('background', '');
        }, 2000);
    });
    
    // === ОТПРАВКА ФОРМЫ ЗАГРУЗКИ ===
    $(document).on('submit', '#pgc-coupon-upload-form', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const formData = new FormData(this);
        formData.append('action', 'city_coupons_upload');
        const $btn = $('.pgc-submit-btn', this);
        const $msg = $('#pgc-response-message', this);
        
        // Проверка обязательных полей
        const storeName = $('#pgc-store-name').val().trim();
        const title = $('#pgc-title').val().trim();
        const image = $('#pgc-image')[0].files[0];
        const couponType = $('input[name="coupon_type"]:checked').val();
        
        if (!storeName || !title || !image || !couponType) {
            $msg.removeClass('success').addClass('error')
                .html('❌ Заполните все обязательные поля').show();
            return;
        }
        
        $btn.prop('disabled', true).html('<span class="spinner"></span> Отправка...');
        
        $.ajax({
            url: city_coupons_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    const editUrl = response.data.edit_url;
                    
                    // Красивое сообщение об успехе
                    let message = `
                        <div style="text-align: center;">
                            <div style="font-size: 48px; margin-bottom: 15px;">🎉</div>
                            <h3 style="margin: 0 0 10px; color: #155724;">Успешно отправлено!</h3>
                            <p style="margin-bottom: 20px;">Ваша публикация отправлена на модерацию.</p>
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 20px 0;">
                                <p style="margin: 0 0 10px; font-weight: 600;">📌 Сохраните эту ссылку:</p>
                                <input type="text" value="${editUrl}" readonly 
                                    style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; background: white;"
                                    onclick="this.select()">
                                <p style="margin: 10px 0 0; font-size: 13px; color: #666;">
                                    По ней вы сможете редактировать или удалить купон
                                </p>
                            </div>
                        </div>
                    `;
                    
                    $msg.removeClass('error').addClass('success').html(message).show();
                    
                    // Очищаем форму
                    document.getElementById('pgc-coupon-upload-form').reset();
                    
                    // Автоматически закрываем через 8 секунд
                    setTimeout(function() {
                        closeAllModals();
                    }, 8000);
                    
                    // Показываем таймер
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
                $msg.removeClass('success').addClass('error')
                    .html('❌ Ошибка соединения. Проверьте интернет и попробуйте снова.').show();
                $btn.prop('disabled', false).text('Отправить на модерацию');
                console.error('AJAX Error:', error);
            }
        });
    });
    
    // === ESC ДЛЯ ЗАКРЫТИЯ МОДАЛКИ ===
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && currentModal) {
            closeAllModals();
        }
    });
    
    // === СПИННЕР ===
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
        </style>
    `);
    
    // Глобальная функция для закрытия из других мест
    window.closeAllModals = closeAllModals;
});