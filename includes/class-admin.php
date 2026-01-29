<?php
class CityCoupons_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // AJAX обработчики для купонов
        add_action('wp_ajax_pgc_moderate_coupon', [$this, 'moderate_coupon']);
        add_action('wp_ajax_pgc_delete_coupon_admin', [$this, 'delete_coupon']);
        
        // AJAX обработчики для фото
        add_action('wp_ajax_pgc_moderate_photo', [$this, 'moderate_photo']);
        add_action('wp_ajax_pgc_delete_photo_admin', [$this, 'delete_photo']);
        
        // AJAX обработчики для объявлений
        add_action('wp_ajax_pgc_moderate_ad', [$this, 'moderate_ad']);
        add_action('wp_ajax_pgc_delete_ad_admin', [$this, 'delete_ad']);
        add_action('wp_ajax_pgc_set_main_photo', [$this, 'set_main_photo']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Купоны, Фото и Объявления',
            'Купоны и Фото',
            'manage_options',
            'city-coupons',
            [$this, 'display_admin_page'],
            'dashicons-tickets',
            31
        );
    }

    public function display_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Недостаточно прав');
        }
        include CITY_COUPONS_DIR . 'admin/admin-page.php';
    }

    // ========== МЕТОДЫ ДЛЯ КУПОНОВ ==========
    public function moderate_coupon() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        if (!$coupon_id || !in_array($action_type, ['approve', 'reject'])) {
            wp_send_json_error('Неверные параметры');
        }

        global $wpdb;
        $table = $wpdb->prefix . CITY_COUPONS_TABLE;
        $new_status = $action_type === 'approve' ? 'approved' : 'rejected';

        $result = $wpdb->update(
            $table,
            ['status' => $new_status],
            ['id' => $coupon_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            $this->log_action($coupon_id, "coupon_{$action_type}", get_current_user_id());
            $result_text = ($action_type === 'approve') ? 'одобрен' : 'отклонён';
            wp_send_json_success("Купон {$result_text}");
        } else {
            wp_send_json_error('Ошибка БД: ' . $wpdb->last_error);
        }
    }

    public function delete_coupon() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        if (!$coupon_id) {
            wp_send_json_error('Нет ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . CITY_COUPONS_TABLE;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $coupon_id));

        if ($coupon) {
            $this->safe_delete_file($coupon->image_url);
            $this->log_action($coupon_id, 'coupon_delete', get_current_user_id());
            $wpdb->delete($table, ['id' => $coupon_id], ['%d']);
            wp_send_json_success('Удалено');
        } else {
            wp_send_json_error('Не найдено');
        }
    }

    // ========== МЕТОДЫ ДЛЯ ФОТО ==========
    public function moderate_photo() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $photo_id = intval($_POST['photo_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        if (!$photo_id || !in_array($action_type, ['approve', 'reject'])) {
            wp_send_json_error('Неверные параметры');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $new_status = $action_type === 'approve' ? 'approved' : 'rejected';

        $result = $wpdb->update(
            $table,
            ['status' => $new_status],
            ['id' => $photo_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            $this->log_action($photo_id, "photo_{$action_type}", get_current_user_id());
            $result_text = ($action_type === 'approve') ? 'одобрена' : 'отклонена';
            wp_send_json_success("Фотография {$result_text}");
        } else {
            wp_send_json_error('Ошибка БД: ' . $wpdb->last_error);
        }
    }

    public function delete_photo() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $photo_id = intval($_POST['photo_id'] ?? 0);
        if (!$photo_id) {
            wp_send_json_error('Нет ID');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $photo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $photo_id));

        if ($photo) {
            $this->safe_delete_file($photo->image_url);
            $this->log_action($photo_id, 'photo_delete', get_current_user_id());
            $wpdb->delete($table, ['id' => $photo_id], ['%d']);
            wp_send_json_success('Фото удалено');
        } else {
            wp_send_json_error('Не найдено');
        }
    }

    // ========== МЕТОДЫ ДЛЯ ОБЪЯВЛЕНИЙ ==========
    public function moderate_ad() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $ad_id = intval($_POST['ad_id'] ?? 0);
        $action_type = sanitize_text_field($_POST['action_type'] ?? '');

        if (!$ad_id || !in_array($action_type, ['approve', 'reject', 'sold'])) {
            wp_send_json_error('Неверные параметры');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'city_ads';
        $new_status = $action_type === 'approve' ? 'approved' : 
                      ($action_type === 'reject' ? 'rejected' : 'sold');

        $result = $wpdb->update(
            $table,
            ['status' => $new_status],
            ['id' => $ad_id],
            ['%s'],
            ['%d']
        );

        if ($result !== false) {
            $this->log_action($ad_id, "ad_{$action_type}", get_current_user_id());
            $result_text = match($action_type) {
                'approve' => 'одобрено',
                'reject' => 'отклонено',
                'sold' => 'отмечено как проданное',
                default => 'обновлено'
            };
            wp_send_json_success("Объявление {$result_text}");
        } else {
            wp_send_json_error('Ошибка БД: ' . $wpdb->last_error);
        }
    }

    public function delete_ad() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $ad_id = intval($_POST['ad_id'] ?? 0);
        if (!$ad_id) {
            wp_send_json_error('Нет ID');
        }

        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $table_photos = $wpdb->prefix . 'city_ad_photos';
        
        $ad = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_ads WHERE id = %d", $ad_id));

        if ($ad) {
            // Удаляем фото
            $photos = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_photos WHERE ad_id = %d", $ad_id));
            $upload_dir = wp_upload_dir();
            
            foreach ($photos as $photo) {
                $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo->image_url);
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
            
            $this->log_action($ad_id, 'ad_delete', get_current_user_id());
            
            // Удаляем из БД
            $wpdb->delete($table_ads, ['id' => $ad_id]);
            wp_send_json_success('Объявление удалено');
        } else {
            wp_send_json_error('Не найдено');
        }
    }

    public function set_main_photo() {
        check_ajax_referer('city_coupons_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Неавторизовано');
        }

        $photo_id = intval($_POST['photo_id'] ?? 0);
        if (!$photo_id) {
            wp_send_json_error('Нет ID фото');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_photos';
        
        // Получаем информацию о фото
        $photo = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $photo_id));
        if (!$photo) {
            wp_send_json_error('Фото не найдено');
        }
        
        // Снимаем отметку с текущего главного фото
        $wpdb->update(
            $table,
            ['is_main' => 0],
            ['ad_id' => $photo->ad_id, 'is_main' => 1],
            ['%d'],
            ['%d', '%d']
        );
        
        // Устанавливаем новое главное фото
        $result = $wpdb->update(
            $table,
            ['is_main' => 1],
            ['id' => $photo_id],
            ['%d'],
            ['%d']
        );

        if ($result !== false) {
            wp_send_json_success('Главное фото обновлено');
        } else {
            wp_send_json_error('Ошибка БД: ' . $wpdb->last_error);
        }
    }

    // ========== ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ ==========
    private function safe_delete_file($image_url) {
        if (empty($image_url)) return;
        
        $upload_dir = wp_upload_dir();
        $baseurl = $upload_dir['baseurl'];
        $basedir = $upload_dir['basedir'];
        
        $possible_paths = [
            str_replace($baseurl, $basedir, $image_url),
            str_replace(home_url('/wp-content/uploads'), $basedir, $image_url),
            str_replace(site_url('/wp-content/uploads'), $basedir, $image_url),
            $basedir . '/city-coupons/' . basename($image_url),
            $basedir . '/city-photos/' . basename($image_url),
            $basedir . '/city-ads/' . basename($image_url)
        ];
        
        foreach ($possible_paths as $filepath) {
            if (file_exists($filepath) && is_file($filepath) && strpos($filepath, $basedir) === 0) {
                @unlink($filepath);
                break;
            }
        }
    }
    
    private function log_action($item_id, $action, $user_id) {
        $logs = get_option('city_coupons_logs', []);
        $logs[] = [
            'time' => current_time('mysql'),
            'item_id' => $item_id,
            'action' => $action,
            'user_id' => $user_id
        ];
        if (count($logs) > 100) {
            array_shift($logs);
        }
        update_option('city_coupons_logs', $logs);
    }

    // ========== ПОЛУЧЕНИЕ ДАННЫХ ДЛЯ АДМИНКИ ==========
    public function get_pending_coupons() {
        global $wpdb;
        $table = $wpdb->prefix . CITY_COUPONS_TABLE;
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY created_at DESC");
    }

    public function get_all_coupons($limit = 50) {
        global $wpdb;
        $limit = intval($limit);
        $table = $wpdb->prefix . CITY_COUPONS_TABLE;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public function get_pending_photos() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY created_at DESC");
    }

    public function get_all_photos($limit = 50) {
        global $wpdb;
        $limit = intval($limit);
        $table = $wpdb->prefix . 'city_photos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public function get_pending_ads() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ads';
        return $wpdb->get_results("SELECT * FROM $table WHERE status = 'pending' ORDER BY created_at DESC");
    }

    public function get_all_ads($limit = 30) {
        global $wpdb;
        $limit = intval($limit);
        $table = $wpdb->prefix . 'city_ads';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public function get_ad_photos($ad_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_photos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ad_id = %d ORDER BY is_main DESC, sort_order",
            $ad_id
        ));
    }

    public function get_category_name($category_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_categories';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT name FROM $table WHERE id = %d",
            $category_id
        )) ?: 'Без категории';
    }
    
    public function get_stats() {
        global $wpdb;
        $table_coupons = $wpdb->prefix . CITY_COUPONS_TABLE;
        $table_photos = $wpdb->prefix . 'city_photos';
        $table_ads = $wpdb->prefix . 'city_ads';
        
        $coupon_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_coupons,
                SUM(status = 'pending') as pending_coupons,
                SUM(status = 'approved') as approved_coupons,
                SUM(status = 'rejected') as rejected_coupons
            FROM $table_coupons
        ");
        
        $photo_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_photos,
                SUM(status = 'pending') as pending_photos,
                SUM(status = 'approved') as approved_photos,
                SUM(status = 'rejected') as rejected_photos
            FROM $table_photos
        ");
        
        $ad_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_ads,
                SUM(status = 'pending') as pending_ads,
                SUM(status = 'approved') as approved_ads,
                SUM(status = 'rejected') as rejected_ads,
                SUM(status = 'sold') as sold_ads
            FROM $table_ads
        ");
        
        // Объединяем статистику
        return (object) array_merge(
            (array) $coupon_stats, 
            (array) $photo_stats,
            (array) $ad_stats
        );
    }
}
?>