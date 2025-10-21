<?php

use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$defaultSelectedOptions = [
    'two_piece_disc_construction' => 'no',
    'rotor_pattern' => 'none',
    'caliper_logo' => 'standard',
    'electric_handbrake' => 'no',
];

$favoriteSelectedOptions = $defaultSelectedOptions;
$productMeta = null;
$optionsFromMeta = [];
$favoritePriceFormatted = null;

if (class_exists(FavoritesManager::class)) {
    $state = FavoritesManager::getClientState();
    $meta = isset($state['meta']) && is_array($state['meta']) ? $state['meta'] : [];
    $productMeta = $meta[$arResult['ID']] ?? null;
    if (is_array($productMeta)) {
        $optionsFromMeta = FavoritesManager::prepareOptionsPayload($productMeta['options'] ?? [], false);
        if (is_array($optionsFromMeta)) {
            foreach ($optionsFromMeta as $code => $value) {
                if (!array_key_exists($code, $favoriteSelectedOptions)) {
                    continue;
                }
                $favoriteSelectedOptions[$code] = is_string($value) ? $value : (is_scalar($value) ? strtolower((string)$value) : $favoriteSelectedOptions[$code]);
            }
        }

        $metaPrice = $productMeta['price']['formatted'] ?? null;
        if (is_string($metaPrice) && $metaPrice !== '') {
            $favoritePriceFormatted = $metaPrice;
        }
    }
}

$twoPieceSelected = $favoriteSelectedOptions['two_piece_disc_construction'];
$rotorSelected = $favoriteSelectedOptions['rotor_pattern'];
$caliperSelected = $favoriteSelectedOptions['caliper_logo'];
$handbrakeSelected = $favoriteSelectedOptions['electric_handbrake'];

$twoPieceYesSelected = $twoPieceSelected === 'yes';
$twoPieceNoSelected = $twoPieceSelected !== 'yes';
$rotorNoneSelected = $rotorSelected === 'none';
$rotorPerforationSelected = $rotorSelected === 'perforation';
$rotorSlotsSelected = $rotorSelected === 'slots';
$rotorComboSelected = in_array($rotorSelected, ['perforation_slots', 'perforation_and_notches'], true);
$caliperStandardSelected = $caliperSelected === 'standard';
$caliperSpecialSelected = in_array($caliperSelected, ['special', 'custom_logo', 'custom'], true);
$handbrakeYesSelected = $handbrakeSelected === 'yes';
$handbrakeNoSelected = $handbrakeSelected !== 'yes';

$optionsAttrPayload = FavoritesManager::prepareOptionsPayload($favoriteSelectedOptions);
$optionsAttrJson = !empty($optionsAttrPayload)
    ? json_encode($optionsAttrPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    : '{}';
$optionsAttr = htmlspecialcharsbx($optionsAttrJson ?: '{}');

if ($favoritePriceFormatted === null && !empty($optionsAttrPayload)) {
    try {
        $priceData = FavoritesManager::getProductPrice($arResult['ID'], $optionsAttrPayload);
        if (is_array($priceData) && !empty($priceData['PRICE_FORMATTED'])) {
            $favoritePriceFormatted = (string)$priceData['PRICE_FORMATTED'];
        }
    } catch (\Throwable $exception) {
        // ignore preload errors to keep page rendering
    }
}

$basePriceValue = null;
$basePriceCurrency = 'RUB';

if (Loader::includeModule('catalog')) {
    $basePriceRow = \CPrice::GetBasePrice($arResult['ID']);
    if (is_array($basePriceRow) && isset($basePriceRow['PRICE'])) {
        $basePriceValue = (float)$basePriceRow['PRICE'];
        $basePriceCurrency = $basePriceRow['CURRENCY'] ?? 'RUB';
    }
}

if ($basePriceValue === null && isset($arResult['ITEM_PRICES'][0]['PRICE'])) {
    $basePriceValue = (float)$arResult['ITEM_PRICES'][0]['PRICE'];
    $basePriceCurrency = $arResult['ITEM_PRICES'][0]['CURRENCY'] ?? 'RUB';
}

$basePriceFormatted = $basePriceValue !== null
    ? number_format($basePriceValue, 0, '.', ' ') . ' ' . htmlspecialcharsbx($basePriceCurrency)
    : '';

$initialPriceFormatted = $basePriceFormatted;
if (is_string($favoritePriceFormatted) && $favoritePriceFormatted !== '') {
    $initialPriceFormatted = htmlspecialcharsback($favoritePriceFormatted);
}
?>
<div class="main__overlay">
    <div class="main__content">
        <div class="main__media">
            <?php

            if ($arResult['GALLERY']) {
            ?>
                <div data-fls-slider="" class="swiper main-swiper">
                    <div class="swiper-wrapper main-swiper__wrapper gallery" data-fls-gallery="">
                        <?php

                        foreach ($arResult['GALLERY'] as $item) {
                            $main = $item['main']['src'] ?? $item['original'] ?? '';
                            $mainWidth = isset($item['main']['width']) ? (int)$item['main']['width'] : 0;
                            $mainHeight = isset($item['main']['height']) ? (int)$item['main']['height'] : 0;
                            if ($main === '') {
                                continue;
                            }
                        ?>
                            <div class="swiper-slide main-swiper__slide">
                                <a class="main-swiper__gallery__image gallery__image"
                                   href="<?=$main?>"
                                   data-src="<?=$main?>">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=$main?>" type="image/jpeg">
                                        <source media="(max-width: 1200px)" srcset="<?=$main?>" type="image/jpeg">
                                        <img class="main-swiper__img gallery__preview"
                                             alt="Img"
                                             src="<?=$main?>"
                                             <?php if ($mainWidth > 0) { ?>width="<?=$mainWidth?>"<?php } ?>
                                             <?php if ($mainHeight > 0) { ?>height="<?=$mainHeight?>"<?php } ?>>
                                    </picture>
                                </a>
                            </div>
                        <?php

                        }
                        ?>
                    </div>
                </div>

                <!-- Слайдер мініатюр -->
                <div class="thumbs-swiper__overlay">
                    <button class="thumbs-swiper__prev"></button>
                    <div data-fls-slider="" class="swiper thumbs-swiper">
                        <div class="thumbs-swiper__wrapper swiper-wrapper">
                            <?php

                            foreach ($arResult['GALLERY'] as $item) {
                                $thumb = $item['thumb']['src'] ?? $item['main']['src'] ?? $item['original'] ?? '';
                                if ($thumb === '') {
                                    continue;
                                }
                                $thumbWidth = isset($item['thumb']['width']) ? (int)$item['thumb']['width'] : 0;
                                $thumbHeight = isset($item['thumb']['height']) ? (int)$item['thumb']['height'] : 0;
                            ?>
                                <div class="thumbs-swiper__slide swiper-slide">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=$thumb?>" type="image/jpeg">
                                        <source media="(max-width: 1200px)" srcset="<?=$thumb?>" type="image/jpeg">
                                        <img class="thumbs-swiper__img"
                                             alt="Img"
                                             src="<?=$thumb?>"
                                             <?php if ($thumbWidth > 0) { ?>width="<?=$thumbWidth?>"<?php } ?>
                                             <?php if ($thumbHeight > 0) { ?>height="<?=$thumbHeight?>"<?php } ?>>
                                    </picture>
                                </div>
                            <?php

                            }
                            ?>
                        </div>
                    </div>
                    <button class="thumbs-swiper__next"></button>
                </div>
            <?php

            }
            ?>
        </div>
        <div class="main__details main-details" data-fls-dynamic=".main__overlay, 1199.98">
            <div data-fls-dynamic=".main__media, 1199.98, 0" class="main-details__status">
                <div class="main-details__status-item status-item--1 active">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-1.svg" alt="Image">
                    <span class="main-details__status-text">В наличии</span>
                </div>
                <div class="main-details__status-item status-item--2">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-2.svg" alt="Image">
                    <span class="main-details__status-text">Нет в наличии</span>
                </div>
                <div class="main-details__status-item status-item--3">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-3.svg" alt="Image">
                    <span class="main-details__status-text">Под заказ</span>
                </div>
            </div>
            <div class="main-details__price">
<!--                <div class="main-details__price-top">-->
<!--                    <span class="main-details__price-action">-25%</span>-->
<!--                    <span class="main-details__price-old">170 000 ₽%</span>-->
<!--                </div>-->
                <span class="main-details__price-new"><?= $initialPriceFormatted ?></span>
            </div>
            <div class="main-details__feature">
                <div class="main-cataloge__info">
                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                        <details class="spollers__item">
                            <summary class="main-details__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item<?= $twoPieceYesSelected ? ' selected' : '' ?>">Да</div>
                                <div class="main-cataloge__sublist-item<?= $twoPieceNoSelected ? ' selected' : '' ?>">Нет</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Рисунок ротора:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item<?= $rotorNoneSelected ? ' selected' : '' ?>">НЕТ</div>
                                <div class="main-cataloge__sublist-item<?= $rotorPerforationSelected ? ' selected' : '' ?>">ПЕРФОРАЦИЯ</div>
                                <div class="main-cataloge__sublist-item<?= $rotorSlotsSelected ? ' selected' : '' ?>">НАСЕЧКИ</div>
                                <div class="main-cataloge__sublist-item<?= $rotorComboSelected ? ' selected' : '' ?>">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Лого на суппорт:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item<?= $caliperStandardSelected ? ' selected' : '' ?>">Стандартный</div>
                                <div class="main-cataloge__sublist-item<?= $caliperSpecialSelected ? ' selected' : '' ?>">Особый логотип</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Электроручник</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item<?= $handbrakeYesSelected ? ' selected' : '' ?>">Да</div>
                                <div class="main-cataloge__sublist-item<?= $handbrakeNoSelected ? ' selected' : '' ?>">Нет</div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
            <div class="main-details__shoping" data-fls-like-product="<?=$arResult['ID']?>">
                <button data-fls-addtocart-button="" class="main-details__shoping-btn" data-options='<?=$optionsAttr?>'>
                    <span class="main-details__shoping-text">В корзину</span>
                </button>
                <button data-fls-like-image="" data-fls-like-button="" data-product-id="<?=$arResult['ID']?>" data-options='<?=$optionsAttr?>' class="main-details__shoping-like"></button>
            </div>
            <button data-fls-popup-link="speedBuy"
                    class="main-details__buy"
                    href="#"
                    data-product-name="<?= $arResult['NAME'] ?>"
                    data-product-url="<?= $arResult['DETAIL_PAGE_URL'] ?>"
                    data-options='<?=$optionsAttr?>'>Купить в один клик</button>
        </div>
    </div>
</div>
<div class="main__services">
    <?php

    if ($arResult['DELIVERY']) {
    ?>
        <a href="<?=$arResult['DELIVERY']['UF_LINK']?>" target="_blank" class="main__services-item main__services-item--1">
            <img class="main__services-icon" src="<?=$arResult['DELIVERY']['UF_FILE']?>" alt="Image">
            <span class="main__services-text"><?=$arResult['DELIVERY']['UF_DESCRIPTION']?></span>
        </a>
    <?php

    }
    ?>
    <div class="main__services-item main__services-item--2">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-2.svg" alt="Image">
        <span class="main__services-text">Доставка CDEK</span>
    </div>
    <div class="main__services-item main__services-item--3 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-3.svg" alt="Image">
        <span class="main__services-text">Самовывоз</span>
    </div>
    <div class="main__services-item main__services-item--4 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-4.svg" alt="Image">
        <span class="main__services-text">Гарантия</span>
    </div>
    <div class="main__services-item main__services-item--5 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-5.svg" alt="Image">
        <span class="main__services-text">Рассрочка</span>
    </div>
</div>
<div class="main__descr">
    <?php

    if (!empty($arResult['DETAIL_TEXT'])) {
        echo $arResult['DETAIL_TEXT'];
    } elseif (!empty($arResult['PROPERTIES']['CUSTOM_DESCRIPTION']['~VALUE']['TEXT'])) {
        echo $arResult['PROPERTIES']['CUSTOM_DESCRIPTION']['~VALUE']['TEXT'];
    }
    ?>
</div>
<div class="main__details details">
    <?php

    foreach ($arResult['PROPERTIES'] as $prop) {
        switch ($prop['CODE']) {
            case 'VIDEO_LINK':
            case 'LINK_PHOTO':
            case 'LINK_PHOTO_FILE':
            case 'CML2_TRAITS':
            case 'COLOR':
            case 'RECOMMENDED':
            case 'CML2_BASE_UNIT':
            case 'CUSTOM_DESCRIPTION':
            case 'DELIVERY':
                continue(2);
        }

        if (!$prop['VALUE']) {
            continue;
        }
    ?>
        <div class="details-row">
            <span class="details-label"><?=$prop['NAME']?>:</span>
            <span class="details-dots"></span>
            <span class="details-value"><?=$prop['VALUE']?></span>
        </div>
    <?php

    }
    ?>
</div>
<div data-fls-popup="speedBuy" aria-hidden="true" class="popup">
    <div data-fls-popup-wrapper="" class="popup__wrapper">
        <div data-fls-popup-body="" class="popup__body">
            <button data-fls-popup-close="" type="button" class="popup__close">
                <svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
                    <path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
                </svg>
            </button>
            <div data-fls-popup-content="" class="popup__text">
                <?$APPLICATION->IncludeComponent(
                    "bitrix:form.result.new",
                    "one_click",
                    Array(
                        "AJAX_MODE" => "Y",
                        "AJAX_OPTION_ADDITIONAL" => "",
                        "AJAX_OPTION_HISTORY" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "CACHE_TIME" => "3600",
                        "CACHE_TYPE" => "A",
                        "CHAIN_ITEM_LINK" => "",
                        "CHAIN_ITEM_TEXT" => "",
                        "EDIT_URL" => "",
                        "IGNORE_CUSTOM_TEMPLATE" => "N",
                        "LIST_URL" => "",
                        "SEF_MODE" => "N",
                        "SUCCESS_URL" => "",
                        "USE_EXTENDED_ERRORS" => "N",
                        "VARIABLE_ALIASES" => Array(
                            "RESULT_ID" => "RESULT_ID",
                            "WEB_FORM_ID" => "WEB_FORM_ID"
                        ),
                        "WEB_FORM_ID" => "QUICK_ORDER"
                    )
                );?>
            </div>
        </div>
    </div>
</div>
