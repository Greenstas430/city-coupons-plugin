<?php
class CityCoupons_Photos {
    public function __construct() {
        add_action('wp_ajax_city_photos_upload', [$this, 'handle_upload']);
        add_action('wp_ajax_nopriv_city_photos_upload', [$this, 'handle_upload']);
        add_action('wp_ajax_pgc_toggle_like', [$this, 'handle_toggle_like']);
        add_action('wp_ajax_nopriv_pgc_toggle_like', [$this, 'handle_toggle_like']);
        add_action('wp_ajax_pgc_update_photo', [$this, 'handle_update']);
        add_action('wp_ajax_nopriv_pgc_update_photo', [$this, 'handle_update']);
        add_action('wp_ajax_pgc_delete_photo', [$this, 'handle_delete']);
        add_action('wp_ajax_nopriv_pgc_delete_photo', [$this, 'handle_delete']);
        
        // Крон для сброса дневных лайков
        add_action('city_photos_reset_daily_likes', [$this, 'reset_daily_likes']);
        if (!wp_next_scheduled('city_photos_reset_daily_likes')) {
            wp_schedule_event(strtotime('00:00:00'), 'daily', 'city_photos_reset_daily_likes');
        }
    }

    public function handle_upload() {
        if (!wp_verify_nonce($_POST['nonce'], 'city_photos_nonce')) {
            wp_send_json_error('Проверка безопасности не пройдена');
        }

        $photographer_name = sanitize_text_field($_POST['photographer_name'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (!$photographer_name) {
            wp_send_json_error('Укажите ваше имя');
        }

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Ошибка загрузки изображения');
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = wp_check_filetype($_FILES['image']['name']);
        if (!in_array($file_type['type'], $allowed_types)) {
            wp_send_json_error('Разрешены только JPG, PNG, GIF, WebP');
        }
        if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
            wp_send_json_error('Файл слишком большой (макс. 10 МБ)');
        }

        $upload_dir = wp_upload_dir();
        $photos_dir = $upload_dir['basedir'] . '/city-photos/';
        if (!file_exists($photos_dir)) {
            wp_mkdir_p($photos_dir);
        }

        $filename = uniqid() . '_' . sanitize_file_name($_FILES['image']['name']);
        $filepath = $photos_dir . $filename;

        if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
            wp_send_json_error('Не удалось сохранить файл');
        }

        // Оптимизация изображения
        $this->optimize_image($filepath);

        $image_url = $upload_dir['baseurl'] . '/city-photos/' . $filename;
        $edit_token = bin2hex(random_bytes(16));

        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $data = [
            'photographer_name' => $photographer_name,
            'description'       => $description,
            'image_url'         => $image_url,
            'edit_token'        => $edit_token,
            'status'            => 'pending'
        ];

        $wpdb->insert($table, $data);
        $photo_id = $wpdb->insert_id;

        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            'Новое фото на модерацию — ' . get_bloginfo('name'),
            "Новая фотография от автора: {$photographer_name}.\n\nОписание: {$description}\n\nПроверьте в админке."
        );

        $edit_url = home_url("/photo/edit/{$edit_token}");

        wp_send_json_success([
            'message' => 'Ваша фотография отправлена на модерацию',
            'edit_url' => $edit_url
        ]);
    }

    public function handle_toggle_like() {
        $photo_id = intval($_POST['photo_id'] ?? 0);
        if (!$photo_id) {
            wp_send_json_error('Неверный ID фотографии');
        }

        $ip_address = $this->get_client_ip();
        if (!$ip_address) {
            wp_send_json_error('Не удалось определить IP');
        }

        global $wpdb;
        $table_likes = $wpdb->prefix . 'city_photo_likes';
        $table_photos = $wpdb->prefix . 'city_photos';

        // Проверяем, есть ли уже лайк
        $existing_like = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_likes WHERE photo_id = %d AND ip_address = %s",
            $photo_id, $ip_address
        ));

        if ($existing_like) {
            // Удаляем лайк
            $wpdb->delete($table_likes, ['photo_id' => $photo_id, 'ip_address' => $ip_address]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_photos SET likes_count = likes_count - 1 WHERE id = %d",
                $photo_id
            ));
            $liked = false;
        } else {
            // Добавляем лайк
            $wpdb->insert($table_likes, [
                'photo_id' => $photo_id,
                'ip_address' => $ip_address
            ]);
            $wpdb->query($wpdb->prepare(
                "UPDATE $table_photos SET 
                    likes_count = likes_count + 1,
                    day_likes_count = IF(last_like_date = CURDATE(), day_likes_count + 1, 1),
                    last_like_date = CURDATE()
                WHERE id = %d",
                $photo_id
            ));
            $liked = true;
        }

        // Получаем обновленное количество лайков
        $likes_count = $wpdb->get_var($wpdb->prepare(
            "SELECT likes_count FROM $table_photos WHERE id = %d",
            $photo_id
        ));

        wp_send_json_success([
            'liked' => $liked,
            'likes_count' => $likes_count
        ]);
    }

    public function handle_update() {
        if (!wp_verify_nonce($_POST['nonce'], 'city_photos_edit_nonce')) {
            wp_send_json_error('Проверка безопасности не пройдена');
        }

        $token = sanitize_text_field($_POST['edit_token'] ?? '');
        $photo = $this->get_photo_by_token($token);
        if (!$photo) {
            wp_send_json_error('Фото не найдено');
        }

        $data = [
            'photographer_name' => sanitize_text_field($_POST['photographer_name']),
            'description' => sanitize_textarea_field($_POST['description'] ?? '')
        ];

        if (empty($data['photographer_name'])) {
            wp_send_json_error('Укажите ваше имя');
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $file_type = wp_check_filetype($_FILES['image']['name']);
            if (!in_array($file_type['type'], $allowed_types)) {
                wp_send_json_error('Разрешены только JPG, PNG, GIF, WebP');
            }
            if ($_FILES['image']['size'] > 10 * 1024 * 1024) {
                wp_send_json_error('Файл слишком большой (макс. 10 МБ)');
            }

            $upload_dir = wp_upload_dir();
            $photos_dir = $upload_dir['basedir'] . '/city-photos/';
            if (!file_exists($photos_dir)) wp_mkdir_p($photos_dir);

            $filename = uniqid() . '_' . sanitize_file_name($_FILES['image']['name']);
            $filepath = $photos_dir . $filename;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                $this->optimize_image($filepath);
                $data['image_url'] = $upload_dir['baseurl'] . '/city-photos/' . $filename;

                $old_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo->image_url);
                if (file_exists($old_path)) unlink($old_path);
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $wpdb->update($table, $data, ['edit_token' => $token]);

        wp_send_json_success(['redirect' => add_query_arg('updated', '1', home_url("photo/edit/{$token}"))]);
    }

    public function handle_delete() {
        if (!wp_verify_nonce($_POST['nonce'], 'city_photos_edit_nonce')) {
            wp_send_json_error('Проверка безопасности не пройдена');
        }

        $token = sanitize_text_field($_POST['edit_token'] ?? '');
        $photo = $this->get_photo_by_token($token);
        if (!$photo) {
            wp_send_json_error('Фото не найдено');
        }

        $upload_dir = wp_upload_dir();
        $filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $photo->image_url);
        if (file_exists($filepath)) unlink($filepath);

        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $wpdb->delete($table, ['edit_token' => $token]);

        wp_send_json_success();
    }

    public function get_approved_photos($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE status = 'approved' ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public function get_photo_of_the_day() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        
        return $wpdb->get_row(
            "SELECT * FROM $table 
            WHERE status = 'approved' 
            AND last_like_date = CURDATE()
            ORDER BY day_likes_count DESC, likes_count DESC 
            LIMIT 1"
        );
    }

    public function get_photo_by_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE edit_token = %s",
            $token
        ));
    }

    public function is_liked_by_ip($photo_id, $ip_address) {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photo_likes';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE photo_id = %d AND ip_address = %s",
            $photo_id, $ip_address
        )) > 0;
    }

    public function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
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

    private function optimize_image($filepath) {
        // Базовая оптимизация изображения
        if (function_exists('wp_get_image_editor')) {
            $editor = wp_get_image_editor($filepath);
            if (!is_wp_error($editor)) {
                $editor->set_quality(85);
                $editor->save($filepath);
            }
        }
    }

    public function reset_daily_likes() {
        global $wpdb;
        $table = $wpdb->prefix . 'city_photos';
        $wpdb->query("UPDATE $table SET day_likes_count = 0 WHERE last_like_date < CURDATE()");
    }
}
?>