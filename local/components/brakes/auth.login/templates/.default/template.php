<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die(); ?>

<div class="login__signup">
    <h1 class="login__main-title main__title">Вход</h1>
    <p class="login__message">Введите номер телефона</p>
    <div class="login__body">
        <form class="login__form contacts-form" action="#" id="login-form">
            <div class="login__form-item contacts-form__item">
                <label class="login__label contacts-form__label" for="login-phone">Номер телефона</label>
                <div class="login__input-wrapper contacts-form__input-wrapper">
                    <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                    <input class="login__input contacts-form__input--tel" id="login-phone" name="phone" placeholder="" type="tel">
                </div>
            </div>
            <button type="submit" class="contacts-form-btn main-cataloge__shoping-btn">
                <span class="main-cataloge__shoping-text">Вход</span>
            </button>
        </form>
        <div class="login__switch-form">
            <p class="login__switch-text">Нет аккаунта? <a href="/register/" class="login__switch-link">Зарегистрируйтесь</a></p>
        </div>
    </div>
</div>
<!-- Скрытая ссылка для управления попапом -->
<a href="#popup3" data-fls-popup-link id="open-popup-3" style="display: none;"></a>