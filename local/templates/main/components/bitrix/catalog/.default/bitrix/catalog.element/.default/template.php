<?php

use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$defaultSelectedOptions = [
    'two_piece_disc_construction' => 'no',
    'rotor_pattern' => 'perforation',
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
$allowedRotorValues = ['perforation', 'slots', 'perforation_slots', 'perforation_and_notches'];
if (!in_array($rotorSelected, $allowedRotorValues, true)) {
    $rotorSelected = 'perforation';
}
$caliperSelected = $favoriteSelectedOptions['caliper_logo'];
$handbrakeSelected = $favoriteSelectedOptions['electric_handbrake'];

$twoPieceYesSelected = $twoPieceSelected === 'yes';
$twoPieceNoSelected = $twoPieceSelected !== 'yes';
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

// Подготовка применяемости для вывода
$splitValues = static function ($value): array {
    $result = [];
    if (is_string($value)) {
        $parts = explode(';', $value);
        $result = $parts;
    } elseif (is_array($value)) {
        $result = $value;
    }
    return array_values(array_filter(array_map('trim', $result), 'strlen'));
};

$formatDateShort = static function ($value): string {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    $parts = explode(' ', $value);
    return $parts[0];
};

$markValues = $splitValues($arResult['PROPERTIES']['MARK']['VALUE'] ?? []);
$modelValues = $splitValues($arResult['PROPERTIES']['MODEL']['VALUE'] ?? []);
$bodyValues = $splitValues($arResult['PROPERTIES']['BODY']['VALUE'] ?? []);

// Годы выпуска: берём либо из профильных свойств, либо из CML2_TRAITS,
// в зависимости от того, где данные полнее для текущего товара.
$propDateStartRaw = $arResult['PROPERTIES']['DATE_RELEASE']['VALUE'] ?? null;
$propDateEndRaw = $arResult['PROPERTIES']['DATE_END']['VALUE'] ?? null;
$propDateStartValues = $splitValues($propDateStartRaw ?? []);
$propDateEndValues = $splitValues($propDateEndRaw ?? []);

$traitsDateStartRaw = null;
$traitsDateEndRaw = null;
if (!empty($arResult['PROPERTIES']['CML2_TRAITS']['VALUE']) && is_array($arResult['PROPERTIES']['CML2_TRAITS']['VALUE'])) {
    $traitsValues = $arResult['PROPERTIES']['CML2_TRAITS']['VALUE'];
    $traitsDesc = $arResult['PROPERTIES']['CML2_TRAITS']['DESCRIPTION'] ?? [];

    foreach ($traitsValues as $k => $val) {
        $name = $traitsDesc[$k] ?? '';
        if ($name === 'Год начала выпуска') {
            $traitsDateStartRaw = (string)$val;
        } elseif ($name === 'Год окончания выпуска') {
            $traitsDateEndRaw = (string)$val;
        }
    }
}

$traitsDateStartValues = $splitValues($traitsDateStartRaw ?? []);
$traitsDateEndValues = $splitValues($traitsDateEndRaw ?? []);

$applicabilityRows = [];
$rowCount = min(count($markValues), count($modelValues), count($bodyValues));

$useTraitsDates = false;
if ($rowCount > 0) {
    $propHasAll = count($propDateStartValues) >= $rowCount && count($propDateEndValues) >= $rowCount;
    $traitsHasAll = count($traitsDateStartValues) >= $rowCount && count($traitsDateEndValues) >= $rowCount;

    if ($traitsHasAll && !$propHasAll) {
        $useTraitsDates = true;
    } elseif ($traitsHasAll && $propHasAll) {
        // если оба источника полные, можно оставить приоритет у отдельного свойства
        $useTraitsDates = false;
    } elseif (!$propHasAll && (count($traitsDateStartValues) > count($propDateStartValues) || count($traitsDateEndValues) > count($propDateEndValues))) {
        $useTraitsDates = true;
    }
}

$dateStartValues = $useTraitsDates ? $traitsDateStartValues : $propDateStartValues;
$dateEndValues = $useTraitsDates ? $traitsDateEndValues : $propDateEndValues;

for ($i = 0; $i < $rowCount; $i++) {
    $applicabilityRows[] = [
        'MARK' => $markValues[$i] ?? '',
        'MODEL' => $modelValues[$i] ?? '',
        'BODY' => $bodyValues[$i] ?? '',
        'DATE_RELEASE' => $dateStartValues[$i] ?? '',
        'DATE_END' => $dateEndValues[$i] ?? '',
    ];
}

$contextApplicability = null;
$contextSectionId = 0;
$contextSectionPath = '';
$filteredApplicability = $applicabilityRows;
if (!empty($applicabilityRows) && !empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    $path = array_values($arResult['SECTION']['PATH']);
    $normalize = static function ($value): string {
        return mb_strtolower(trim((string)$value));
    };

    $pathCount = count($path);
    // Определяем бренд/модель/кузов по длине пути: 3+ уровня - последние три, 2 уровня - марка+модель, 1 - только марка
    $brandName = $brandCode = $modelName = $modelCode = $bodyName = $bodyCode = '';
    if ($pathCount >= 3) {
        $brand = $path[$pathCount - 3];
        $model = $path[$pathCount - 2];
        $body  = $path[$pathCount - 1];
    } elseif ($pathCount === 2) {
        $brand = $path[0];
        $model = $path[1];
        $body  = [];
    } else {
        $brand = $path[0];
        $model = [];
        $body  = [];
    }
    $brandName = $brand['NAME'] ?? '';
    $brandCode = $brand['CODE'] ?? '';
    $modelName = $model['NAME'] ?? '';
    $modelCode = $model['CODE'] ?? '';
    $bodyName  = $body['NAME'] ?? '';
    $bodyCode  = $body['CODE'] ?? '';

    $brandNameN = $normalize($brandName);
    $brandCodeN = $normalize($brandCode);
    $modelNameN = $normalize($modelName);
    $modelCodeN = $normalize($modelCode);
    $bodyNameN  = $normalize($bodyName);
    $bodyCodeN  = $normalize($bodyCode);

    $contextSectionId = 0;
    $contextSectionPath = '';
    if ($pathCount > 0) {
        $contextSectionId = (int)($path[$pathCount - 1]['ID'] ?? 0);
        $contextCodes = array_map(static function ($item) {
            return isset($item['CODE']) ? (string)$item['CODE'] : '';
        }, $path);
        $contextCodes = array_values(array_filter($contextCodes, static fn($code) => $code !== ''));
        if (!empty($contextCodes)) {
            $contextSectionPath = implode('/', $contextCodes);
        }
    }

    // Фильтрация в зависимости от уровня пути: 3+ (марка+модель+кузов), 2 (марка+модель), 1 (марка), иначе полный список
    if ($pathCount >= 3) {
        $filteredApplicability = array_values(array_filter($applicabilityRows, static function ($row) use ($normalize, $brandNameN, $modelNameN, $modelCodeN, $bodyNameN, $bodyCodeN) {
            $markN  = $normalize($row['MARK']);
            $modelN = $normalize($row['MODEL']);
            $bodyN  = $normalize($row['BODY']);
            return ($markN === $brandNameN)
                && (($modelNameN !== '' && $modelN === $modelNameN) || ($modelCodeN !== '' && $modelN === $modelCodeN))
                && (($bodyNameN !== '' && $bodyN === $bodyNameN) || ($bodyCodeN !== '' && $bodyN === $bodyCodeN));
        }));
        $contextApplicability = $filteredApplicability[0] ?? null;
    } elseif ($pathCount === 2) {
        $filteredApplicability = array_values(array_filter($applicabilityRows, static function ($row) use ($normalize, $brandNameN, $modelNameN, $modelCodeN) {
            $markN  = $normalize($row['MARK']);
            $modelN = $normalize($row['MODEL']);
            return ($markN === $brandNameN)
                && (($modelNameN !== '' && $modelN === $modelNameN) || ($modelCodeN !== '' && $modelN === $modelCodeN));
        }));
        // здесь показываем все модели бренда, контекст не нужен
        $contextApplicability = null;
    } elseif ($pathCount === 1) {
        $filteredApplicability = array_values(array_filter($applicabilityRows, static function ($row) use ($normalize, $brandNameN) {
            $markN  = $normalize($row['MARK']);
            return ($markN === $brandNameN);
        }));
        $contextApplicability = null;
    }
}

// Debug: выводим цепочку разделов/значения применяемости в HTML-комментарий
$sectionPathInfo = [];
if (!empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    foreach ($arResult['SECTION']['PATH'] as $sec) {
        $sectionPathInfo[] = ($sec['NAME'] ?? '') . ' [' . ($sec['CODE'] ?? '') . ']';
    }
}
$debugLine = date('c')
    . ' element_id=' . (int)$arResult['ID']
    . ' uri=' . ($_SERVER['REQUEST_URI'] ?? '')
    . ' section_path=' . (empty($sectionPathInfo) ? 'no' : implode(' / ', $sectionPathInfo))
    . ' brand=' . $brandName . '|' . $brandCode
    . ' model=' . $modelName . '|' . $modelCode
    . ' body=' . $bodyName . '|' . $bodyCode
    . ' context_mark=' . ($contextApplicability['MARK'] ?? '')
    . ' context_model=' . ($contextApplicability['MODEL'] ?? '')
    . ' context_body=' . ($contextApplicability['BODY'] ?? '')
    . ' marks=' . (is_array($markValues) ? implode(';', $markValues) : '')
    . ' models=' . (is_array($modelValues) ? implode(';', $modelValues) : '')
    . ' bodies=' . (is_array($bodyValues) ? implode(';', $bodyValues) : '');
echo "<!-- applicability_debug: " . htmlspecialcharsbx($debugLine) . " -->";
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
        <div class="main__details main-details" data-fls-dynamic=".main__overlay, 1199.98" data-fls-like-product="<?=$arResult['ID']?>"<?php if ($contextSectionId > 0) { ?> data-context-section-id="<?=$contextSectionId?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?>>
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
                            <summary class="main-details__feature-item spollers__title">Тип ротора:</summary>
                            <div class="main-cataloge__sublist spollers__body">
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
            <div class="main-details__shoping" data-fls-like-product="<?=$arResult['ID']?>"<?php if ($contextSectionId > 0) { ?> data-context-section-id="<?=$contextSectionId?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?>>
                <button data-fls-addtocart-button="" class="main-details__shoping-btn" data-options='<?=$optionsAttr?>'>
                    <span class="main-details__shoping-text">В корзину</span>
                </button>
                <button data-fls-like-image="" data-fls-like-button="" data-product-id="<?=$arResult['ID']?>" data-options='<?=$optionsAttr?>' <?php if ($contextSectionId > 0) { ?>data-context-section-id="<?=$contextSectionId?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?> class="main-details__shoping-like"></button>
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

    $skipCodes = ['MARK', 'MODEL', 'BODY', 'DATE_RELEASE', 'DATE_END'];
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

        if (in_array($prop['CODE'], $skipCodes, true)) {
            continue;
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

    <?php if ($contextApplicability): ?>
        <div class="details-row">
            <span class="details-label">Марка:</span>
            <span class="details-dots"></span>
            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['MARK'])?></span>
        </div>
        <div class="details-row">
            <span class="details-label">Модель:</span>
            <span class="details-dots"></span>
            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['MODEL'])?></span>
        </div>
        <div class="details-row">
            <span class="details-label">Кузов:</span>
            <span class="details-dots"></span>
            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['BODY'])?></span>
        </div>
        <?php if ($contextApplicability['DATE_RELEASE'] || $contextApplicability['DATE_END']): ?>
            <div class="details-row">
                <span class="details-label">Год начала выпуска:</span>
                <span class="details-dots"></span>
                <span class="details-value"><?=htmlspecialcharsbx($formatDateShort($contextApplicability['DATE_RELEASE'] ?? ''))?></span>
            </div>
            <div class="details-row">
                <span class="details-label">Год окончания выпуска:</span>
                <span class="details-dots"></span>
                <span class="details-value"><?=htmlspecialcharsbx($formatDateShort($contextApplicability['DATE_END'] ?? ''))?></span>
            </div>
        <?php endif; ?>
    <?php elseif (!empty($filteredApplicability)): ?>
        <div class="details-row applicability-row">
            <span class="details-label">Применяемость:</span>
            <span class="details-dots"></span>
            <span class="details-value">
                <div class="applicability-table__wrapper">
                    <table class="applicability-table">
                        <thead>
                        <tr>
                            <th>Марка</th>
                            <th>Модель</th>
                            <th>Кузов</th>
                            <th>Начало</th>
                            <th>Окончание</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($filteredApplicability as $row): ?>
                            <tr>
                                <td><?=htmlspecialcharsbx($row['MARK'])?></td>
                                <td><?=htmlspecialcharsbx($row['MODEL'])?></td>
                                <td><?=htmlspecialcharsbx($row['BODY'])?></td>
                                <td><?=htmlspecialcharsbx($formatDateShort($row['DATE_RELEASE'] ?? ''))?></td>
                                <td><?=htmlspecialcharsbx($formatDateShort($row['DATE_END'] ?? ''))?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </span>
        </div>
    <?php endif; ?>
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
