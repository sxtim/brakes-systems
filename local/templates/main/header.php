<?php

use \Bitrix\Main\Page\Asset;
use \Bitrix\Main\Web\Json;
use App\Brakes\Helper\FavoritesManager;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (\Bitrix\Main\Loader::includeModule('pull')) {
    \Bitrix\Main\UI\Extension::load("pull.client");
}
\Bitrix\Main\UI\Extension::load("ui.core");
\Bitrix\Main\UI\Extension::load("ui.notification");
\Bitrix\Main\UI\Extension::load("ajax");

// Asset::getInstance()->addJs(SITE_TEMPLATE_PATH.'/assets/js/slider.min.js');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/app.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/slider.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/popup.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/cataloge.min.js"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/product-options.js"></script>');
if ($APPLICATION->GetCurPage(false) === '/basket/') { // Подключаем скрипт только на странице корзины
   Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/basket-page.min.js"></script>');
}
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/dev/auth.js?v='.time().'"></script>');
Asset::getInstance()->addString('<script type="module" src="'.SITE_TEMPLATE_PATH.'/assets/js/dev/favorites.js?v='.time().'"></script>');

Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/app.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/slider.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/index.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/other-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/popup.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/contacts-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/login-page.min.css');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/basket-page.min.css');

$favoritesClientState = FavoritesManager::getClientState();
Asset::getInstance()->addString('<script>window.__FAVORITES__ = ' . Json::encode($favoritesClientState) . '</script>', true);
Asset::getInstance()->addString("<script>BX.message({'ERROR_FAVORITES_TOGGLE': 'Не удалось обновить избранное.'});</script>", true);

$favoriteProductData = [];
$favoritesIds = $favoritesClientState['items'] ?? [];

if (!empty($favoritesIds) && \Bitrix\Main\Loader::includeModule('iblock')) {
    $elements = [];
    $result = \CIBlockElement::GetList([], ['ID' => $favoritesIds], false, false, ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'PREVIEW_PICTURE']);
    while ($row = $result->GetNext()) {
        $id = (int)$row['ID'];
        $elements[$id] = [
            'ID' => $id,
            'NAME' => $row['~NAME'] ?? $row['NAME'],
            'URL' => $row['DETAIL_PAGE_URL'],
            'PICTURE' => $row['PREVIEW_PICTURE'] ? \CFile::GetPath($row['PREVIEW_PICTURE']) : null,
            'PRICE' => null,
        ];
    }

    if (!empty($elements) && \Bitrix\Main\Loader::includeModule('catalog') && \Bitrix\Main\Loader::includeModule('currency')) {
        foreach ($elements as $elementId => &$element) {
            $priceData = \CCatalogProduct::GetOptimalPrice($elementId);

            if ($priceData && isset($priceData['RESULT_PRICE']['DISCOUNT_PRICE'])) {
                $element['PRICE'] = \CCurrencyLang::CurrencyFormat(
                    $priceData['RESULT_PRICE']['DISCOUNT_PRICE'],
                    $priceData['RESULT_PRICE']['CURRENCY']
                );
            }
        }
        unset($element);
    }

    foreach ($favoritesIds as $favoriteId) {
        $favoriteId = (int)$favoriteId;
        if (isset($elements[$favoriteId])) {
            $favoriteProductData[] = $elements[$favoriteId];
        }
    }
}

Asset::getInstance()->addString('<meta charset="'.LANG_CHARSET.'">');
Asset::getInstance()->addString(
    '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-SemiBold.woff2" as="font" type="font/woff2" crossorigin="anonymous">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Regular.woff2" as="font" type="font/woff2" crossorigin="anonymous">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Medium.woff2" as="font" type="font/woff2" crossorigin="anonymous">'
);
Asset::getInstance()->addString(
    '<link rel="preload" href="'.SITE_TEMPLATE_PATH
    .'/assets/fonts/Montserrat-Bold.woff2" as="font" type="font/woff2" crossorigin="anonymous">'
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
    <title><?php $APPLICATION->ShowTitle(false); ?></title>
</head>
<body>
<?php $APPLICATION->ShowPanel(); ?>
<div class="wrapper">
    <header data-fls-header="" class="header">
        <div class="header__top">
            <div class="header__container header__top-container">
                <div class="burger" id="burger">
                    <span></span>
                </div>

                <?$APPLICATION->IncludeComponent(
                        "brakes:menu",
                        "main"
                );?>

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
                    <?$APPLICATION->IncludeComponent(
                        "bitrix:catalog.section.list",
                        "",
                        Array(
                            "ADDITIONAL_COUNT_ELEMENTS_FILTER" => "additionalCountFilter",
                            "VIEW_MODE" => "TEXT",
                            "SHOW_PARENT_NAME" => "Y",
                            "IBLOCK_TYPE" => 'catalog',
                            "IBLOCK_ID" => 1,
                            "SECTION_ID" => "",
                            "SECTION_CODE" => "",
                            "SECTION_URL" => "",
                            "COUNT_ELEMENTS" => "Y",
                            "COUNT_ELEMENTS_FILTER" => "CNT_ACTIVE",
                            "HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS" => "N",
                            "TOP_DEPTH" => "3",
                            "SECTION_FIELDS" => "",
                            "SECTION_USER_FIELDS" => "",
                            "ADD_SECTIONS_CHAIN" => "Y",
                            "CACHE_TYPE" => "A",
                            "CACHE_TIME" => "36000000",
                            "CACHE_NOTES" => "",
                            "CACHE_GROUPS" => "Y",
                            "SECTION_USER_FIELDS" => [
                                "UF_SVG",
                            ]
                        )
                    );?>
                </div>
                <form class="header__search" action="#">
                    <button class="header__search-mobile search-mobile"
                            id="header__search-mobile">
                        <img class="search-mobile__img"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/search-mobile.svg"
                             alt="Image">
                        <p class="search-mobile__text">Поиск</p>
                    </button>
                    <div class="header__search-box"
                         data-fls-dynamic=".header__bottom-container, 479.98, 0">
                        <input class="header__search-input" type="text"
                               placeholder="Поиск">
                        <button class="header__search-btn">
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
                       href="/basket/">
                        <svg class="header__cart-icon header__controls-icon">
                            <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/assets/img/spritemap.svg#sprite-cart"></use>
                        </svg>
                        <span class="header__cart-quantity cart__quantity"
                              data-fls-addtocart="">0</span>
                    </a>
                </div>
                <div class="header__info">
                    <a class="header__tel" href="tel:84955555555">8 495
                        555-55-55</a>
                    <p class="header__time">Работаем пн-вс, с 9 до 21</p>
                    <a class="header__tel--mobile"
                       data-fls-dynamic=".header__top-container, 576, 1, .header__top"
                       href="tel:84955555555">
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
                        <?php if (!empty($favoriteProductData)): ?>
                            <?php foreach ($favoriteProductData as $favoriteProduct): ?>
                                <a class="favorit-box__item" data-fls-like-product="<?= $favoriteProduct['ID'] ?>" href="<?= htmlspecialcharsbx($favoriteProduct['URL']) ?>">
                                    <div class="favorit-box__item-foto">
                                        <?php if ($favoriteProduct['PICTURE']): ?>
                                            <img class="favorit-box__img" alt="<?= htmlspecialcharsbx($favoriteProduct['NAME']) ?>" src="<?= htmlspecialcharsbx($favoriteProduct['PICTURE']) ?>">
                                        <?php else: ?>
                                            <img class="favorit-box__img" alt="<?= htmlspecialcharsbx($favoriteProduct['NAME']) ?>" src="<?= SITE_TEMPLATE_PATH ?>/assets/img/favorite/1.webp">
                                        <?php endif; ?>
                                    </div>
                                    <div class="favorit-box__inner">
                                        <h3 class="favorit-box__item-title"><?= htmlspecialcharsbx($favoriteProduct['NAME']) ?></h3>
                                        <?php if ($favoriteProduct['PRICE']): ?>
                                            <div class="favorit-box__item-bottom">
                                                <div class="favorit-box__item-price"><?= $favoriteProduct['PRICE'] ?></div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <button class="favorit-box__delete" data-fls-like-button data-product-id="<?= $favoriteProduct['ID'] ?>" aria-label="Удалить из избранного">
                                        <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/favorite/trash.svg" alt="Удалить">
                                    </button>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="favorit-box__empty">В избранном пока нет товаров.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>
