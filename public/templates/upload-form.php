<div class="pgc-upload-modal" id="pgc-photo-upload-modal">
    <div class="pgc-modal-content">
        <span class="pgc-close-modal">&times;</span>
        <h2>📷 Добавить фотографию</h2>
        <p style="text-align: center; color: #666; margin-bottom: 25px;">
            Поделитесь вашим лучшим снимком! После модерации фото появится в галерее.
        </p>
        
        <form id="pgc-photo-upload-form" enctype="multipart/form-data">
            <?php wp_nonce_field('city_photos_nonce', 'nonce'); ?>

            <div class="pgc-form-group">
                <label for="pgc-photographer-name">
                    <span style="color: #dc3232;">*</span> Ваше имя (будет отображаться под фото)
                </label>
                <input type="text" id="pgc-photographer-name" name="photographer_name" 
                       required maxlength="255" placeholder="Например: Иван Петров">
            </div>

            <div class="pgc-form-group">
                <label for="pgc-photo-description">Описание фото (необязательно)</label>
                <textarea id="pgc-photo-description" name="description" rows="4" maxlength="1000"
                          placeholder="Расскажите о вашем снимке, где и когда сделан..."></textarea>
                <small>Максимум 1000 символов</small>
            </div>

            <div class="pgc-form-group">
                <label for="pgc-photo-image">
                    <span style="color: #dc3232;">*</span> Выберите фотографию
                </label>
                <div style="border: 2px dashed #dee2e6; border-radius: 8px; padding: 25px; text-align: center; margin-top: 8px;">
                    <div style="font-size: 48px; color: #adb5bd; margin-bottom: 15px;">🖼️</div>
                    <p style="color: #6c757d; margin-bottom: 15px;">Перетащите сюда фото или</p>
                    <input type="file" id="pgc-photo-image" name="image" accept="image/*" required
                           style="display: block; margin: 0 auto;">
                    <small style="display: block; margin-top: 15px;">
                        Форматы: JPG, PNG, GIF, WebP. Макс. размер: 10 МБ
                    </small>
                </div>
            </div>

            <button type="submit" class="pgc-submit-btn">
                📤 Отправить на модерацию
            </button>
            
            <p style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 14px;">
                После отправки вы получите ссылку для редактирования.<br>
                Сохраните её, чтобы управлять вашим фото!
            </p>
            
            <div id="pgc-photo-response-message"></div>
        </form>
    </div>
</div>