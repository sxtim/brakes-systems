<?php

use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Web\Json;
use App\Brakes\Helper\FavoritesManager;
use App\Brakes\Helper\BasketManager;
use App\Brakes\Pricing\Configurator;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (\Bitrix\Main\Loader::includeModule('pull')) {
    \Bitrix\Main\UI\Extension::load("pull.client");
}
\Bitrix\Main\UI\Extension::load("ui.core");
\Bitrix\Main\UI\Extension::load("ui.notification");
\Bitrix\Main\UI\Extension::load("ajax");

$productOptionsEnabled = Configurator::isProductOptionsEnabled();

// Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/assets/js/slider.min.js');
$appJsPath = SITE_TEMPLATE_PATH . '/assets/js/app.min.js';
$appJsAbsolutePath = $_SERVER['DOCUMENT_ROOT'] . $appJsPath;
$appJsVersion = file_exists($appJsAbsolutePath) ? filemtime($appJsAbsolutePath) : time();
Asset::getInstance()->addString('<script type="module" src="'.$appJsPath.'?v='.$appJsVersion.'"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/slider.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/popup.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/cataloge.min.js"></script>');
Asset::getInstance()->addString('<script>window.__BRAKES_PRODUCT_OPTIONS_ENABLED__ = ' . ($productOptionsEnabled ? 'true' : 'false') . ';</script>', true);
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/product-options.js"></script>');
if (in_array($APPLICATION->GetCurPage(false), ['/basket/', '/personal/cart/'], true)) { // Подключаем скрипт только на странице корзины
   Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/basket-page.min.js"></script>');
}
if ($APPLICATION->GetCurPage(false) === '/personal/order/') {
    Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/order-page.min.css');
}
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/dev/auth.js?v='.time().'"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/dev/favorites.js?v='.time().'"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/dev/basket-actions.js?v='.time().'"></script>');

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/app.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/slider.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/index.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/catalog-tabs.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/other-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/popup.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/contacts-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/login-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/basket-page.min.css');
$unifiedCssAbsolutePath = $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . '/assets/css/dev/header-footer-unified.css';
$unifiedCssVersion = file_exists($unifiedCssAbsolutePath) ? filemtime($unifiedCssAbsolutePath) : time();
if (in_array($APPLICATION->GetCurPage(false), ['/', '/index.php'], true)) {
    Asset::getInstance()->addString(
        '<link rel="preload" as="image" href="' . SITE_TEMPLATE_PATH . '/assets/home-main-dist/video/hero-poster-mobile.webp" media="(max-width: 767.98px)" fetchpriority="high">',
        false,
        \Bitrix\Main\Page\AssetLocation::AFTER_CSS
    );
}
Asset::getInstance()->addString(
    '<link rel="stylesheet" href="' . SITE_TEMPLATE_PATH . '/assets/css/dev/header-footer-unified.css?v=' . $unifiedCssVersion . '">',
    false,
    \Bitrix\Main\Page\AssetLocation::AFTER_CSS
);

$favoritesClientState = FavoritesManager::getClientState();
$basketSummary = BasketManager::getSummary();
$basketCount = $basketSummary['count'] ?? 0;
Asset::getInstance()->addString('<script>window.__FAVORITES__ = ' . Json::encode($favoritesClientState) . '</script>', true);
Asset::getInstance()->addString("<script>BX.message({'ERROR_FAVORITES_TOGGLE': 'Не удалось обновить избранное.'});</script>", true);

$favoritesIds = $favoritesClientState['items'] ?? [];
$favoriteProductData = FavoritesManager::getFavoritesProductsData($favoritesIds);
$favoritesPopupHtml = FavoritesManager::buildFavoritesPopupHtml($favoriteProductData);
$sitePhonePath = function_exists('brakes_contact_include_path')
    ? brakes_contact_include_path('phone')
    : SITE_DIR . 'include/contacts/phone.php';
$sitePhoneText = function_exists('brakes_contact_include_text')
    ? brakes_contact_include_text($sitePhonePath, '+7 903 765-76-38')
    : '+7 903 765-76-38';
$sitePhoneHref = function_exists('brakes_contact_phone_href')
    ? brakes_contact_phone_href($sitePhoneText)
    : 'tel:+79037657638';
$siteWorktimePath = function_exists('brakes_contact_include_path')
    ? brakes_contact_include_path('worktime')
    : SITE_DIR . 'include/contacts/worktime.php';
$siteWorktimeText = function_exists('brakes_contact_include_text')
    ? brakes_contact_include_text($siteWorktimePath, 'Работаем пн-вс, с 9 до 21')
    : 'Работаем пн-вс, с 9 до 21';

Asset::getInstance()->addString('<meta charset="'.LANG_CHARSET.'">');
Asset::getInstance()->addString(
    '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-SemiBold.woff2" as="font" type="font/woff2" crossorigin="anonymous" media="(min-width: 768px)">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Regular.woff2" as="font" type="font/woff2" crossorigin="anonymous" media="(min-width: 768px)">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Medium.woff2" as="font" type="font/woff2" crossorigin="anonymous" media="(min-width: 768px)">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Bold.woff2" as="font" type="font/woff2" crossorigin="anonymous" media="(min-width: 768px)">'
);
Asset::getInstance()->addString(
    '<link rel="shortcut icon" href="'.SITE_TEMPLATE_PATH
    .'/assets/img/favicon.ico">'
);


?>
<!doctype html>
<html lang="ru">
<head>
    <?php $APPLICATION->ShowHead(); ?>
    <title><?php $APPLICATION->ShowTitle(); ?></title>
</head>
<?php
$bodyClass = $APPLICATION->GetCurPage(false) === '/personal/order/' ? 'bx-soa-order-page' : '';
?>
<body<?= $bodyClass !== '' ? ' class="'.$bodyClass.'"' : '' ?>>
<?php $APPLICATION->ShowPanel(); ?>
<div class="wrapper">
    <header data-fls-header="" class="header header--unified">
        <div class="header__top">
            <div class="header__container header__top-container">
                <div class="burger" id="burger">
                    <span></span>
                </div>

                <?php
                $APPLICATION->IncludeComponent(
                    'brakes:menu',
                    'header_unified',
                    [
                        'ROOT_MENU_TYPE' => 'top',
                    ]
                );
                ?>

                <div class="header__sign"
                     data-fls-dynamic=".header__top-nav, 479.98, 0">
                    <?if($USER->IsAuthorized()):?>
                        <!-- Отображение для авторизованного пользователя -->
                        <div class="header__sign-user-info">
                            <span class="header__sign-user"><?= $USER->GetFirstName() ?: $USER->GetLogin() ?></span>
                        </div>
                        <?
                        // Обработка выхода пользователя
                        if (isset($_GET['logout']) && $_GET['logout'] === 'yes') {
                            $USER->Logout();
                            LocalRedirect('/');
                        }
                        ?>
                        <a href="?logout=yes"
                           class="header__sign-out header__sign-link"
                           title="Выйти">
                            Выйти
                        </a>
                        <a href="#"
                           class="header__sign-up header__sign-link">Профиль</a>
                    <?else:?>
                        <!-- Отображение для неавторизованного пользователя -->
                        <a href="/login/"
                           class="header__sign-in header__sign-link">
                            <img class="header__sign-icon"
                                 src="<?= SITE_TEMPLATE_PATH ?>/assets/img/sign-in.svg"
                                 alt="Image">
                            Вход
                        </a>
                        <a href="/register/"
                           class="header__sign-up header__sign-link">Регистрация</a>
                    <?endif?>
                </div>
            </div>
        </div>
        <div class="header__bottom">
            <div class="header__container header__bottom-container">
                <a class="header__logo logo"
                   data-fls-dynamic=".header__top-container, 767.98, 0, .header__top"
                   href="<?=SITE_DIR?>">
                    <picture>
                        <source media="(max-width: 600px)"
                                srcset="<?= SITE_TEMPLATE_PATH ?>/assets/img/logo-600.webp"
                                type="image/webp">
                        <source media="(max-width: 1200px)"
                                srcset="<?= SITE_TEMPLATE_PATH ?>/assets/img/logo-1200.webp"
                                type="image/webp">
                        <img class="header__logo-img logo-img" alt="Image"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/logo.webp">
                    </picture>
                </a>
                <div class="header__menu menu">
                    <button type="button" data-fls-menu=""
                            class="menu__icon icon-menu">
                        <img class="menu__icon-img"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/burger.svg"
                             alt="Image">
                        <p class="menu__name">Каталог</p>
                        <span></span>
                    </button>
                    <nav class="menu__body"
                         data-header-catalog-menu
                         data-load-url="/local/ajax/header-catalog-menu.php"
                         aria-busy="false">
                        <div class="menu__container">
                            <div class="menu__list">
                                <a class="menu__item" href="/catalog/">Перейти в каталог</a>
                            </div>
                        </div>
                    </nav>
                </div>
                <form id="header-search-form" class="header__search" action="/search/" method="get">
                    <button class="header__search-mobile search-mobile"
                            id="header__search-mobile"
                            type="button">
                        <img class="search-mobile__img"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/search-mobile.svg"
                             alt="Image">
                        <p class="search-mobile__text">Поиск</p>
                    </button>
                    <div class="header__search-box"
                         data-fls-dynamic=".header__bottom-container, 479.98, 0">
                        <input class="header__search-input" type="text" name="q" form="header-search-form"
                               value="<?= htmlspecialcharsbx((string)($_REQUEST['q'] ?? '')) ?>"
                               placeholder="Поиск">
                        <button class="header__search-btn" type="submit" form="header-search-form">
                            <svg class="header__search-icon">
                                <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/img/spritemap.svg#sprite-search"></use>
                            </svg>
                        </button>
                    </div>
                </form>
                <div class="header__controls">
                    <div class="header__like header__controls-btn"
                         data-fls-dynamic=".header__bottom-container, 479.98, 2">
                        <svg class="header__like-icon header__controls-icon">
                            <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/img/spritemap.svg#sprite-like"></use>
                        </svg>
                        <p class="header__like-text">Избранное</p>
                        <span data-fls-like="" class="header__like-quantity cart__quantity"><?= $favoritesClientState['count'] ?></span>
                    </div>
                    <a class="header__cart header__controls-btn"
                       href="/personal/cart/">
                        <svg class="header__cart-icon header__controls-icon">
                            <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/img/spritemap.svg#sprite-cart"></use>
                        </svg>
                        <span class="header__cart-quantity cart__quantity"
                              data-fls-addtocart=""><?= htmlspecialcharsbx((string)$basketCount) ?></span>
                    </a>
                </div>
                <div class="header__info">
                    <a class="header__tel" href="<?= htmlspecialcharsbx($sitePhoneHref) ?>"><?php
                        if (function_exists('brakes_contact_include_area')) {
                            brakes_contact_include_area($sitePhonePath, $sitePhoneText);
                        } else {
                            echo htmlspecialcharsbx($sitePhoneText);
                        }
                    ?></a>
                    <p class="header__time"><?php
                        if (function_exists('brakes_contact_include_area')) {
                            brakes_contact_include_area($siteWorktimePath, $siteWorktimeText);
                        } else {
                            echo htmlspecialcharsbx($siteWorktimeText);
                        }
                    ?></p>
                    <a class="header__tel--mobile"
                       data-fls-dynamic=".header__top-container, 576, 1, .header__top"
                       href="<?= htmlspecialcharsbx($sitePhoneHref) ?>">
                        <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/mobile-tel.svg"
                             alt="Image">
                    </a>
                </div>
                <?if($USER->IsAuthorized()):?>
                    <!-- Кнопка профиля для авторизованного пользователя -->
                    <button class="header__profil" title="Профиль пользователя">
                        <img class="header__profil-img"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/sign-in.svg"
                             alt="Image">
                        <p class="header__profil-text"><?= $USER->GetFirstName() ?: $USER->GetLogin() ?></p>
                    </button>
                <?else:?>
                    <!-- Кнопка входа для неавторизованного пользователя -->
                    <a href="/login/" class="header__profil" style="text-decoration: none; color: inherit;">
                        <img class="header__profil-img"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/sign-in.svg"
                             alt="Image">
                        <p class="header__profil-text">Вход</p>
                    </a>
                <?endif?>
            </div>
            <div class="header__favorit-box favorit-box">
                <div class="favorit-box__container">
                    <div class="favorit-box__top">
                        <h2 class="favorit-box__title">Избранное</h2>
                        <button class="favorit-box__close">
                            <img class="favorit-box__close-icon"
                                 src="<?= SITE_TEMPLATE_PATH ?>/assets/img/close.svg"
                                 alt="Image">
                        </button>
                    </div>
                    <div class="favorit-box__body">
                        <?= $favoritesPopupHtml ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
