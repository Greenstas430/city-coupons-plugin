<?php
/**
 * Plugin Name: Купоны, Фото и Акции Камышина
 * Description: Плагин для публикации купонов, фотографий и акций без регистрации. Редактирование — по секретной ссылке.
 * Version: 3.6
 * Author: Ты
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

define('CITY_COUPONS_DIR', plugin_dir_path(__FILE__));
define('CITY_COUPONS_URL', plugin_dir_url(__FILE__));
define('CITY_COUPONS_VERSION', '3.4');
define('CITY_COUPONS_TABLE', 'city_coupons');

// Подключаем классы
require_once CITY_COUPONS_DIR . 'includes/class-database.php';
require_once CITY_COUPONS_DIR . 'includes/class-coupons.php';
require_once CITY_COUPONS_DIR . 'includes/class-photos.php';
require_once CITY_COUPONS_DIR . 'includes/class-ads.php';
require_once CITY_COUPONS_DIR . 'includes/class-admin.php';
require_once CITY_COUPONS_DIR . 'includes/class-shortcodes.php';

class CityCoupons {
    private static $instance = null;
    private $database;
    private $coupons;
    private $photos;
    private $ads;
    private $admin;
    private $shortcodes;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        if (!function_exists('add_action')) {
            die('Direct access not allowed');
        }

        $this->database = new CityCoupons_Database();
        $this->coupons = new CityCoupons_Coupons();
        $this->photos = new CityCoupons_Photos();
        $this->ads = new CityCoupons_Ads();
        $this->admin = new CityCoupons_Admin();
        $this->shortcodes = new CityCoupons_Shortcodes();

        register_activation_hook(__FILE__, [$this->database, 'create_tables']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Правила перезаписи
        add_action('init', [$this, 'add_rewrite_rules'], 10);
        add_action('wp_loaded', [$this, 'flush_rewrite_rules_if_needed']);
        
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_edit_page']);

        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function add_rewrite_rules() {
        // Правила для купонов
        add_rewrite_rule(
            '^coupon/edit/([a-zA-Z0-9]{32})/?$',
            'index.php?pgc_edit_token=$matches[1]',
            'top'
        );
        
        // Правила для фото
        add_rewrite_rule(
            '^photo/edit/([a-zA-Z0-9]{32})/?$',
            'index.php?pgc_photo_edit_token=$matches[1]',
            'top'
        );
        
        // Правила для объявлений
        add_rewrite_rule(
            '^ad/edit/([a-zA-Z0-9]{32})/?$',
            'index.php?pgc_ad_edit_token=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^ad/view/([0-9]+)/?$',
            'index.php?pgc_ad_view=$matches[1]',
            'top'
        );
        add_rewrite_rule(
            '^ads/category/([^/]+)/?$',
            'index.php?pgc_ad_category=$matches[1]',
            'top'
        );
    }

    public function flush_rewrite_rules_if_needed() {
        if (get_option('city_coupons_flush_rewrite_rules')) {
            flush_rewrite_rules();
            delete_option('city_coupons_flush_rewrite_rules');
        }
    }

    public function add_query_vars($vars) {
        $vars[] = 'pgc_edit_token';
        $vars[] = 'pgc_photo_edit_token';
        $vars[] = 'pgc_ad_edit_token';
        $vars[] = 'pgc_ad_view';
        $vars[] = 'pgc_ad_category';
        return $vars;
    }

    public function handle_edit_page() {
        $token = get_query_var('pgc_edit_token');
        $photo_token = get_query_var('pgc_photo_edit_token');
        $ad_token = get_query_var('pgc_ad_edit_token');
        $ad_view = get_query_var('pgc_ad_view');
        
        if ($token) {
            if (strlen($token) !== 32) {
                wp_die('Неверный формат токена.', 'Ошибка', ['response' => 404]);
            }

            $coupon = (new CityCoupons_Coupons())->get_coupon_by_token($token);
            if (!$coupon) {
                wp_die('Купон не найден или уже удален.', 'Ошибка', ['response' => 404]);
            }

            add_filter('template_include', function() {
                return CITY_COUPONS_DIR . 'public/templates/edit-form.php';
            });
        } elseif ($photo_token) {
            if (strlen($photo_token) !== 32) {
                wp_die('Неверный формат токена.', 'Ошибка', ['response' => 404]);
            }

            $photo = (new CityCoupons_Photos())->get_photo_by_token($photo_token);
            if (!$photo) {
                wp_die('Фото не найдено или уже удалено.', 'Ошибка', ['response' => 404]);
            }

            add_filter('template_include', function() {
                return CITY_COUPONS_DIR . 'public/templates/edit-photo.php';
            });
        } elseif ($ad_token) {
            if (strlen($ad_token) !== 32) {
                wp_die('Неверный формат токена.', 'Ошибка', ['response' => 404]);
            }

            $ad = (new CityCoupons_Ads())->get_ad_by_token($ad_token);
            if (!$ad) {
                wp_die('Объявление не найдено или уже удалено.', 'Ошибка', ['response' => 404]);
            }

            add_filter('template_include', function() {
                return CITY_COUPONS_DIR . 'public/templates/edit-ad.php';
            });
        } elseif ($ad_view) {
            $ad = (new CityCoupons_Ads())->get_ad_by_id($ad_view);
            if (!$ad || $ad->status !== 'approved') {
                wp_die('Объявление не найдено или не одобрено.', 'Ошибка', ['response' => 404]);
            }

            add_filter('template_include', function() {
                return CITY_COUPONS_DIR . 'public/templates/view-ad.php';
            });
        }
    }

    public function enqueue_public_scripts() {
        wp_enqueue_style('city-coupons-style', CITY_COUPONS_URL . 'public/css/style.css', [], CITY_COUPONS_VERSION);
        wp_enqueue_style('magnific-popup', CITY_COUPONS_URL . 'public/css/magnific-popup.css', [], '1.1.0');
        wp_enqueue_style('slick-carousel', CITY_COUPONS_URL . 'public/css/slick.css', [], '1.8.1');
        wp_enqueue_style('slick-theme', CITY_COUPONS_URL . 'public/css/slick-theme.css', [], '1.8.1');
        
        wp_enqueue_script('jquery');
        wp_enqueue_script('magnific-popup', CITY_COUPONS_URL . 'public/js/magnific-popup.min.js', ['jquery'], '1.1.0', true);
        wp_enqueue_script('slick-carousel', CITY_COUPONS_URL . 'public/js/slick.min.js', ['jquery'], '1.8.1', true);
        
        wp_enqueue_script('city-coupons-script', CITY_COUPONS_URL . 'public/js/script.js', ['jquery', 'magnific-popup'], CITY_COUPONS_VERSION, true);
        wp_enqueue_script('city-photos-script', CITY_COUPONS_URL . 'public/js/photos.js', ['jquery', 'magnific-popup'], CITY_COUPONS_VERSION, true);
        wp_enqueue_script('city-ads-script', CITY_COUPONS_URL . 'public/js/ads.js', ['jquery', 'magnific-popup', 'slick-carousel'], CITY_COUPONS_VERSION, true);

        // Локализация - ИСПРАВЛЕНО!
        wp_localize_script('city-coupons-script', 'city_coupons_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('city_coupons_ajax_nonce'),
            'home_url' => home_url('/')
        ]);
        
        wp_localize_script('city-photos-script', 'city_photos_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('city_photos_ajax_nonce')
        ]);
        
        wp_localize_script('city-ads-script', 'city_ads_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('city_ads_ajax_nonce') // Изменено!
        ]);
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'city-coupons') !== false) {
            wp_enqueue_style('city-coupons-admin-style', CITY_COUPONS_URL . 'admin/css/admin-style.css', [], CITY_COUPONS_VERSION);
            wp_enqueue_script('city-coupons-admin-script', CITY_COUPONS_URL . 'admin/js/admin-script.js', ['jquery'], CITY_COUPONS_VERSION, true);
            wp_localize_script('city-coupons-admin-script', 'city_coupons_admin', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('city_coupons_admin_nonce')
            ]);
        }
    }

    public function deactivate() {
        delete_option('city_coupons_flush_rewrite_rules');
        wp_clear_scheduled_hook('city_photos_reset_daily_likes');
    }
}

CityCoupons::get_instance();

// Существующие шорткоды
add_shortcode('upload_coupon_form', function() {
    return (new CityCoupons_Shortcodes())->render_upload_form();
});
add_shortcode('coupons_list', function($atts) {
    return (new CityCoupons_Shortcodes())->render_coupons_list($atts);
});
add_shortcode('photo_gallery', function($atts) {
    return (new CityCoupons_Shortcodes())->render_photo_gallery($atts);
});
add_shortcode('photo_of_the_day', function() {
    return (new CityCoupons_Shortcodes())->render_photo_of_the_day();
});

// Новые шорткоды для объявлений
add_shortcode('ads_list', function($atts) {
    return (new CityCoupons_Shortcodes())->render_ads_list($atts);
});
add_shortcode('ads_categories', function() {
    return (new CityCoupons_Shortcodes())->render_ads_categories();
});
add_shortcode('ad_of_the_day', function() {
    return (new CityCoupons_Shortcodes())->render_ad_of_the_day();
});