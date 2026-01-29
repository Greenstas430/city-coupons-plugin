<?php
class CityCoupons_Ads {
    public function __construct() {
        add_action('wp_ajax_city_ads_upload', [$this, 'handle_upload']);
        add_action('wp_ajax_nopriv_city_ads_upload', [$this, 'handle_upload']);
        add_action('wp_ajax_pgc_toggle_favorite', [$this, 'handle_toggle_favorite']);
        add_action('wp_ajax_nopriv_pgc_toggle_favorite', [$this, 'handle_toggle_favorite']);
        add_action('wp_ajax_pgc_update_ad', [$this, 'handle_update']);
        add_action('wp_ajax_nopriv_pgc_update_ad', [$this, 'handle_update']);
        add_action('wp_ajax_pgc_delete_ad', [$this, 'handle_delete']);
        add_action('wp_ajax_nopriv_pgc_delete_ad', [$this, 'handle_delete']);
        
        // Увеличиваем счетчик просмотров
        add_action('wp', [$this, 'track_ad_view']);
    }

    public function handle_upload() {
        // ВРЕМЕННО для отладки - закомментируйте проверку nonce
        // if (!wp_verify_nonce($_POST['nonce'], 'city_ads_ajax_nonce')) {
        //     wp_send_json_error('Проверка безопасности не пройдена');
        // }
        
        // Для отладки пишем в лог
        error_log('=== AD UPLOAD START ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data count: ' . (isset($_FILES['photos']) ? count($_FILES['photos']['name']) : 0));

        // Проверяем обязательные поля
        $required_fields = ['title', 'category_id', 'contact_name', 'contact_phone'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                wp_send_json_error("Заполните поле: " . $this->get_field_label($field));
            }
        }

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'price' => $this->sanitize_price($_POST['price'] ?? ''),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'руб.'),
            'category_id' => intval($_POST['category_id']),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'contact_phone' => sanitize_text_field($_POST['contact_phone']),
            'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
            'contact_name' => sanitize_text_field($_POST['contact_name']),
            'status' => 'pending'
        ];

        // Проверяем фото
        if (empty($_FILES['photos']['name'][0])) {
            wp_send_json_error('Добавьте хотя бы одно фото');
        }

        $photos_count = count($_FILES['photos']['name']);
        if ($photos_count > 10) {
            wp_send_json_error('Максимум 10 фотографий');
        }

        // Создаем объявление
        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $data['edit_token'] = bin2hex(random_bytes(16));
        
        $wpdb->insert($table_ads, $data);
        $ad_id = $wpdb->insert_id;
        
        error_log('Ad created with ID: ' . $ad_id);

        // Обрабатываем фото
        $upload_dir = wp_upload_dir();
        $ads_dir = $upload_dir['basedir'] . '/city-ads/';
        if (!file_exists($ads_dir)) {
            wp_mkdir_p($ads_dir);
        }

        $table_photos = $wpdb->prefix . 'city_ad_photos';
        $uploaded_photos = 0;

        for ($i = 0; $i < $photos_count; $i++) {
            if ($_FILES['photos']['error'][$i] !== UPLOAD_ERR_OK) {
                error_log('Photo upload error: ' . $_FILES['photos']['error'][$i]);
                continue;
            }

            // Проверяем тип файла
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = wp_check_filetype($_FILES['photos']['name'][$i]);
            if (!in_array($file_type['type'], $allowed_types)) {
                error_log('Invalid file type: ' . $file_type['type']);
                continue;
            }

            // Проверяем размер (макс 5 МБ на фото)
            if ($_FILES['photos']['size'][$i] > 5 * 1024 * 1024) {
                error_log('File too large: ' . $_FILES['photos']['size'][$i]);
                continue;
            }

            $filename = uniqid() . '_' . sanitize_file_name($_FILES['photos']['name'][$i]);
            $filepath = $ads_dir . $filename;

            if (move_uploaded_file($_FILES['photos']['tmp_name'][$i], $filepath)) {
                $this->optimize_image($filepath);
                $image_url = $upload_dir['baseurl'] . '/city-ads/' . $filename;

                $photo_data = [
                    'ad_id' => $ad_id,
                    'image_url' => $image_url,
                    'sort_order' => $i,
                    'is_main' => ($i === 0) ? 1 : 0
                ];

                $wpdb->insert($table_photos, $photo_data);
                $uploaded_photos++;
                error_log('Photo uploaded: ' . $filename);
            } else {
                error_log('Failed to move uploaded file: ' . $_FILES['photos']['name'][$i]);
            }
        }

        if ($uploaded_photos === 0) {
            // Если ни одно фото не загрузилось, удаляем объявление
            $wpdb->delete($table_ads, ['id' => $ad_id]);
            wp_send_json_error('Не удалось загрузить фото. Проверьте формат и размер файлов.');
        }

        // Отправляем уведомление админу
        $admin_email = get_option('admin_email');
        $category_name = $this->get_category_name($data['category_id']);
        
        wp_mail(
            $admin_email,
            'Новое объявление на модерацию — ' . get_bloginfo('name'),
            "Новое объявление в категории: {$category_name}\n\n" .
            "Название: {$data['title']}\n" .
            "Цена: " . ($data['price'] ? $data['price'] . ' ' . $data['currency'] : 'Договорная') . "\n" .
            "Контакт: {$data['contact_name']} ({$data['contact_phone']})\n\n" .
            "Проверьте в админке."
        );

        $edit_url = home_url("/ad/edit/{$data['edit_token']}");

        error_log('=== AD UPLOAD SUCCESS ===');
        wp_send_json_success([
            'message' => 'Ваше объявление отправлено на модерацию',
            'edit_url' => $edit_url,
            'photos_count' => $uploaded_photos
        ]);
    }

    public function handle_toggle_favorite() {
        // ВРЕМЕННО для отладки
        // if (!wp_verify_nonce($_POST['nonce'], 'city_ads_ajax_nonce')) {
        //     wp_send_json_error('Проверка безопасности не пройдена');
        // }
        
        $ad_id = intval($_POST['ad_id'] ?? 0);
        if (!$ad_id) {
            wp_send_json_error('Неверный ID объявления');
        }

        $ip_address = $this->get_client_ip();
        if (!$ip_address) {
            wp_send_json_error('Не удалось определить IP');
        }

        global $wpdb;
        $table_favorites = $wpdb->prefix . 'city_ad_favorites';
        $table_ads = $wpdb->prefix . 'city_ads';

        // Проверяем, есть ли уже в избранном
        $existing_fav = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_favorites WHERE ad_id = %d AND ip_address = %s",
            $ad_id, $ip_address
        ));

        if ($existing_fav) {
            // Удаляем из избранного
            $wpdb->delete($table_favorites, ['ad_id' => $ad_id, 'ip_address' => $ip_address]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_ads SET favorites_count = favorites_count - 1 WHERE id = %d",
                $ad_id
            ));
            $favorited = false;
        } else {
            // Добавляем в избранное
            $wpdb->insert($table_favorites, [
                'ad_id' => $ad_id,
                'ip_address' => $ip_address
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_ads SET favorites_count = favorites_count + 1 WHERE id = %d",
                $ad_id
            ));
            $favorited = true;
        }

        // Получаем обновленное количество
        $favorites_count = $wpdb->get_var($wpdb->prepare(
            "SELECT favorites_count FROM $table_ads WHERE id = %d",
            $ad_id
        ));

        wp_send_json_success([
            'favorited' => $favorited,
            'favorites_count' => $favorites_count
        ]);
    }

    public function handle_update() {
        if (!wp_verify_nonce($_POST['nonce'], 'city_ads_edit_nonce')) {
            wp_send_json_error('Проверка безопасности не пройдена');
        }

        $token = sanitize_text_field($_POST['edit_token'] ?? '');
        $ad = $this->get_ad_by_token($token);
        if (!$ad) {
            wp_send_json_error('Объявление не найдено');
        }

        $data = [
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description'] ?? ''),
            'price' => $this->sanitize_price($_POST['price'] ?? ''),
            'currency' => sanitize_text_field($_POST['currency'] ?? 'руб.'),
            'category_id' => intval($_POST['category_id']),
            'address' => sanitize_text_field($_POST['address'] ?? ''),
            'contact_phone' => sanitize_text_field($_POST['contact_phone']),
            'contact_email' => sanitize_email($_POST['contact_email'] ?? ''),
            'contact_name' => sanitize_text_field($_POST['contact_name'])
        ];

        // Проверяем обязательные поля
        if (empty($data['title']) || empty($data['category_id']) || 
            empty($data['contact_name']) || empty($data['contact_phone'])) {
            wp_send_json_error('Заполните все обязательные поля');
        }

        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $wpdb->update($table_ads, $data, ['edit_token' => $token]);

        // Обрабатываем новые фото, если есть
        if (!empty($_FILES['new_photos']['name'][0])) {
            $this->handle_additional_photos($ad->id);
        }

        // Удаляем отмеченные для удаления фото
        if (!empty($_POST['delete_photos'])) {
            $delete_ids = array_map('intval', explode(',', $_POST['delete_photos']));
            $this->delete_ad_photos($delete_ids, $ad->id);
        }

        wp_send_json_success(['redirect' => add_query_arg('updated', '1', home_url("ad/edit/{$token}"))]);
    }

    public function handle_delete() {
        if (!wp_verify_nonce($_POST['nonce'], 'city_ads_edit_nonce')) {
            wp_send_json_error('Проверка безопасности не пройдена');
        }

        $token = sanitize_text_field($_POST['edit_token'] ?? '');
        $ad = $this->get_ad_by_token($token);
        if (!$ad) {
            wp_send_json_error('Объявление не найдено');
        }

        // Удаляем фото
        $photos = $this->get_ad_photos($ad->id);
        $upload_dir = wp_upload_dir();
        
        foreach ($photos as $photo) {
            $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo->image_url);
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Удаляем из БД
        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $wpdb->delete($table_ads, ['edit_token' => $token]);

        wp_send_json_success();
    }

    public function track_ad_view() {
        if (is_singular() && get_query_var('pgc_ad_view')) {
            $ad_id = intval(get_query_var('pgc_ad_view'));
            
            // Увеличиваем счетчик просмотров (раз в сессию)
            if (!isset($_SESSION['viewed_ads'])) {
                $_SESSION['viewed_ads'] = [];
            }
            
            if (!in_array($ad_id, $_SESSION['viewed_ads'])) {
                global $wpdb;
                $table = $wpdb->prefix . 'city_ads';
                $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET views_count = views_count + 1 WHERE id = %d",
                    $ad_id
                ));
                $_SESSION['viewed_ads'][] = $ad_id;
            }
        }
    }

    // ========== PUBLIC METHODS ==========
    
    public function get_approved_ads($limit = 20, $category_id = null) {
        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $table_categories = $wpdb->prefix . 'city_ad_categories';
        
        $where = "a.status = 'approved'";
        if ($category_id) {
            $where .= $wpdb->prepare(" AND a.category_id = %d", $category_id);
        }
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.name as category_name, c.slug as category_slug 
             FROM $table_ads a 
             LEFT JOIN $table_categories c ON a.category_id = c.id 
             WHERE $where 
             ORDER BY a.is_premium DESC, a.created_at DESC 
             LIMIT %d",
            $limit
        ));
    }

    public function get_ad_of_the_day() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ads';
        
        return $wpdb->get_row(
            "SELECT * FROM $table 
            WHERE status = 'approved' 
            ORDER BY (views_count * 0.3 + favorites_count * 0.7) DESC 
            LIMIT 1"
        );
    }

    public function get_ad_by_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ads';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE edit_token = %s",
            $token
        ));
    }

    public function get_ad_by_id($id) {
        global $wpdb;
        $table_ads = $wpdb->prefix . 'city_ads';
        $table_categories = $wpdb->prefix . 'city_ad_categories';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, c.name as category_name 
             FROM $table_ads a 
             LEFT JOIN $table_categories c ON a.category_id = c.id 
             WHERE a.id = %d",
            $id
        ));
    }

    public function get_ad_photos($ad_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_photos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE ad_id = %d ORDER BY sort_order, is_main DESC",
            $ad_id
        ));
    }

    public function get_categories() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_categories';
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE is_active = 1 ORDER BY sort_order, name"
        );
    }

    public function get_category($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_categories';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
    }

    public function get_category_name($id) {
        $category = $this->get_category($id);
        return $category ? $category->name : 'Без категории';
    }

    public function is_favorited_by_ip($ad_id, $ip_address) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_ad_favorites';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE ad_id = %d AND ip_address = %s",
            $ad_id, $ip_address
        )) > 0;
    }

    // ========== PRIVATE METHODS ==========
    
    private function handle_additional_photos($ad_id) {
        global $wpdb;
        $upload_dir = wp_upload_dir();
        $ads_dir = $upload_dir['basedir'] . '/city-ads/';
        if (!file_exists($ads_dir)) wp_mkdir_p($ads_dir);

        $table_photos = $wpdb->prefix . 'city_ad_photos';
        $photos_count = count($_FILES['new_photos']['name']);
        $current_photos = $this->get_ad_photos($ad_id);
        $start_order = count($current_photos);

        for ($i = 0; $i < $photos_count; $i++) {
            if ($_FILES['new_photos']['error'][$i] !== UPLOAD_ERR_OK) continue;

            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = wp_check_filetype($_FILES['new_photos']['name'][$i]);
            if (!in_array($file_type['type'], $allowed_types)) continue;
            if ($_FILES['new_photos']['size'][$i] > 5 * 1024 * 1024) continue;

            $filename = uniqid() . '_' . sanitize_file_name($_FILES['new_photos']['name'][$i]);
            $filepath = $ads_dir . $filename;

            if (move_uploaded_file($_FILES['new_photos']['tmp_name'][$i], $filepath)) {
                $this->optimize_image($filepath);
                $image_url = $upload_dir['baseurl'] . '/city-ads/' . $filename;

                $photo_data = [
                    'ad_id' => $ad_id,
                    'image_url' => $image_url,
                    'sort_order' => $start_order + $i,
                    'is_main' => ($start_order === 0 && $i === 0) ? 1 : 0
                ];

                $wpdb->insert($table_photos, $photo_data);
            }
        }
    }

    private function delete_ad_photos($photo_ids, $ad_id) {
        global $wpdb;
        $table_photos = $wpdb->prefix . 'city_ad_photos';
        $upload_dir = wp_upload_dir();

        foreach ($photo_ids as $photo_id) {
            $photo = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_photos WHERE id = %d AND ad_id = %d",
                $photo_id, $ad_id
            ));

            if ($photo) {
                $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo->image_url);
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                $wpdb->delete($table_photos, ['id' => $photo_id]);
            }
        }

        // Если удалили главное фото, назначаем новое главное
        $remaining_photos = $this->get_ad_photos($ad_id);
        if ($remaining_photos && !$this->has_main_photo($remaining_photos)) {
            $wpdb->update($table_photos, 
                ['is_main' => 1], 
                ['ad_id' => $ad_id, 'id' => $remaining_photos[0]->id]
            );
        }
    }

    private function has_main_photo($photos) {
        foreach ($photos as $photo) {
            if ($photo->is_main) return true;
        }
        return false;
    }

    private function sanitize_price($price) {
        $price = preg_replace('/[^0-9,.]/', '', $price);
        $price = str_replace(',', '.', $price);
        return $price ? floatval($price) : null;
    }

    private function optimize_image($filepath) {
        if (function_exists('wp_get_image_editor')) {
            $editor = wp_get_image_editor($filepath);
            if (!is_wp_error($editor)) {
                $editor->set_quality(85);
                $editor->save($filepath);
            }
        }
    }

    private function get_field_label($field) {
        $labels = [
            'title' => 'Название',
            'category_id' => 'Категория',
            'contact_name' => 'Ваше имя',
            'contact_phone' => 'Телефон'
        ];
        return $labels[$field] ?? $field;
    }

    public function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}