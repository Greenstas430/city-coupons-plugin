<?php
class CityCoupons_Coupons {
	public function __construct() {
		add_action('wp_ajax_city_coupons_upload', [$this, 'handle_upload']);
		add_action('wp_ajax_nopriv_city_coupons_upload', [$this, 'handle_upload']);
		add_action('wp_ajax_pgc_update_coupon', [$this, 'handle_update']);
		add_action('wp_ajax_nopriv_pgc_update_coupon', [$this, 'handle_update']);
		add_action('wp_ajax_pgc_delete_coupon', [$this, 'handle_delete']);
		add_action('wp_ajax_nopriv_pgc_delete_coupon', [$this, 'handle_delete']);
	}

	public function handle_upload() {
		if (!wp_verify_nonce($_POST['nonce'], 'city_coupons_nonce')) {
			wp_send_json_error('Проверка безопасности не пройдена');
		}

		$title = sanitize_text_field($_POST['title'] ?? '');
		$store_name = sanitize_text_field($_POST['store_name'] ?? '');
		$description = sanitize_textarea_field($_POST['description'] ?? '');
		$coupon_type = in_array($_POST['coupon_type'], ['coupon', 'promotion']) ? $_POST['coupon_type'] : 'promotion';

		if (!$title || !$store_name) {
			wp_send_json_error('Укажите название акции и магазин');
		}

		if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
			wp_send_json_error('Ошибка загрузки изображения');
		}

		$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
		$file_type = wp_check_filetype($_FILES['image']['name']);
		if (!in_array($file_type['type'], $allowed_types)) {
			wp_send_json_error('Разрешены только JPG, PNG, GIF');
		}
		if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
			wp_send_json_error('Файл слишком большой (макс. 5 МБ)');
		}

		$upload_dir = wp_upload_dir();
		$coupons_dir = $upload_dir['basedir'] . '/city-coupons/';
		if (!file_exists($coupons_dir)) {
			wp_mkdir_p($coupons_dir);
		}

		$filename = uniqid() . '_' . sanitize_file_name($_FILES['image']['name']);
		$filepath = $coupons_dir . $filename;

		if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
			wp_send_json_error('Не удалось сохранить файл');
		}

		$image_url = $upload_dir['baseurl'] . '/city-coupons/' . $filename;
		$edit_token = bin2hex(random_bytes(16));

		global $wpdb;
		$table = $wpdb->prefix . CITY_COUPONS_TABLE;
		$data = [
			'title'        => $title,
			'description'  => $description,
			'store_name'   => $store_name,
			'image_url'    => $image_url,
			'coupon_type'  => $coupon_type,
			'edit_token'   => $edit_token,
			'status'       => 'pending'
		];

		$wpdb->insert($table, $data);

		$admin_email = get_option('admin_email');
		wp_mail(
			$admin_email,
			'Новый купон/акция на модерацию — ' . get_bloginfo('name'),
			"Новая публикация от магазина «{$store_name}».\n\nТип: {$coupon_type}\nНазвание: {$title}\nОписание: {$description}\n\nПроверьте в админке."
		);

		$edit_url = home_url("/coupon/edit/{$edit_token}");

		wp_send_json_success([
			'message' => 'Ваша публикация отправлена на модерацию',
			'edit_url' => $edit_url
		]);
	}

	public function handle_update() {
		if (!wp_verify_nonce($_POST['nonce'], 'city_coupons_edit_nonce')) {
			wp_send_json_error('Проверка безопасности не пройдена');
		}

		$token = sanitize_text_field($_POST['edit_token'] ?? '');
		$coupon = $this->get_coupon_by_token($token);
		if (!$coupon) {
			wp_send_json_error('Купон не найден');
		}

		$data = [
			'store_name'  => sanitize_text_field($_POST['store_name']),
			'title'       => sanitize_text_field($_POST['title']),
			'description' => sanitize_textarea_field($_POST['description'] ?? ''),
			'coupon_type' => in_array($_POST['coupon_type'], ['coupon', 'promotion']) ? $_POST['coupon_type'] : 'promotion'
		];

		if (empty($data['store_name']) || empty($data['title'])) {
			wp_send_json_error('Заполните все обязательные поля');
		}

		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
			$file_type = wp_check_filetype($_FILES['image']['name']);
			if (!in_array($file_type['type'], $allowed_types)) {
				wp_send_json_error('Разрешены только JPG, PNG, GIF');
			}
			if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
				wp_send_json_error('Файл слишком большой (макс. 5 МБ)');
			}

			$upload_dir = wp_upload_dir();
			$coupons_dir = $upload_dir['basedir'] . '/city-coupons/';
			if (!file_exists($coupons_dir)) wp_mkdir_p($coupons_dir);

			$filename = uniqid() . '_' . sanitize_file_name($_FILES['image']['name']);
			$filepath = $coupons_dir . $filename;
			if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
				$data['image_url'] = $upload_dir['baseurl'] . '/city-coupons/' . $filename;

				$old_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $coupon->image_url);
				if (file_exists($old_path)) unlink($old_path);
			}
		}

		global $wpdb;
		$table = $wpdb->prefix . CITY_COUPONS_TABLE;
		$wpdb->update($table, $data, ['edit_token' => $token]);

		wp_send_json_success(['redirect' => add_query_arg('updated', '1', home_url("coupon/edit/{$token}"))]);
	}

	public function handle_delete() {
		if (!wp_verify_nonce($_POST['nonce'], 'city_coupons_edit_nonce')) {
			wp_send_json_error('Проверка безопасности не пройдена');
		}

		$token = sanitize_text_field($_POST['edit_token'] ?? '');
		$coupon = $this->get_coupon_by_token($token);
		if (!$coupon) {
			wp_send_json_error('Купон не найден');
		}

		$upload_dir = wp_upload_dir();
		$filepath = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $coupon->image_url);
		if (file_exists($filepath)) unlink($filepath);

		global $wpdb;
		$table = $wpdb->prefix . CITY_COUPONS_TABLE;
		$wpdb->delete($table, ['edit_token' => $token]);

		wp_send_json_success();
	}

	public function get_approved_coupons($limit = 50) {
		global $wpdb;
		$table = $wpdb->prefix . CITY_COUPONS_TABLE;
		return $wpdb->get_results("SELECT * FROM $table WHERE status = 'approved' ORDER BY is_promoted DESC, promoted_until DESC, created_at DESC LIMIT $limit");
	}

	public function get_coupon_by_token($token) {
		global $wpdb;
		$table = $wpdb->prefix . CITY_COUPONS_TABLE;
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE edit_token = %s", $token));
	}
}