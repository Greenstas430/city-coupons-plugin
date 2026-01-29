<?php
if (!current_user_can('manage_options')) wp_die('Недостаточно прав');

$admin = new CityCoupons_Admin();
$pending_coupons = $admin->get_pending_coupons();
$pending_photos = $admin->get_pending_photos();
$pending_ads = $admin->get_pending_ads();
$all_coupons = $admin->get_all_coupons(50);
$all_photos = $admin->get_all_photos(50);
$all_ads = $admin->get_all_ads(30);
$stats = $admin->get_stats();
?>

<div class="wrap pgc-admin-wrap">
    <h1>Купоны, Фото и Объявления — Модерация</h1>

    <!-- Статистика -->
    <div class="pgc-stats-cards">
        <!-- Статистика купонов -->
        <div class="pgc-stat-card">
            <div class="pgc-stat-number"><?php echo esc_html($stats->total_coupons ?? 0); ?></div>
            <div class="pgc-stat-label">Всего купонов</div>
        </div>
        <div class="pgc-stat-card pending">
            <div class="pgc-stat-number"><?php echo esc_html($stats->pending_coupons ?? 0); ?></div>
            <div class="pgc-stat-label">Купоны на модерации</div>
        </div>
        <div class="pgc-stat-card approved">
            <div class="pgc-stat-number"><?php echo esc_html($stats->approved_coupons ?? 0); ?></div>
            <div class="pgc-stat-label">Купонов одобрено</div>
        </div>
        <div class="pgc-stat-card rejected">
            <div class="pgc-stat-number"><?php echo esc_html($stats->rejected_coupons ?? 0); ?></div>
            <div class="pgc-stat-label">Купонов отклонено</div>
        </div>
        
        <!-- Статистика фото -->
        <div class="pgc-stat-card">
            <div class="pgc-stat-number"><?php echo esc_html($stats->total_photos ?? 0); ?></div>
            <div class="pgc-stat-label">Всего фото</div>
        </div>
        <div class="pgc-stat-card pending">
            <div class="pgc-stat-number"><?php echo esc_html($stats->pending_photos ?? 0); ?></div>
            <div class="pgc-stat-label">Фото на модерации</div>
        </div>
        <div class="pgc-stat-card approved">
            <div class="pgc-stat-number"><?php echo esc_html($stats->approved_photos ?? 0); ?></div>
            <div class="pgc-stat-label">Фото одобрено</div>
        </div>
        <div class="pgc-stat-card rejected">
            <div class="pgc-stat-number"><?php echo esc_html($stats->rejected_photos ?? 0); ?></div>
            <div class="pgc-stat-label">Фото отклонено</div>
        </div>
        
        <!-- Статистика объявлений -->
        <div class="pgc-stat-card">
            <div class="pgc-stat-number"><?php echo esc_html($stats->total_ads ?? 0); ?></div>
            <div class="pgc-stat-label">Всего объявлений</div>
        </div>
        <div class="pgc-stat-card pending">
            <div class="pgc-stat-number"><?php echo esc_html($stats->pending_ads ?? 0); ?></div>
            <div class="pgc-stat-label">Объявления на модерации</div>
        </div>
        <div class="pgc-stat-card approved">
            <div class="pgc-stat-number"><?php echo esc_html($stats->approved_ads ?? 0); ?></div>
            <div class="pgc-stat-label">Объявлений одобрено</div>
        </div>
        <div class="pgc-stat-card rejected">
            <div class="pgc-stat-number"><?php echo esc_html($stats->rejected_ads ?? 0); ?></div>
            <div class="pgc-stat-label">Объявлений отклонено</div>
        </div>
    </div>

    <!-- Купоны на модерации -->
    <div class="pgc-admin-section">
        <h2>Купоны на модерации <?php if (count($pending_coupons)): ?><span style="color:#d63638;font-size:14px;font-weight:600;">(<?php echo esc_html(count($pending_coupons)); ?>)</span><?php endif; ?></h2>

        <?php if (empty($pending_coupons)): ?>
            <div class="pgc-empty-state">
                <span class="dashicons dashicons-tickets"></span>
                <p>Нет купонов на модерации.</p>
            </div>
        <?php else: ?>
            <div class="pgc-pending-photos">
                <?php foreach ($pending_coupons as $c): ?>
                    <div class="pgc-pending-item" data-coupon-id="<?php echo esc_attr($c->id); ?>">
                        <div class="pgc-preview">
                            <img src="<?php echo esc_url($c->image_url); ?>" 
                                onclick="window.open('<?php echo esc_url($c->image_url); ?>', '_blank')"
                                style="cursor:pointer;"
                                alt="<?php echo esc_attr($c->title); ?>">
                        </div>
                        <div class="pgc-details">
                            <h3><?php echo esc_html($c->store_name); ?></h3>
                            <p><strong><?php echo esc_html($c->title); ?></strong></p>
                            <?php if ($c->description): ?>
                                <p class="pgc-photo-desc"><?php echo esc_html($c->description); ?></p>
                            <?php endif; ?>
                            <p>
                                <small>
                                    Тип: <b><?php echo $c->coupon_type === 'coupon' ? 'Купон' : 'Акция'; ?></b><br>
                                    Загружено: <?php echo human_time_diff(strtotime($c->created_at)); ?> назад<br>
                                    ID: <?php echo esc_html($c->id); ?>
                                </small>
                            </p>
                            <div class="pgc-actions">
                                <button class="button button-primary pgc-approve-btn"
                                    data-coupon-id="<?php echo esc_attr($c->id); ?>"
                                    data-action-type="approve">
                                    <span class="dashicons dashicons-yes-alt"></span> Одобрить
                                </button>
                                <button class="button button-secondary pgc-reject-btn"
                                    data-coupon-id="<?php echo esc_attr($c->id); ?>"
                                    data-action-type="reject">
                                    <span class="dashicons dashicons-no-alt"></span> Отклонить
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Фото на модерации -->
    <div class="pgc-admin-section">
        <h2>Фото на модерации <?php if (count($pending_photos)): ?><span style="color:#d63638;font-size:14px;font-weight:600;">(<?php echo esc_html(count($pending_photos)); ?>)</span><?php endif; ?></h2>

        <?php if (empty($pending_photos)): ?>
            <div class="pgc-empty-state">
                <span class="dashicons dashicons-camera"></span>
                <p>Нет фотографий на модерации.</p>
            </div>
        <?php else: ?>
            <div class="pgc-pending-photos">
                <?php foreach ($pending_photos as $p): ?>
                    <div class="pgc-pending-item" data-photo-id="<?php echo esc_attr($p->id); ?>">
                        <div class="pgc-preview">
                            <img src="<?php echo esc_url($p->image_url); ?>" 
                                onclick="window.open('<?php echo esc_url($p->image_url); ?>', '_blank')"
                                style="cursor:pointer;"
                                alt="<?php echo esc_attr($p->description ?: 'Фото'); ?>">
                        </div>
                        <div class="pgc-details">
                            <h3><?php echo esc_html($p->photographer_name); ?></h3>
                            <?php if ($p->description): ?>
                                <p class="pgc-photo-desc"><?php echo esc_html($p->description); ?></p>
                            <?php endif; ?>
                            <p>
                                <small>
                                    Загружено: <?php echo human_time_diff(strtotime($p->created_at)); ?> назад<br>
                                    ID: <?php echo esc_html($p->id); ?><br>
                                    Лайков: <?php echo esc_html($p->likes_count); ?>
                                </small>
                            </p>
                            <div class="pgc-actions">
                                <button class="button button-primary pgc-approve-photo-btn"
                                    data-photo-id="<?php echo esc_attr($p->id); ?>"
                                    data-action-type="approve">
                                    <span class="dashicons dashicons-yes-alt"></span> Одобрить
                                </button>
                                <button class="button button-secondary pgc-reject-photo-btn"
                                    data-photo-id="<?php echo esc_attr($p->id); ?>"
                                    data-action-type="reject">
                                    <span class="dashicons dashicons-no-alt"></span> Отклонить
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Объявления на модерации -->
    <div class="pgc-admin-section">
        <h2>Объявления на модерации <?php if (count($pending_ads)): ?><span style="color:#d63638;font-size:14px;font-weight:600;">(<?php echo esc_html(count($pending_ads)); ?>)</span><?php endif; ?></h2>

        <?php if (empty($pending_ads)): ?>
            <div class="pgc-empty-state">
                <span class="dashicons dashicons-megaphone"></span>
                <p>Нет объявлений на модерации.</p>
            </div>
        <?php else: ?>
            <div class="pgc-pending-photos">
                <?php foreach ($pending_ads as $ad): 
                    $photos = $admin->get_ad_photos($ad->id);
                    $main_photo = $photos ? $photos[0]->image_url : CITY_COUPONS_URL . 'public/images/no-photo.svg';
                    ?>
                    <div class="pgc-pending-item" data-ad-id="<?php echo esc_attr($ad->id); ?>">
                        <div class="pgc-preview">
                            <img src="<?php echo esc_url($main_photo); ?>" 
                                onclick="window.open('<?php echo esc_url($main_photo); ?>', '_blank')"
                                style="cursor:pointer; height: 150px; object-fit: cover;"
                                alt="<?php echo esc_attr($ad->title); ?>">
                        </div>
                        <div class="pgc-details">
                            <h3><?php echo esc_html($ad->title); ?></h3>
                            <p>
                                <small>
                                    Категория: <b><?php echo esc_html($admin->get_category_name($ad->category_id)); ?></b><br>
                                    Цена: <b><?php echo $ad->price ? number_format($ad->price, 0, ',', ' ') . ' ' . $ad->currency : 'Договорная'; ?></b><br>
                                    Контакт: <?php echo esc_html($ad->contact_name); ?> (<?php echo esc_html($ad->contact_phone); ?>)<br>
                                    Загружено: <?php echo human_time_diff(strtotime($ad->created_at)); ?> назад<br>
                                    ID: <?php echo esc_html($ad->id); ?><br>
                                    Фото: <?php echo count($photos); ?> шт.
                                </small>
                            </p>
                            <div class="pgc-actions">
                                <button class="button button-primary pgc-approve-ad-btn"
                                    data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                    data-action-type="approve">
                                    <span class="dashicons dashicons-yes-alt"></span> Одобрить
                                </button>
                                <button class="button button-secondary pgc-reject-ad-btn"
                                    data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                    data-action-type="reject">
                                    <span class="dashicons dashicons-no-alt"></span> Отклонить
                                </button>
                                <a href="<?php echo home_url("/ad/view/{$ad->id}"); ?>" 
                                   class="button" 
                                   target="_blank"
                                   style="text-decoration: none;">
                                    <span class="dashicons dashicons-visibility"></span> Посмотреть
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Все купоны -->
    <div class="pgc-admin-section">
        <h2>Все купоны и акции</h2>
        <table class="wp-list-table widefat fixed striped pgc-photos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Магазин</th>
                    <th>Название</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_coupons as $c):
                    $status_class = match($c->status) {
                        'approved' => 'status-approved',
                        'pending'  => 'status-pending',
                        default    => 'status-rejected'
                    };
                    ?>
                    <tr>
                        <td><?php echo esc_html($c->id); ?></td>
                        <td><?php echo esc_html($c->store_name); ?></td>
                        <td><?php echo esc_html($c->title); ?></td>
                        <td><?php echo $c->coupon_type === 'coupon' ? 'Купон' : 'Акция'; ?></td>
                        <td><span class="pgc-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($c->status); ?></span></td>
                        <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($c->created_at))); ?></td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php if ($c->status !== 'approved'): ?>
                                    <button class="button button-small pgc-approve-btn"
                                        data-coupon-id="<?php echo esc_attr($c->id); ?>"
                                        data-action-type="approve">
                                        Одобрить
                                    </button>
                                <?php endif; ?>
                                <?php if ($c->status !== 'rejected'): ?>
                                    <button class="button button-small pgc-reject-btn"
                                        data-coupon-id="<?php echo esc_attr($c->id); ?>"
                                        data-action-type="reject">
                                        Отклонить
                                    </button>
                                <?php endif; ?>
                                <button class="button button-small pgc-delete-btn"
                                    data-coupon-id="<?php echo esc_attr($c->id); ?>">
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Все фото -->
    <div class="pgc-admin-section">
        <h2>Все фотографии</h2>
        <table class="wp-list-table widefat fixed striped pgc-photos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Автор</th>
                    <th>Описание</th>
                    <th>Лайки</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_photos as $p):
                    $status_class = match($p->status) {
                        'approved' => 'status-approved',
                        'pending'  => 'status-pending',
                        default    => 'status-rejected'
                    };
                    ?>
                    <tr>
                        <td><?php echo esc_html($p->id); ?></td>
                        <td><?php echo esc_html($p->photographer_name); ?></td>
                        <td><?php echo esc_html(mb_substr($p->description, 0, 100)); ?>...</td>
                        <td><?php echo esc_html($p->likes_count); ?></td>
                        <td><span class="pgc-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($p->status); ?></span></td>
                        <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($p->created_at))); ?></td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php if ($p->status !== 'approved'): ?>
                                    <button class="button button-small pgc-approve-photo-btn"
                                        data-photo-id="<?php echo esc_attr($p->id); ?>"
                                        data-action-type="approve">
                                        Одобрить
                                    </button>
                                <?php endif; ?>
                                <?php if ($p->status !== 'rejected'): ?>
                                    <button class="button button-small pgc-reject-photo-btn"
                                        data-photo-id="<?php echo esc_attr($p->id); ?>"
                                        data-action-type="reject">
                                        Отклонить
                                    </button>
                                <?php endif; ?>
                                <button class="button button-small pgc-delete-photo-btn"
                                    data-photo-id="<?php echo esc_attr($p->id); ?>">
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Все объявления -->
    <div class="pgc-admin-section">
        <h2>Все объявления</h2>
        <table class="wp-list-table widefat fixed striped pgc-photos-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Категория</th>
                    <th>Цена</th>
                    <th>Контакты</th>
                    <th>Просмотры</th>
                    <th>Избранное</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_ads as $ad):
                    $status_class = match($ad->status) {
                        'approved' => 'status-approved',
                        'pending'  => 'status-pending',
                        'rejected' => 'status-rejected',
                        'sold'     => 'status-sold',
                        default    => 'status-rejected'
                    };
                    
                    $status_text = match($ad->status) {
                        'approved' => 'Одобрено',
                        'pending'  => 'На модерации',
                        'rejected' => 'Отклонено',
                        'sold'     => 'Продано',
                        default    => $ad->status
                    };
                    ?>
                    <tr>
                        <td><?php echo esc_html($ad->id); ?></td>
                        <td>
                            <strong><?php echo esc_html(mb_substr($ad->title, 0, 30)); ?>...</strong><br>
                            <small><?php echo esc_html(mb_substr($ad->description, 0, 50)); ?>...</small>
                        </td>
                        <td><?php echo esc_html($admin->get_category_name($ad->category_id)); ?></td>
                        <td>
                            <?php if ($ad->price): ?>
                                <strong><?php echo number_format($ad->price, 0, ',', ' '); ?> <?php echo esc_html($ad->currency); ?></strong>
                            <?php else: ?>
                                <span style="color: #718096;">Договорная</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <small>
                                <?php echo esc_html($ad->contact_name); ?><br>
                                <?php echo esc_html($ad->contact_phone); ?><br>
                                <?php echo esc_html($ad->contact_email); ?>
                            </small>
                        </td>
                        <td><?php echo esc_html($ad->views_count); ?></td>
                        <td><?php echo esc_html($ad->favorites_count); ?></td>
                        <td><span class="pgc-status <?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span></td>
                        <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($ad->created_at))); ?></td>
                        <td>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <?php if ($ad->status !== 'approved'): ?>
                                    <button class="button button-small pgc-approve-ad-btn"
                                        data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                        data-action-type="approve">
                                        Одобрить
                                    </button>
                                <?php endif; ?>
                                <?php if ($ad->status !== 'rejected'): ?>
                                    <button class="button button-small pgc-reject-ad-btn"
                                        data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                        data-action-type="reject">
                                        Отклонить
                                    </button>
                                <?php endif; ?>
                                <?php if ($ad->status === 'approved'): ?>
                                    <button class="button button-small pgc-mark-sold-btn"
                                        data-ad-id="<?php echo esc_attr($ad->id); ?>"
                                        data-action-type="sold">
                                        Продано
                                    </button>
                                <?php endif; ?>
                                <a href="<?php echo home_url("/ad/view/{$ad->id}"); ?>" 
                                   class="button button-small" 
                                   target="_blank"
                                   style="text-decoration: none;">
                                    Посмотреть
                                </a>
                                <button class="button button-small pgc-delete-ad-btn"
                                    data-ad-id="<?php echo esc_attr($ad->id); ?>">
                                    Удалить
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="pgc-notice notice-info">
        <h3><span class="dashicons dashicons-info"></span> Шорткоды для вставки на страницы:</h3>
        <ul>
            <li><code>[upload_coupon_form]</code> — форма загрузки купонов</li>
            <li><code>[coupons_list count="15"]</code> — список купонов (рекомендуется)</li>
            <li><code>[photo_gallery count="20"]</code> — фотогалерея</li>
            <li><code>[photo_of_the_day]</code> — фото дня для сайдбара</li>
            <li><code>[ads_list count="20"]</code> — список объявлений</li>
            <li><code>[ads_categories]</code> — категории объявлений (виджет)</li>
            <li><code>[ad_of_the_day]</code> — объявление дня для сайдбара</li>
        </ul>
        <p><strong>Совет:</strong> используйте <code>[coupons_list]</code>, <code>[photo_gallery]</code> и <code>[ads_list]</code> — они уже содержат кнопки загрузки.</p>
        <p><small>После активации плагина перейдите в <a href="<?php echo admin_url('options-permalink.php'); ?>">Настройки → Постоянные ссылки</a> и нажмите "Сохранить изменения" для активации ссылок редактирования.</small></p>
    </div>
</div>