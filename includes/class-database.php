<?php
class CityCoupons_Database {
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // Существующая таблица купонов
        $table_coupons = $wpdb->prefix . CITY_COUPONS_TABLE;
        $sql_coupons = "CREATE TABLE IF NOT EXISTS $table_coupons (
            id INT(11) NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            store_name VARCHAR(255),
            image_url VARCHAR(512) NOT NULL,
            coupon_type ENUM('coupon', 'promotion') NOT NULL DEFAULT 'promotion',
            edit_token CHAR(32) NOT NULL UNIQUE,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            expires_at DATETIME NULL,
            is_promoted TINYINT(1) DEFAULT 0,
            promoted_until DATETIME NULL,
            PRIMARY KEY (id),
            INDEX status_idx (status),
            INDEX coupon_type_idx (coupon_type)
        ) $charset_collate;";

        // Новая таблица для фотографий
        $table_photos = $wpdb->prefix . 'city_photos';
        $sql_photos = "CREATE TABLE IF NOT EXISTS $table_photos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            photographer_name VARCHAR(255) NOT NULL,
            description TEXT,
            image_url VARCHAR(512) NOT NULL,
            edit_token CHAR(32) NOT NULL UNIQUE,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            likes_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            day_likes_count INT DEFAULT 0,
            last_like_date DATE NULL,
            PRIMARY KEY (id),
            INDEX status_idx (status),
            INDEX photographer_idx (photographer_name(191)),
            INDEX likes_idx (likes_count),
            INDEX day_likes_idx (day_likes_count, last_like_date)
        ) $charset_collate;";

        // Таблица лайков для фотографий (по IP)
        $table_likes = $wpdb->prefix . 'city_photo_likes';
        $sql_likes = "CREATE TABLE IF NOT EXISTS $table_likes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            photo_id INT(11) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY photo_ip (photo_id, ip_address),
            INDEX photo_idx (photo_id),
            INDEX ip_idx (ip_address),
            FOREIGN KEY (photo_id) REFERENCES $table_photos(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_coupons);
        dbDelta($sql_photos);
        dbDelta($sql_likes);
        
        // ========== ТАБЛИЦЫ ДЛЯ ОБЪЯВЛЕНИЙ ==========
        
        // Категории объявлений
        $table_categories = $wpdb->prefix . 'city_ad_categories';
        $sql_categories = "CREATE TABLE IF NOT EXISTS $table_categories (
            id INT(11) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT,
            parent_id INT(11) DEFAULT 0,
            sort_order INT(11) DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX slug_idx (slug),
            INDEX parent_idx (parent_id),
            INDEX active_idx (is_active)
        ) $charset_collate;";

        dbDelta($sql_categories);
        
        // Основная таблица объявлений
        $table_ads = $wpdb->prefix . 'city_ads';
        $sql_ads = "CREATE TABLE IF NOT EXISTS $table_ads (
            id INT(11) NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NULL,
            currency VARCHAR(10) DEFAULT 'руб.',
            category_id INT(11) NOT NULL,
            address VARCHAR(500),
            contact_phone VARCHAR(50),
            contact_email VARCHAR(100),
            contact_name VARCHAR(255),
            views_count INT DEFAULT 0,
            favorites_count INT DEFAULT 0,
            edit_token CHAR(32) NOT NULL UNIQUE,
            status ENUM('pending', 'approved', 'rejected', 'sold') DEFAULT 'pending',
            is_premium TINYINT(1) DEFAULT 0,
            expires_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX status_idx (status),
            INDEX category_idx (category_id),
            INDEX price_idx (price),
            INDEX premium_idx (is_premium),
            INDEX expires_idx (expires_at)
        ) $charset_collate;";

        dbDelta($sql_ads);
        
        // После создания таблицы ads, добавляем FOREIGN KEY отдельно
        $this->add_foreign_keys();

        // Фото для объявлений (множественные)
        $table_ad_photos = $wpdb->prefix . 'city_ad_photos';
        $sql_ad_photos = "CREATE TABLE IF NOT EXISTS $table_ad_photos (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ad_id INT(11) NOT NULL,
            image_url VARCHAR(512) NOT NULL,
            sort_order INT(11) DEFAULT 0,
            is_main TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX ad_idx (ad_id),
            INDEX main_idx (is_main),
            INDEX sort_idx (sort_order)
        ) $charset_collate;";

        dbDelta($sql_ad_photos);

        // Избранные объявления (по аналогии с лайками)
        $table_ad_favorites = $wpdb->prefix . 'city_ad_favorites';
        $sql_ad_favorites = "CREATE TABLE IF NOT EXISTS $table_ad_favorites (
            id INT(11) NOT NULL AUTO_INCREMENT,
            ad_id INT(11) NOT NULL,
            ip_address VARCHAR(45) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ad_ip (ad_id, ip_address),
            INDEX ad_idx (ad_id),
            INDEX ip_idx (ip_address)
        ) $charset_collate;";

        dbDelta($sql_ad_favorites);
        
        // Добавляем основные категории при первом создании
        if ($wpdb->get_var("SELECT COUNT(*) FROM $table_categories") == 0) {
            $default_categories = [
                ['name' => 'Недвижимость', 'slug' => 'real-estate'],
                ['name' => 'Транспорт', 'slug' => 'transport'],
                ['name' => 'Работа', 'slug' => 'jobs'],
                ['name' => 'Услуги', 'slug' => 'services'],
                ['name' => 'Электроника', 'slug' => 'electronics'],
                ['name' => 'Одежда и обувь', 'slug' => 'clothing'],
                ['name' => 'Детские товары', 'slug' => 'children'],
                ['name' => 'Животные', 'slug' => 'animals'],
                ['name' => 'Мебель', 'slug' => 'furniture'],
                ['name' => 'Разное', 'slug' => 'other']
            ];
            
            foreach ($default_categories as $category) {
                $wpdb->insert($table_categories, $category);
            }
        }
        
        // Проверяем и обновляем структуру таблиц
        $this->update_table_structure();
        
        update_option('city_coupons_flush_rewrite_rules', true);
    }
    
    private function add_foreign_keys() {
        global $wpdb;
        
        $table_ads = $wpdb->prefix . 'city_ads';
        $table_categories = $wpdb->prefix . 'city_ad_categories';
        $table_ad_photos = $wpdb->prefix . 'city_ad_photos';
        $table_ad_favorites = $wpdb->prefix . 'city_ad_favorites';
        $table_photos = $wpdb->prefix . 'city_photos';
        $table_likes = $wpdb->prefix . 'city_photo_likes';
        
        // Проверяем существование FOREIGN KEY и добавляем если нет
        $this->add_foreign_key_if_not_exists($table_ads, 'category_id', $table_categories, 'id');
        $this->add_foreign_key_if_not_exists($table_ad_photos, 'ad_id', $table_ads, 'id');
        $this->add_foreign_key_if_not_exists($table_ad_favorites, 'ad_id', $table_ads, 'id');
        $this->add_foreign_key_if_not_exists($table_likes, 'photo_id', $table_photos, 'id');
    }
    
    private function add_foreign_key_if_not_exists($table, $column, $ref_table, $ref_column) {
        global $wpdb;
        
        // Проверяем существование таблиц
        if (!$this->table_exists($table) || !$this->table_exists($ref_table)) {
            return;
        }
        
        // Проверяем существование колонки
        if (!$this->column_exists($table, $column)) {
            return;
        }
        
        // Проверяем существование FOREIGN KEY
        $fk_name = "fk_{$table}_{$column}";
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS 
             WHERE CONSTRAINT_SCHEMA = DATABASE() 
             AND CONSTRAINT_NAME = %s 
             AND TABLE_NAME = %s",
            $fk_name, $table
        ));
        
        if ($result == 0) {
            // Добавляем FOREIGN KEY
            $wpdb->query(
                "ALTER TABLE $table 
                 ADD CONSTRAINT $fk_name 
                 FOREIGN KEY ($column) REFERENCES $ref_table($ref_column) ON DELETE CASCADE"
            );
        }
    }
    
    private function update_table_structure() {
        global $wpdb;
        
        $table_ads = $wpdb->prefix . 'city_ads';
        
        // Проверяем существование таблицы
        if (!$this->table_exists($table_ads)) {
            return;
        }
        
        // Список колонок, которые должны быть в таблице ads
        $required_columns = [
            'category_id' => "INT(11) NOT NULL AFTER description",
            'currency' => "VARCHAR(10) DEFAULT 'руб.' AFTER price",
            'address' => "VARCHAR(500) AFTER category_id",
            'contact_phone' => "VARCHAR(50) AFTER address",
            'contact_email' => "VARCHAR(100) AFTER contact_phone",
            'contact_name' => "VARCHAR(255) AFTER contact_email",
            'views_count' => "INT DEFAULT 0 AFTER contact_name",
            'favorites_count' => "INT DEFAULT 0 AFTER views_count",
            'edit_token' => "CHAR(32) NOT NULL UNIQUE AFTER favorites_count",
            'status' => "ENUM('pending', 'approved', 'rejected', 'sold') DEFAULT 'pending' AFTER edit_token",
            'is_premium' => "TINYINT(1) DEFAULT 0 AFTER status",
            'expires_at' => "DATETIME NULL AFTER is_premium",
            'updated_at' => "DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
        ];
        
        // Добавляем отсутствующие колонки
        foreach ($required_columns as $column => $definition) {
            if (!$this->column_exists($table_ads, $column)) {
                $wpdb->query("ALTER TABLE $table_ads ADD COLUMN $column $definition");
            }
        }
        
        // Проверяем таблицу ad_photos
        $table_ad_photos = $wpdb->prefix . 'city_ad_photos';
        if ($this->table_exists($table_ad_photos)) {
            if (!$this->column_exists($table_ad_photos, 'is_main')) {
                $wpdb->query("ALTER TABLE $table_ad_photos ADD COLUMN is_main TINYINT(1) DEFAULT 0 AFTER sort_order");
            }
            if (!$this->column_exists($table_ad_photos, 'sort_order')) {
                $wpdb->query("ALTER TABLE $table_ad_photos ADD COLUMN sort_order INT(11) DEFAULT 0 AFTER image_url");
            }
        }
    }
    
    private function table_exists($table_name) {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    }
    
    private function column_exists($table_name, $column_name) {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() 
             AND TABLE_NAME = %s 
             AND COLUMN_NAME = %s",
            $table_name, $column_name
        ));
        return $result > 0;
    }
    
    // Публичный метод для принудительного обновления структуры
    public function force_update_tables() {
        $this->create_tables();
    }
}
?>