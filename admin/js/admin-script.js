jQuery(document).ready(function($) {
    // ========== КУПОНЫ ==========
    // Одобрение / Отклонение купонов
    $(document).on('click', '.pgc-approve-btn, .pgc-reject-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('coupon-id');
        var action = $btn.hasClass('pgc-approve-btn') ? 'approve' : 'reject';
        var actionText = action === 'approve' ? 'одобрить' : 'отклонить';
        
        if (!confirm('Вы уверены, что хотите ' + actionText + ' этот купон?')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Обработка...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_moderate_coupon',
            coupon_id: id,
            action_type: action,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': action === 'approve' ? '#d4edda' : '#f8d7da',
                    'transition': 'all 0.5s'
                });
                
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text(action === 'approve' ? 'Одобрить' : 'Отклонить');
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text(action === 'approve' ? 'Одобрить' : 'Отклонить');
        });
    });

    // Удаление купонов
    $(document).on('click', '.pgc-delete-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('coupon-id');
        
        if (!confirm('Удалить навсегда? Это действие нельзя отменить.')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Удаление...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_delete_coupon_admin',
            coupon_id: id,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': '#f8d7da',
                    'opacity': '0.5',
                    'transition': 'all 0.5s'
                }).slideUp(400, function() {
                    $(this).remove();
                    location.reload();
                });
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text('Удалить');
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text('Удалить');
        });
    });

    // ========== ФОТО ==========
    // Модерация фото
    $(document).on('click', '.pgc-approve-photo-btn, .pgc-reject-photo-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('photo-id');
        var action = $btn.hasClass('pgc-approve-photo-btn') ? 'approve' : 'reject';
        var actionText = action === 'approve' ? 'одобрить' : 'отклонить';
        
        if (!confirm('Вы уверены, что хотите ' + actionText + ' это фото?')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Обработка...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_moderate_photo',
            photo_id: id,
            action_type: action,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': action === 'approve' ? '#d4edda' : '#f8d7da',
                    'transition': 'all 0.5s'
                });
                
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text(action === 'approve' ? 'Одобрить' : 'Отклонить');
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text(action === 'approve' ? 'Одобрить' : 'Отклонить');
        });
    });

    // Удаление фото
    $(document).on('click', '.pgc-delete-photo-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('photo-id');
        
        if (!confirm('Удалить фото навсегда? Это действие нельзя отменить.')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Удаление...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_delete_photo_admin',
            photo_id: id,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': '#f8d7da',
                    'opacity': '0.5',
                    'transition': 'all 0.5s'
                }).slideUp(400, function() {
                    $(this).remove();
                    location.reload();
                });
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text('Удалить');
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text('Удалить');
        });
    });

    // ========== ОБЪЯВЛЕНИЯ ==========
    // Модерация объявлений
    $(document).on('click', '.pgc-approve-ad-btn, .pgc-reject-ad-btn, .pgc-mark-sold-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('ad-id');
        var action = $btn.hasClass('pgc-approve-ad-btn') ? 'approve' : 
                     $btn.hasClass('pgc-reject-ad-btn') ? 'reject' : 'sold';
        
        var actionText = {
            'approve': 'одобрить',
            'reject': 'отклонить', 
            'sold': 'отметить как проданное'
        }[action];
        
        if (!confirm('Вы уверены, что хотите ' + actionText + ' это объявление?')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Обработка...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_moderate_ad',
            ad_id: id,
            action_type: action,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                var bgColor = {
                    'approve': '#d4edda',
                    'reject': '#f8d7da',
                    'sold': '#fff3cd'
                }[action];
                
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': bgColor,
                    'transition': 'all 0.5s'
                });
                
                setTimeout(function() {
                    location.reload();
                }, 800);
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text({
                    'approve': 'Одобрить',
                    'reject': 'Отклонить',
                    'sold': 'Продано'
                }[action]);
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text({
                'approve': 'Одобрить',
                'reject': 'Отклонить',
                'sold': 'Продано'
            }[action]);
        });
    });

    // Удаление объявлений
    $(document).on('click', '.pgc-delete-ad-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var id = $btn.data('ad-id');
        
        if (!confirm('Удалить объявление со всеми фотографиями? Это действие нельзя отменить.')) return;
        
        $btn.prop('disabled', true).html('<span class="spinner is-active"></span> Удаление...');
        
        $.post(city_coupons_admin.ajax_url, {
            action: 'pgc_delete_ad_admin',
            ad_id: id,
            nonce: city_coupons_admin.nonce
        }, function(r) {
            if (r.success) {
                $btn.closest('.pgc-pending-item, tr').css({
                    'background': '#f8d7da',
                    'opacity': '0.5',
                    'transition': 'all 0.5s'
                }).slideUp(400, function() {
                    $(this).remove();
                    location.reload();
                });
            } else {
                alert('Ошибка: ' + r.data);
                $btn.prop('disabled', false).text('Удалить');
            }
        }).fail(function() {
            alert('Ошибка сети. Попробуйте еще раз.');
            $btn.prop('disabled', false).text('Удалить');
        });
    });
    
    // ========== ВСПОМОГАТЕЛЬНОЕ ==========
    // Подсказки при наведении
    $('.pgc-approve-btn').attr('title', 'Одобрить публикацию');
    $('.pgc-reject-btn').attr('title', 'Отклонить публикацию');
    $('.pgc-delete-btn').attr('title', 'Удалить навсегда');
    
    $('.pgc-approve-photo-btn').attr('title', 'Одобрить фото');
    $('.pgc-reject-photo-btn').attr('title', 'Отклонить фото');
    $('.pgc-delete-photo-btn').attr('title', 'Удалить фото навсегда');
    
    $('.pgc-approve-ad-btn').attr('title', 'Одобрить объявление');
    $('.pgc-reject-ad-btn').attr('title', 'Отклонить объявление');
    $('.pgc-mark-sold-btn').attr('title', 'Отметить как проданное');
    $('.pgc-delete-ad-btn').attr('title', 'Удалить объявление со всеми фото');
});