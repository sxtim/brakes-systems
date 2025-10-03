<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die(); ?>

<div class="login__signin">
    <h1 class="login__main-title main__title">Регистрация</h1>
    <p class="login__message">Заполните информацию о себе</p>
    <div class="login__body">
        <form class="login__form contacts-form" action="#" id="reg-form">
            <div class="login__form-item contacts-form__item">
                <label class="login__label contacts-form__label" for="reg-name">Имя</label>
                <div class="login__input-wrapper contacts-form__input-wrapper">
                    <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
                    <input class="login__input contacts-form__input--user" id="reg-name" name="name" type="text">
                </div>
            </div>
            <div class="login__form-item contacts-form__item">
                <label class="login__label contacts-form__label" for="reg-phone">Номер телефона</label>
                <div class="login__input-wrapper contacts-form__input-wrapper">
                    <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                    <input class="login__input contacts-form__input--tel" id="reg-phone" name="phone" type="tel">
                </div>
            </div>
            <div class="login__form-item contacts-form__item">
                <label class="login__label contacts-form__label" for="reg-email">Email</label>
                <div class="login__input-wrapper contacts-form__input-wrapper">
                    <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/mail.svg" alt="Image">
                    <input class="login__input contacts-form__input--mail" id="reg-email" name="email" type="email">
                </div>
            </div>
            <button type="submit" class="contacts-form-btn main-cataloge__shoping-btn">
                <span class="main-cataloge__shoping-text">Регистрация</span>
            </button>
        </form>
        <div class="login__switch-form">
            <p class="login__switch-text">Уже есть аккаунт? <a href="/login/" class="login__switch-link">Войдите</a></p>
        </div>
    </div>
</div>
<!-- Скрытые ссылки для управления попапами -->
<a href="#popup1" data-fls-popup-link id="open-popup-1" style="display: none;"></a>
<a href="#popup2" data-fls-popup-link id="open-popup-2" style="display: none;"></a>

