<div class="pgc-upload-modal" id="pgc-ad-upload-modal">
    <div class="pgc-modal-content" style="max-width: 700px;">
        <span class="pgc-close-modal">&times;</span>
        <h2>🏷️ Подать объявление</h2>
        <p style="text-align: center; color: #666; margin-bottom: 25px;">
            Заполните форму ниже. После модерации ваше объявление появится на сайте.
        </p>
        
        <form id="pgc-ad-upload-form" enctype="multipart/form-data">
            <?php wp_nonce_field('city_ads_nonce', 'nonce'); ?>

            <div class="pgc-form-group">
                <label for="pgc-ad-title">
                    <span style="color: #dc3232;">*</span> Название объявления
                </label>
                <input type="text" id="pgc-ad-title" name="title" 
                       required maxlength="255" placeholder="Например: Продам iPhone 12">
            </div>

            <div class="pgc-form-group">
                <label for="pgc-ad-category">
                    <span style="color: #dc3232;">*</span> Категория
                </label>
                <select id="pgc-ad-category" name="category_id" required>
                    <option value="">Выберите категорию</option>
                    <?php 
                    $categories = (new CityCoupons_Ads())->get_categories();
                    foreach ($categories as $cat): ?>
                        <option value="<?php echo esc_attr($cat->id); ?>">
                            <?php echo esc_html($cat->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-ad-description">Подробное описание</label>
                <textarea id="pgc-ad-description" name="description" rows="6" maxlength="2000"
                          placeholder="Опишите товар/услугу подробно: состояние, характеристики, условия продажи..."></textarea>
                <small>Максимум 2000 символов</small>
            </div>

            <div class="pgc-form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-ad-price">Цена</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" id="pgc-ad-price" name="price" 
                               placeholder="Например: 15000"
                               style="flex: 2;">
                        <select name="currency" style="flex: 1;">
                            <option value="руб.">руб.</option>
                            <option value="$">$</option>
                            <option value="€">€</option>
                            <option value="тенге">тенге</option>
                            <option value="договорная">договорная</option>
                        </select>
                    </div>
                    <small>Оставьте пустым для "Договорная"</small>
                </div>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-ad-address">Адрес (необязательно)</label>
                <input type="text" id="pgc-ad-address" name="address" 
                       placeholder="Например: ул. Ленина, 15">
            </div>

            <div class="pgc-form-group">
                <label for="pgc-ad-photos">
                    <span style="color: #dc3232;">*</span> Фотографии
                </label>
                <div style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 25px; text-align: center; margin-top: 8px;">
                    <div style="font-size: 48px; color: #adb5bd; margin-bottom: 15px;">📷</div>
                    <p style="color: #6c757d; margin-bottom: 15px;">Добавьте от 1 до 10 фотографий</p>
                    <input type="file" id="pgc-ad-photos" name="photos[]" accept="image/*" 
                           multiple required
                           style="display: block; margin: 0 auto;">
                    <small style="display: block; margin-top: 15px;">
                        Форматы: JPG, PNG, GIF, WebP. Макс. размер: 5 МБ на фото
                    </small>
                    <div id="pgc-photos-preview" style="margin-top: 20px; display: none;">
                        <div class="pgc-photos-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;"></div>
                    </div>
                </div>
            </div>

            <div class="pgc-form-row" style="display: flex; gap: 20px; margin-bottom: 25px;">
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-ad-contact-name">
                        <span style="color: #dc3232;">*</span> Ваше имя
                    </label>
                    <input type="text" id="pgc-ad-contact-name" name="contact_name" 
                           required placeholder="Иван">
                </div>
                
                <div class="pgc-form-group" style="flex: 1;">
                    <label for="pgc-ad-contact-phone">
                        <span style="color: #dc3232;">*</span> Телефон
                    </label>
                    <input type="tel" id="pgc-ad-contact-phone" name="contact_phone" 
                           required placeholder="+7 (999) 123-45-67">
                </div>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-ad-contact-email">Email (необязательно)</label>
                <input type="email" id="pgc-ad-contact-email" name="contact_email" 
                       placeholder="ivan@example.com">
            </div>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 25px 0;">
                <p style="margin: 0; color: #6c757d; font-size: 14px;">
                    <span style="color: #dc3232; font-weight: bold;">Важно:</span> 
                    Не указывайте пароли, номера карт и другую конфиденциальную информацию. 
                    Все объявления проходят модерацию.
                </p>
            </div>

            <button type="submit" class="pgc-submit-btn">
                📤 Отправить на модерацию
            </button>
            
            <p style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 14px;">
                После отправки вы получите ссылку для редактирования.<br>
                Сохраните её, чтобы управлять вашим объявлением!
            </p>
            
            <div id="pgc-ad-response-message"></div>
        </form>
    </div>
</div>