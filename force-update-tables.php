<?php
/**
 * Скрипт для принудительного обновления структуры таблиц
 * Использование: ЗАЙДИТЕ НА СТРАНИЦУ /wp-admin/admin.php?page=force-update-tables
 */

// Прямой доступ запрещен
if (!defined('ABSPATH')) {
    exit;
}

// Добавляем страницу в админку
add_action('admin_menu', function() {
    add_submenu_page(
        'city-coupons',
        'Обновление таблиц',
        'Обновить таблицы',
        'manage_options',
        'force-update-tables',
        'city_coupons_force_update_page'
    );
});

function city_coupons_force_update_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Недостаточно прав');
    }
    
    global $wpdb;
    $updated = false;
    $errors = [];
    
    if (isset($_POST['force_update'])) {
        // Загружаем класс database
        require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
        $database = new CityCoupons_Database();
        
        try {
            // Принудительно обновляем таблицы
            $database->force_update_tables();
            $updated = true;
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    // Проверяем существование таблиц
    $tables = [
        'coupons' => $wpdb->prefix . 'city_coupons',
        'photos' => $wpdb->prefix . 'city_photos',
        'photo_likes' => $wpdb->prefix . 'city_photo_likes',
        'ad_categories' => $wpdb->prefix . 'city_ad_categories',
        'ads' => $wpdb->prefix . 'city_ads',
        'ad_photos' => $wpdb->prefix . 'city_ad_photos',
        'ad_favorites' => $wpdb->prefix . 'city_ad_favorites'
    ];
    
    $table_status = [];
    foreach ($tables as $key => $table) {
        $table_status[$key] = [
            'exists' => $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table,
            'name' => $table
        ];
        
        if ($table_status[$key]['exists']) {
            // Проверяем важные колонки
            switch ($key) {
                case 'ads':
                    $table_status[$key]['has_category_id'] = $wpdb->get_var("SHOW COLUMNS FROM $table LIKE 'category_id'") != null;
                    $table_status[$key]['rows'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    break;
                case 'ad_categories':
                    $table_status[$key]['rows'] = $wpdb->get_var("SELECT COUNT(*) FROM $table");
                    break;
            }
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Принудительное обновление таблиц</h1>
        
        <?php if ($updated): ?>
            <div class="notice notice-success is-dismissible">
                <p>✅ Таблицы успешно обновлены!</p>
            </div>
        <?php endif; ?>
        
        <?php foreach ($errors as $error): ?>
            <div class="notice notice-error is-dismissible">
                <p>❌ Ошибка: <?php echo esc_html($error); ?></p>
            </div>
        <?php endforeach; ?>
        
        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2>Статус таблиц</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Таблица</th>
                        <th>Статус</th>
                        <th>Информация</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($table_status as $key => $status): ?>
                        <tr>
                            <td><strong><?php echo esc_html($status['name']); ?></strong></td>
                            <td>
                                <?php if ($status['exists']): ?>
                                    <span style="color: green;">✅ Существует</span>
                                <?php else: ?>
                                    <span style="color: red;">❌ Отсутствует</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($status['exists']): ?>
                                    <?php if ($key == 'ads'): ?>
                                        <?php if ($status['has_category_id']): ?>
                                            <span style="color: green;">✅ Есть category_id</span>
                                        <?php else: ?>
                                            <span style="color: red;">❌ Нет category_id</span>
                                        <?php endif; ?>
                                        | Записей: <?php echo esc_html($status['rows']); ?>
                                    <?php elseif ($key == 'ad_categories'): ?>
                                        Категорий: <?php echo esc_html($status['rows']); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card" style="max-width: 800px; margin: 20px 0;">
            <h2>Действия</h2>
            
            <form method="post">
                <?php wp_nonce_field('force_update_tables', 'force_update_nonce'); ?>
                
                <p>
                    <strong>Внимание!</strong> Это действие пересоздаст структуру таблиц и добавит отсутствующие колонки.
                    Существующие данные не будут удалены.
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="confirm_backup" required>
                        Я понимаю, что делаю и создал резервную копию базы данных
                    </label>
                </p>
                
                <p>
                    <input type="submit" name="force_update" 
                           class="button button-primary button-large" 
                           value="🔄 Принудительно обновить таблицы"
                           onclick="return confirm('Вы уверены?')">
                </p>
            </form>
            
            <hr style="margin: 30px 0;">
            
            <h3>Ручные SQL запросы (если автоматическое не сработает)</h3>
            <textarea style="width: 100%; height: 300px; font-family: monospace; font-size: 12px;" 
                      readonly onclick="this.select()">
-- 1. Добавить category_id если нет
ALTER TABLE <?php echo $wpdb->prefix; ?>city_ads 
ADD COLUMN IF NOT EXISTS category_id INT(11) NOT NULL AFTER description;

-- 2. Добавить другие важные колонки
ALTER TABLE <?php echo $wpdb->prefix; ?>city_ads 
ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'руб.' AFTER price,
ADD COLUMN IF NOT EXISTS address VARCHAR(500) AFTER category_id,
ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(50) AFTER address,
ADD COLUMN IF NOT EXISTS contact_email VARCHAR(100) AFTER contact_phone,
ADD COLUMN IF NOT EXISTS contact_name VARCHAR(255) AFTER contact_email,
ADD COLUMN IF NOT EXISTS views_count INT DEFAULT 0 AFTER contact_name,
ADD COLUMN IF NOT EXISTS favorites_count INT DEFAULT 0 AFTER views_count,
ADD COLUMN IF NOT EXISTS edit_token CHAR(32) NOT NULL UNIQUE AFTER favorites_count,
ADD COLUMN IF NOT EXISTS status ENUM('pending','approved','rejected','sold') DEFAULT 'pending' AFTER edit_token,
ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) DEFAULT 0 AFTER status,
ADD COLUMN IF NOT EXISTS expires_at DATETIME NULL AFTER is_premium,
ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 3. Обновить существующие записи (если нужно)
UPDATE <?php echo $wpdb->prefix; ?>city_ads 
SET category_id = 10, status = 'pending' 
WHERE category_id = 0 OR category_id IS NULL;

-- 4. Создать индекс для category_id
ALTER TABLE <?php echo $wpdb->prefix; ?>city_ads 
ADD INDEX IF NOT EXISTS category_idx (category_id);
            </textarea>
            <p><small>Скопируйте эти запросы и выполните в phpMyAdmin</small></p>
        </div>
    </div>
    
    <style>
        .card {
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
    <?php
}