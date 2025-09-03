<?
use Bitrix\Main\Page\Asset;
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Каталог');
?>
    <main class="page">
        <div class="login__container">
            <div class="other__main page__main">
                <div class="main__inner">
                    <div class="login__main-page">
                        <div class="login__picture-wrapper">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg-1200.webp" type="image/webp">
                                <img class="login__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg.webp">
                            </picture>
                        </div>
                        <div class="login__forms">
                            <?php $APPLICATION->IncludeComponent("bitrix:breadcrumb",
                                "",Array(
                                    "START_FROM" => "0",
                                    "PATH" => "",
                                    "SITE_ID" => "s1",
                                    "CLASS" => "login__breadcrumbs ",
                                )
                            );?>
                            <div class="tabs-wrapper">
                                <div class="tabs-toggle">
                                    <button class="mytabs__btn-left tab-btn active" data-tab="1">Регистрация</button>
                                    <button class="mytabs__btn-right tab-btn" data-tab="2">Вход</button>
                                </div>
                                <div class="mytabs__content-blocks">
                                    <div class="content" data-content="1">
                                        <div class="login-tabs__body login-tabs__body--signin tabs__body">
                                            <div class="login__signin">
                                                <h1 class="login__main-title main__title">Регистрация</h1>
                                                <p class="login__message">Заполните информацию о себе</p>
                                                <div class="login__body">
                                                    <form class="login__form contacts-form" action="#">
                                                        <div class="login__form-item contacts-form__item">
                                                            <label class="login__label contacts-form__label" for="basketInput1">Имя</label>
                                                            <div class="login__input-wrapper contacts-form__input-wrapper">
                                                                <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
                                                                <input class="login__input contacts-form__input contacts-form__input--user" id="basketInput1" placeholder="" type="text">
                                                            </div>
                                                        </div>
                                                        <div class="login__form-item contacts-form__item">
                                                            <label class="login__label contacts-form__label" for="basketInput2">Номер телефона</label>
                                                            <div class="login__input-wrapper contacts-form__input-wrapper">
                                                                <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                                                                <input class="login__input contacts-form__input contacts-form__input--tel" id="basketInput2" placeholder="" type="number">
                                                            </div>
                                                        </div>
                                                        <div class="login__form-item contacts-form__item">
                                                            <label class="login__label contacts-form__label" for="basketInput3">Email</label>
                                                            <div class="login__input-wrapper contacts-form__input-wrapper">
                                                                <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/mail.svg" alt="Image">
                                                                <input class="login__input contacts-form__input contacts-form__input--mail" id="basketInput3" placeholder="" type="text">
                                                            </div>
                                                        </div>
                                                        <button data-fls-popup-link="popup1" class="contacts-form-btn main-cataloge__shoping-btn">
                                                            <span class="main-cataloge__shoping-text">Регистрация</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="content" data-content="2">
                                        <div class="login-tabs__body login-tabs__body--signup tabs__body">
                                            <div class="login__signup">
                                                <h1 class="login__main-title main__title">Вход</h1>
                                                <p class="login__message">Введите номер телефона</p>
                                                <div class="login__body">
                                                    <form class="login__form contacts-form" action="#">
                                                        <div class="login__form-item contacts-form__item">
                                                            <label class="login__label contacts-form__label" for="basketInput2">Номер телефона</label>
                                                            <div class="login__input-wrapper contacts-form__input-wrapper">
                                                                <img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                                                                <input class="login__input contacts-form__input contacts-form__input--tel" id="basketInput2" placeholder="" type="number">
                                                            </div>
                                                        </div>
                                                        <button data-fls-popup-link="popup3" class="contacts-form-btn main-cataloge__shoping-btn">
                                                            <span class="main-cataloge__shoping-text">Вход</span>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
