<?php
if (!defined('ABSPATH')) exit;

$token = get_query_var('pgc_edit_token');
$coupon = (new CityCoupons_Coupons())->get_coupon_by_token($token);

if (!$coupon) {
    wp_die('Купон не найден или уже удален.', 'Ошибка', ['response' => 404]);
}

// Создаем nonce для защиты формы
$edit_nonce = wp_create_nonce('city_coupons_edit_nonce');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать купон — <?php echo esc_html($coupon->store_name); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/style.css?v=<?php echo CITY_COUPONS_VERSION; ?>">
    <style>
        body.pgc-edit-page {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .wp-site-blocks {
            display: none;
        }
    </style>
</head>
<body class="pgc-edit-page">
    <div class="pgc-edit-container">
        <h2>✏️ Редактировать публикацию</h2>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="pgc-message success">
                <span style="font-size: 20px; margin-right: 10px;">✅</span>
                Изменения успешно сохранены!
            </div>
        <?php endif; ?>

        <form id="pgc-edit-form" enctype="multipart/form-data">
            <input type="hidden" name="edit_token" value="<?php echo esc_attr($coupon->edit_token); ?>">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($edit_nonce); ?>">

            <div class="pgc-form-group">
                <label>Текущее изображение:</label>
                <div style="text-align: center; margin: 15px 0;">
                    <img src="<?php echo esc_url($coupon->image_url); ?>" 
                         alt="Текущее изображение" 
                         style="max-width: 300px; max-height: 200px; border-radius: 8px; box-shadow: 0 3px 10px rgba(0,0,0,0.1);">
                </div>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-image">Заменить изображение (необязательно):</label>
                <input type="file" id="pgc-edit-image" name="image" accept="image/*">
                <small>Оставьте пустым, если не хотите менять. Макс. размер: 5 МБ</small>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-store-name">Название магазина *</label>
                <input type="text" id="pgc-edit-store-name" name="store_name" 
                       value="<?php echo esc_attr($coupon->store_name); ?>" required>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-title">Название акции *</label>
                <input type="text" id="pgc-edit-title" name="title" 
                       value="<?php echo esc_attr($coupon->title); ?>" required>
            </div>

            <div class="pgc-form-group">
                <label>Тип публикации *</label>
                <label style="display: block; margin: 10px 0;">
                    <input type="radio" name="coupon_type" value="coupon" 
                           <?php checked($coupon->coupon_type, 'coupon'); ?>> 
                    <span style="font-weight: 600; color: #667eea;">Купон</span> 
                    <small style="color: #666;">(можно скачать и предъявить)</small>
                </label>
                <label style="display: block; margin: 10px 0;">
                    <input type="radio" name="coupon_type" value="promotion" 
                           <?php checked($coupon->coupon_type, 'promotion'); ?>> 
                    <span style="font-weight: 600; color: #f5576c;">Акция</span> 
                    <small style="color: #666;">(просто описание)</small>
                </label>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-description">Описание</label>
                <textarea id="pgc-edit-description" name="description" rows="4"><?php echo esc_textarea($coupon->description); ?></textarea>
            </div>

            <button type="submit" class="pgc-submit-btn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                💾 Сохранить изменения
            </button>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="<?php echo home_url(); ?>" class="pgc-cancel-link">
                    ← Вернуться на главную
                </a>
            </div>
        </form>

        <hr>

        <h3 style="color: #f5576c; text-align: center;">Удалить публикацию</h3>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Это действие нельзя отменить. Изображение будет удалено с сервера.
        </p>
        <button id="pgc-delete-btn" class="pgc-submit-btn" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
            🗑️ Удалить навсегда
        </button>
    </div>

    <script>
    // Обработка формы редактирования
    document.getElementById('pgc-edit-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'pgc_update_coupon');
        
        const submitBtn = this.querySelector('.pgc-submit-btn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<span class="spinner"></span> Сохранение...';
        submitBtn.disabled = true;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.data.redirect;
            } else {
                alert('Ошибка: ' + data.data);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            alert('Ошибка сети. Попробуйте еще раз.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Обработка удаления
    document.getElementById('pgc-delete-btn').addEventListener('click', function() {
        if (!confirm('Вы уверены, что хотите удалить эту публикацию? Это действие нельзя отменить.')) {
            return;
        }
        
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<span class="spinner"></span> Удаление...';
        btn.disabled = true;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=pgc_delete_coupon&edit_token=<?php echo $coupon->edit_token; ?>&nonce=<?php echo $edit_nonce; ?>'
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('Публикация успешно удалена.');
                window.location.href = '<?php echo home_url(); ?>';
            } else {
                alert('Ошибка: ' + res.data);
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(() => {
            alert('Ошибка сети. Попробуйте еще раз.');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
    
    // Добавляем стиль для спиннера
    const style = document.createElement('style');
    style.textContent = `
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
    `;
    document.head.appendChild(style);
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>