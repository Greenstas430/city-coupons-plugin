<?php
if (!defined('ABSPATH')) exit;

$token = get_query_var('pgc_ad_edit_token');
$ad = (new CityCoupons_Ads())->get_ad_by_token($token);
$photos = (new CityCoupons_Ads())->get_ad_photos($ad->id);
$categories = (new CityCoupons_Ads())->get_categories();

if (!$ad) {
    wp_die('Объявление не найдено или уже удалено.', 'Ошибка', ['response' => 404]);
}

$edit_nonce = wp_create_nonce('city_ads_edit_nonce');
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактировать объявление — <?php echo esc_html($ad->title); ?> | <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/style.css?v=<?php echo CITY_COUPONS_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/slick.css?v=1.8.1">
    <link rel="stylesheet" href="<?php echo CITY_COUPONS_URL; ?>public/css/slick-theme.css?v=1.8.1">
    <style>
        body.pgc-edit-page {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .wp-site-blocks {
            display: none;
        }
        .pgc-edit-photos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .pgc-edit-photo-item {
            position: relative;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .pgc-edit-photo-item img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            display: block;
        }
        .pgc-edit-photo-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
        }
        .pgc-edit-photo-checkbox {
            position: absolute;
            top: 5px;
            left: 5px;
        }
        .pgc-edit-photo-item.main-photo {
            border-color: #667eea;
            border-width: 3px;
        }
        .pgc-edit-photo-item.main-photo::after {
            content: 'Главное';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(102, 126, 234, 0.9);
            color: white;
            text-align: center;
            font-size: 12px;
            padding: 3px;
            font-weight: bold;
        }
    </style>
</head>
<body class="pgc-edit-page">
    <div class="pgc-edit-container" style="max-width: 800px;">
        <h2>✏️ Редактировать объявление</h2>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <div class="pgc-message success">
                <span style="font-size: 20px; margin-right: 10px;">✅</span>
                Изменения успешно сохранены!
            </div>
        <?php endif; ?>

        <form id="pgc-edit-ad-form" enctype="multipart/form-data">
            <input type="hidden" name="edit_token" value="<?php echo esc_attr($ad->edit_token); ?>">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($edit_nonce); ?>">
            <input type="hidden" name="delete_photos" id="pgc-delete-photos" value="">

            <div class="pgc-form-group">
                <label>Текущие фотографии:</label>
                <?php if ($photos): ?>
                    <div class="pgc-edit-photos-grid">
                        <?php foreach ($photos as $photo): ?>
                            <div class="pgc-edit-photo-item <?php echo $photo->is_main ? 'main-photo' : ''; ?>" 
                                 data-photo-id="<?php echo esc_attr($photo->id); ?>">
                                <img src="<?php echo esc_url($photo->image_url); ?>" 
                                     alt="Фото объявления">
                                <div class="pgc-edit-photo-actions">
                                    <button type="button" class="pgc-set-main-btn" 
                                            data-photo-id="<?php echo esc_attr($photo->id); ?>"
                                            title="Сделать главной"
                                            style="background: #667eea; color: white; border: none; border-radius: 4px; padding: 4px 8px; cursor: pointer; font-size: 12px;">
                                        ★
                                    </button>
                                </div>
                                <input type="checkbox" class="pgc-edit-photo-checkbox" 
                                       data-photo-id="<?php echo esc_attr($photo->id); ?>"
                                       style="display: none;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size: 14px; color: #666; margin-top: 10px;">
                        <span style="color: #dc3232;">Внимание:</span> Отметьте фотографии для удаления (появятся после клика на фото).
                        Чтобы сделать фото главной — нажмите на звездочку ★.
                    </p>
                <?php else: ?>
                    <p style="color: #718096; font-style: italic;">Нет фотографий</p>
                <?php endif; ?>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-new-photos">Добавить новые фотографии:</label>
                <input type="file" id="pgc-edit-new-photos" name="new_photos[]" 
                       accept="image/*" multiple>
                <small>Максимум 10 фотографий всего. Каждая до 5 МБ</small>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-ad-title">
                    <span style="color: #dc3232;">*</span> Название объявления
                </label>
                <input type="text" id="pgc-edit-ad-title" name="title" 
                       value="<?php echo esc_attr($ad->title); ?>" required>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-ad-category">
                    <span style="color: #dc3232;">*</span> Категория
                </label>
                <select id="pgc-edit-ad-category" name="category_id" required>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->id); ?>" 
                            <?php selected($ad->category_id, $cat->id); ?>>
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-ad-description">Подробное описание</label>
                <textarea id="pgc-edit-ad-description" name="description" rows="6"><?php echo esc_textarea($ad->description); ?></textarea>
            </div>

            <div class="pgc-form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-edit-ad-price">Цена</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pgc-edit-ad-price" name="price" 
                               value="<?php echo esc_attr($ad->price); ?>"
                               placeholder="Договорная">
                        <select name="currency" style="flex: 1;">
                            <option value="руб." <?php selected($ad->currency, 'руб.'); ?>>руб.</option>
                            <option value="$" <?php selected($ad->currency, '$'); ?>>$</option>
                            <option value="€" <?php selected($ad->currency, '€'); ?>>€</option>
                            <option value="тенге" <?php selected($ad->currency, 'тенге'); ?>>тенге</option>
                            <option value="договорная" <?php selected($ad->currency, 'договорная'); ?>>договорная</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-ad-address">Адрес</label>
                <input type="text" id="pgc-edit-ad-address" name="address" 
                       value="<?php echo esc_attr($ad->address); ?>">
            </div>

            <div class="pgc-form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-edit-contact-name">
                        <span style="color: #dc3232;">*</span> Ваше имя
                    </label>
                    <input type="text" id="pgc-edit-contact-name" name="contact_name" 
                           value="<?php echo esc_attr($ad->contact_name); ?>" required>
                </div>
                
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-edit-contact-phone">
                        <span style="color: #dc3232;">*</span> Телефон
                    </label>
                    <input type="tel" id="pgc-edit-contact-phone" name="contact_phone" 
                           value="<?php echo esc_attr($ad->contact_phone); ?>" required>
                </div>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-edit-contact-email">Email</label>
                <input type="email" id="pgc-edit-contact-email" name="contact_email" 
                       value="<?php echo esc_attr($ad->contact_email); ?>">
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

        <h3 style="color: #f5576c; text-align: center;">Удалить объявление</h3>
        <p style="text-align: center; color: #666; margin-bottom: 20px;">
            Это действие нельзя отменить. Все фотографии будут удалены с сервера.
        </p>
        <button id="pgc-delete-ad-btn" class="pgc-submit-btn" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%);">
            🗑️ Удалить навсегда
        </button>
    </div>

    <script src="<?php echo CITY_COUPONS_URL; ?>public/js/slick.min.js?v=1.8.1"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('pgc-edit-ad-form');
        const deletePhotosInput = document.getElementById('pgc-delete-photos');
        const deletePhotoIds = new Set();
        
        // Управление фотографиями
        document.querySelectorAll('.pgc-edit-photo-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (e.target.classList.contains('pgc-set-main-btn') || 
                    e.target.classList.contains('pgc-edit-photo-checkbox')) {
                    return;
                }
                
                const checkbox = this.querySelector('.pgc-edit-photo-checkbox');
                const photoId = this.dataset.photoId;
                
                if (checkbox.checked) {
                    checkbox.checked = false;
                    checkbox.style.display = 'none';
                    deletePhotoIds.delete(photoId);
                    this.style.opacity = '1';
                } else {
                    checkbox.checked = true;
                    checkbox.style.display = 'block';
                    deletePhotoIds.add(photoId);
                    this.style.opacity = '0.5';
                }
                
                deletePhotosInput.value = Array.from(deletePhotoIds).join(',');
            });
        });
        
        // Сделать фото главной
        document.querySelectorAll('.pgc-set-main-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const photoId = this.dataset.photoId;
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=pgc_set_main_photo&photo_id=${photoId}&nonce=<?php echo $edit_nonce; ?>`
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        // Обновляем отображение
                        document.querySelectorAll('.pgc-edit-photo-item').forEach(item => {
                            item.classList.remove('main-photo');
                        });
                        document.querySelector(`[data-photo-id="${photoId}"]`).classList.add('main-photo');
                        alert('Главное фото обновлено!');
                    } else {
                        alert('Ошибка: ' + res.data);
                    }
                })
                .catch(() => {
                    alert('Ошибка сети');
                });
            });
        });
        
        // Отправка формы
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'pgc_update_ad');
            
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
        
        // Удаление объявления
        document.getElementById('pgc-delete-ad-btn').addEventListener('click', function() {
            if (!confirm('Вы уверены, что хотите удалить это объявление? Это действие нельзя отменить.')) {
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
                body: 'action=pgc_delete_ad&edit_token=<?php echo $ad->edit_token; ?>&nonce=<?php echo $edit_nonce; ?>'
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('Объявление успешно удалено.');
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
        
        // Стиль для спиннера
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
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>