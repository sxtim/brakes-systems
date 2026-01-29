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
];

$favoriteSelectedOptions = $defaultSelectedOptions;
$productMeta = null;
$optionsFromMeta = [];
$favoritePriceFormatted = null;
$favoriteContextSectionId = 0;
$favoriteContextSectionPath = '';
 $favkeyParam = trim((string)($_GET['favkey'] ?? ''));
 $favkeyContext = [];

if (!empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    $path = array_values($arResult['SECTION']['PATH']);
    $pathCount = count($path);
    if ($pathCount > 0) {
        $favoriteContextSectionId = (int)($path[$pathCount - 1]['ID'] ?? 0);
        $contextCodes = array_map(static function ($item) {
            return isset($item['CODE']) ? (string)$item['CODE'] : '';
        }, $path);
        $contextCodes = array_values(array_filter($contextCodes, static fn($code) => $code !== ''));
        if (!empty($contextCodes)) {
            $favoriteContextSectionPath = implode('/', $contextCodes);
        }
    }
}

if ($favkeyParam !== '') {
    if (preg_match('/^(\\d+):(s|p|n)(.*)$/', $favkeyParam, $matches)) {
        $favType = $matches[2];
        $favTail = $matches[3] ?? '';
        if ($favType === 's') {
            $favSectionId = (int)$favTail;
            if ($favSectionId > 0) {
                $favkeyContext['section_id'] = $favSectionId;
            }
        } elseif ($favType === 'p') {
            $favSectionPath = trim((string)$favTail, " \t\n\r\0\x0B/");
            if ($favSectionPath !== '') {
                $favkeyContext['section_path'] = $favSectionPath;
            }
        }
    }
}

$resolveSectionPath = static function (int $sectionId) use ($arParams): string {
    if ($sectionId <= 0 || empty($arParams['IBLOCK_ID']) || !Loader::includeModule('iblock')) {
        return '';
    }

    $nav = \CIBlockSection::GetNavChain((int)$arParams['IBLOCK_ID'], $sectionId, ['CODE']);
    $codes = [];
    while ($row = $nav->Fetch()) {
        if (!empty($row['CODE'])) {
            $codes[] = $row['CODE'];
        }
    }
    return $codes !== [] ? implode('/', $codes) : '';
};

if ($favoriteContextSectionId <= 0 && $favoriteContextSectionPath === '' && $favkeyContext !== []) {
    if (isset($favkeyContext['section_id'])) {
        $favoriteContextSectionId = (int)$favkeyContext['section_id'];
    }
    if (isset($favkeyContext['section_path'])) {
        $favoriteContextSectionPath = (string)$favkeyContext['section_path'];
    }
    if ($favoriteContextSectionPath === '' && $favoriteContextSectionId > 0) {
        $favoriteContextSectionPath = $resolveSectionPath($favoriteContextSectionId);
    }
}

if (class_exists(FavoritesManager::class)) {
    $state = FavoritesManager::getClientState();
    $meta = isset($state['meta']) && is_array($state['meta']) ? $state['meta'] : [];
    $metaByProduct = isset($state['metaByProduct']) && is_array($state['metaByProduct'])
        ? $state['metaByProduct']
        : [];
    $favoriteKey = FavoritesManager::buildFavoriteKey($arResult['ID'], [
        'section_id' => $favoriteContextSectionId,
        'section_path' => $favoriteContextSectionPath,
    ]);
    $productMeta = $favoriteKey !== '' ? ($meta[$favoriteKey] ?? null) : null;
    if (!is_array($productMeta)) {
        $productMeta = $metaByProduct[$arResult['ID']] ?? null;
    }
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

$twoPieceYesSelected = $twoPieceSelected === 'yes';
$twoPieceNoSelected = $twoPieceSelected !== 'yes';
$rotorPerforationSelected = $rotorSelected === 'perforation';
$rotorSlotsSelected = $rotorSelected === 'slots';
$rotorComboSelected = in_array($rotorSelected, ['perforation_slots', 'perforation_and_notches'], true);
$caliperStandardSelected = $caliperSelected === 'standard';
$caliperSpecialSelected = in_array($caliperSelected, ['special', 'custom_logo', 'custom'], true);

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
$basePriceFormatted = null;
$catalogModuleLoaded = Loader::includeModule('catalog');

$selectedPriceRow = null;
if (!empty($arResult['ITEM_PRICES']) && is_array($arResult['ITEM_PRICES'])) {
    $selectedIndex = isset($arResult['ITEM_PRICE_SELECTED']) ? (int)$arResult['ITEM_PRICE_SELECTED'] : null;
    if ($selectedIndex !== null && isset($arResult['ITEM_PRICES'][$selectedIndex])) {
        $selectedPriceRow = $arResult['ITEM_PRICES'][$selectedIndex];
    } else {
        $selectedPriceRow = reset($arResult['ITEM_PRICES']);
    }
}

if ($selectedPriceRow !== null && is_array($selectedPriceRow)) {
    if (isset($selectedPriceRow['PRICE']) && is_numeric($selectedPriceRow['PRICE'])) {
        $basePriceValue = (float)$selectedPriceRow['PRICE'];
        $basePriceCurrency = $selectedPriceRow['CURRENCY'] ?? 'RUB';
        $basePriceFormatted = $selectedPriceRow['PRINT_PRICE'] ?? $selectedPriceRow['PRICE_FORMATTED'] ?? null;
    }
}

if ($basePriceValue === null && isset($arResult['MIN_PRICE']) && is_array($arResult['MIN_PRICE'])) {
    if (isset($arResult['MIN_PRICE']['VALUE']) && is_numeric($arResult['MIN_PRICE']['VALUE'])) {
        $basePriceValue = (float)$arResult['MIN_PRICE']['VALUE'];
        $basePriceCurrency = $arResult['MIN_PRICE']['CURRENCY'] ?? 'RUB';
        $basePriceFormatted = $arResult['MIN_PRICE']['PRINT_VALUE'] ?? null;
    }
}

if ($basePriceValue === null && $catalogModuleLoaded) {
    $basePriceRow = \CPrice::GetBasePrice($arResult['ID']);
    if (is_array($basePriceRow) && isset($basePriceRow['PRICE'])) {
        $basePriceValue = (float)$basePriceRow['PRICE'];
        $basePriceCurrency = $basePriceRow['CURRENCY'] ?? 'RUB';
    }
}

if ($basePriceFormatted === null) {
    $basePriceFormatted = $basePriceValue !== null
        ? number_format($basePriceValue, 0, '.', ' ') . ' ' . htmlspecialcharsbx($basePriceCurrency)
        : '0';
}

$initialPriceFormatted = $basePriceFormatted;
if (is_string($favoritePriceFormatted) && $favoritePriceFormatted !== '') {
    $initialPriceFormatted = htmlspecialcharsback($favoritePriceFormatted);
}

$formatQuantity = static function (float $value): string {
    $rounded = round($value, 3);
    $intValue = (int)$rounded;
    if (abs($rounded - $intValue) < 0.0001) {
        return (string)$intValue;
    }
    $formatted = number_format($rounded, 3, '.', '');
    return rtrim(rtrim($formatted, '0'), '.');
};

$catalogQuantity = null;
$catalogQuantityExact = false;
$quantityCandidates = [
    $arResult['CATALOG_QUANTITY'] ?? null,
    $arResult['CATALOG']['QUANTITY'] ?? null,
    $arResult['PRODUCT']['QUANTITY'] ?? null,
];
foreach ($quantityCandidates as $candidate) {
    if ($candidate === null || $candidate === '') {
        continue;
    }
    if (is_numeric($candidate)) {
        $catalogQuantity = (float)$candidate;
        $catalogQuantityExact = true;
        break;
    }
}
if ($catalogQuantity === null && $catalogModuleLoaded && class_exists('CCatalogProduct')) {
    $catalogRow = \CCatalogProduct::GetByID((int)$arResult['ID']);
    if (is_array($catalogRow) && isset($catalogRow['QUANTITY']) && $catalogRow['QUANTITY'] !== '') {
        $catalogQuantity = (float)$catalogRow['QUANTITY'];
        $catalogQuantityExact = true;
    }
}
$catalogQuantityValue = $catalogQuantity ?? 0.0;
$detailInStock = $catalogQuantityValue > 0;
$detailStatusText = $detailInStock ? 'В наличии' : 'Под заказ';
$detailStatusClass = $detailInStock ? 'status-item--1' : 'status-item--3';
$detailQuantityLabel = $catalogQuantityExact ? $formatQuantity($catalogQuantityValue) : '';
$detailStatusTooltip = $detailQuantityLabel !== '' ? 'Остаток: ' . $detailQuantityLabel : '';

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
    $date = $parts[0];
    if ($date === '01.01.0001' || $date === '0001-01-01') {
        return '';
    }
    return $date;
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

// If the user came from global search (or from "viewed"), treat it as "no auto context":
// show full applicability instead of assuming the section-path is the user's selection.
$from = (string)($_GET['from'] ?? '');
if (($contextSectionId <= 0 && $contextSectionPath === '') && $favkeyContext !== []) {
    if (isset($favkeyContext['section_id'])) {
        $contextSectionId = (int)$favkeyContext['section_id'];
    }
    if (isset($favkeyContext['section_path'])) {
        $contextSectionPath = (string)$favkeyContext['section_path'];
    }
    if ($contextSectionPath === '' && $contextSectionId > 0) {
        $contextSectionPath = $resolveSectionPath($contextSectionId);
    }
}

$hasContext = ($contextSectionId > 0 || $contextSectionPath !== '');
$isNeutralFrom = ($from === 'search' || $from === 'viewed') && !$hasContext && $favkeyParam === '';
if ($isNeutralFrom) {
    $contextApplicability = null;
    $filteredApplicability = $applicabilityRows;
}

$actionsAllowed = !$isNeutralFrom && $contextApplicability !== null;

$traitsMap = [];
if (!empty($arResult['PROPERTIES']['CML2_TRAITS']['VALUE']) && is_array($arResult['PROPERTIES']['CML2_TRAITS']['VALUE'])) {
    $traitsValues = $arResult['PROPERTIES']['CML2_TRAITS']['VALUE'];
    $traitsDesc = $arResult['PROPERTIES']['CML2_TRAITS']['DESCRIPTION'] ?? [];
    foreach ($traitsValues as $k => $val) {
        $name = $traitsDesc[$k] ?? '';
        if (is_string($name) && $name !== '') {
            $traitsMap[$name] = (string)$val;
        }
    }
}

$crossNumbersRaw = trim((string)($traitsMap['Кросс номера'] ?? ''));
$crossRows = [];
if ($crossNumbersRaw !== '') {
    $pairs = array_values(array_filter(array_map('trim', explode(';', $crossNumbersRaw)), 'strlen'));
    foreach ($pairs as $pair) {
        $number = $pair;
        $brand = '';
        if (strpos($pair, '|') !== false) {
            [$number, $brand] = array_pad(explode('|', $pair, 2), 2, '');
        }
        $number = trim((string)$number);
        $brand = trim((string)$brand);
        if ($number === '') {
            continue;
        }
        $crossRows[] = [
            'NUMBER' => $number,
            'BRAND' => $brand,
        ];
    }
}

$oemNumbers = [];
$oemPropValue = $arResult['PROPERTIES']['OEM_NUMBERS']['VALUE'] ?? null;
if (is_string($oemPropValue)) {
    $oemNumbers = array_values(array_filter([trim($oemPropValue)], 'strlen'));
} elseif (is_array($oemPropValue)) {
    $oemNumbers = array_values(array_filter(array_map('trim', $oemPropValue), 'strlen'));
}
if (empty($oemNumbers) && !empty($crossRows)) {
    $oemNumbers = array_values(array_unique(array_map(static fn(array $row) => (string)$row['NUMBER'], $crossRows)));
}

$isPadsCategory = false;
if (!empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    foreach ($arResult['SECTION']['PATH'] as $section) {
        if (($section['CODE'] ?? '') === 'tormoznye_kolodki') {
            $isPadsCategory = true;
            break;
        }
    }
}
$isDiscsCategory = false;
if (!empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    foreach ($arResult['SECTION']['PATH'] as $section) {
        if (($section['CODE'] ?? '') === 'tormoznye_diski') {
            $isDiscsCategory = true;
            break;
        }
    }
}

$brandValue = trim((string)($arResult['PROPERTIES']['CML2_MANUFACTURER']['VALUE'] ?? ''));
if ($brandValue === '') {
    $brandValue = trim((string)($arResult['PROPERTIES']['MANUFACTURER']['VALUE'] ?? ''));
}
$axisValue = trim((string)($arResult['PROPERTIES']['INSTALLATION_AXIS']['VALUE'] ?? ''));
$articleValue = trim((string)($arResult['PROPERTIES']['CML2_ARTICLE']['VALUE'] ?? ''));

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
                <div class="main-details__status-item status-item--1<?=$detailInStock ? ' active' : ''?>"<?php if ($detailInStock && $detailStatusTooltip !== '') { ?> title="<?=htmlspecialcharsbx($detailStatusTooltip)?>"<?php } ?><?php if ($detailInStock && $detailQuantityLabel !== '') { ?> data-stock-qty="<?=htmlspecialcharsbx($detailQuantityLabel)?>"<?php } ?>>
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-1.svg" alt="Image">
                    <span class="main-details__status-text">В наличии</span>
                </div>
                <div class="main-details__status-item status-item--2">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-2.svg" alt="Image">
                    <span class="main-details__status-text">Нет в наличии</span>
                </div>
                <div class="main-details__status-item status-item--3<?=$detailInStock ? '' : ' active'?>"<?php if (!$detailInStock && $detailStatusTooltip !== '') { ?> title="<?=htmlspecialcharsbx($detailStatusTooltip)?>"<?php } ?><?php if (!$detailInStock && $detailQuantityLabel !== '') { ?> data-stock-qty="<?=htmlspecialcharsbx($detailQuantityLabel)?>"<?php } ?>>
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
            <?php if (!$isPadsCategory && !$isDiscsCategory): ?>
                <div class="main-details__feature">
                    <div class="main-cataloge__info">
                        <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                            <details class="spollers__item">
                                <summary class="main-details__feature-item main-cataloge__feature-item--big spollers__title">Плавающая конструкция диска</summary>
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
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($actionsAllowed): ?>
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
            <?php else: ?>
                <div class="main-details__shoping">
                    <button type="button" class="main-details__shoping-btn" disabled>
                        <span class="main-details__shoping-text">В корзину</span>
                    </button>
                </div>
                <div class="main-details__context-hint">
                    Чтобы купить или добавить в избранное, выберите автомобиль (поколение) в каталоге.
                    <a href="/catalog/">Подобрать по авто</a>
                </div>
            <?php endif; ?>
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

	    if ($isPadsCategory || $isDiscsCategory) {
	        ?>
                <div class="details-row">
                    <span class="details-label">Артикул:</span>
                    <span class="details-dots-wrap"><span class="details-dots"></span></span>
                    <span class="details-value"><?=htmlspecialcharsbx($articleValue !== '' ? $articleValue : '—')?></span>
                </div>
		        <div class="details-row">
		            <span class="details-label">Производитель:</span>
		            <span class="details-dots-wrap"><span class="details-dots"></span></span>
		            <span class="details-value"><?=htmlspecialcharsbx($brandValue !== '' ? $brandValue : '—')?></span>
		        </div>
		        <div class="details-row">
		            <span class="details-label">Ось:</span>
		            <span class="details-dots-wrap"><span class="details-dots"></span></span>
		            <span class="details-value"><?=htmlspecialcharsbx($axisValue !== '' ? $axisValue : '—')?></span>
		        </div>
		        <?php if (!empty($crossRows)): ?>
                    <details class="details-spoiler" id="oem-numbers">
                        <summary class="details-row details-spoiler__summary">
                            <span class="details-label">Оригинальный номер детали:</span>
                            <span class="details-dots-wrap"><span class="details-dots"></span></span>
                            <span class="details-value"><span class="details-spoiler__btn">Посмотреть</span></span>
                        </summary>
                        <div class="details-spoiler__body">
                            <div class="applicability-table__wrapper">
                                <table class="applicability-table">
                                    <thead>
                                    <tr>
                                        <th>Номер</th>
                                        <th>Бренд</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($crossRows as $row): ?>
                                        <tr>
                                            <td><?=htmlspecialcharsbx($row['NUMBER'])?></td>
                                            <td><?=htmlspecialcharsbx($row['BRAND'] !== '' ? $row['BRAND'] : '—')?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </details>
		        <?php else: ?>
                    <div class="details-row">
                        <span class="details-label">Оригинальный номер детали:</span>
                        <span class="details-dots-wrap"><span class="details-dots"></span></span>
                        <span class="details-value">—</span>
                    </div>
                <?php endif; ?>
	        <?php
	    } else {
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
	                <span class="details-dots-wrap"><span class="details-dots"></span></span>
	                <span class="details-value"><?=$prop['VALUE']?></span>
	            </div>
	            <?php
	        }
    }
    ?>

	    <?php if ($contextApplicability): ?>
	        <div class="details-row">
	            <span class="details-label">Марка:</span>
	            <span class="details-dots-wrap"><span class="details-dots"></span></span>
	            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['MARK'])?></span>
	        </div>
	        <div class="details-row">
	            <span class="details-label">Модель:</span>
	            <span class="details-dots-wrap"><span class="details-dots"></span></span>
	            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['MODEL'])?></span>
	        </div>
	        <div class="details-row">
	            <span class="details-label">Кузов:</span>
	            <span class="details-dots-wrap"><span class="details-dots"></span></span>
	            <span class="details-value"><?=htmlspecialcharsbx($contextApplicability['BODY'])?></span>
	        </div>
	        <?php if ($contextApplicability['DATE_RELEASE'] || $contextApplicability['DATE_END']): ?>
	            <div class="details-row">
	                <span class="details-label">Год начала выпуска:</span>
	                <span class="details-dots-wrap"><span class="details-dots"></span></span>
	                <span class="details-value"><?=htmlspecialcharsbx($formatDateShort($contextApplicability['DATE_RELEASE'] ?? '') ?: '—')?></span>
	            </div>
	            <div class="details-row">
	                <span class="details-label">Год окончания выпуска:</span>
	                <span class="details-dots-wrap"><span class="details-dots"></span></span>
	                <span class="details-value"><?=htmlspecialcharsbx($formatDateShort($contextApplicability['DATE_END'] ?? '') ?: '—')?></span>
	            </div>
	        <?php endif; ?>
	    <?php elseif (!$hasContext && !empty($filteredApplicability)): ?>
	        <div class="details-row applicability-row">
	            <span class="details-label">Применяемость:</span>
	            <span class="details-dots-wrap"><span class="details-dots"></span></span>
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
                                <td><?=htmlspecialcharsbx($formatDateShort($row['DATE_RELEASE'] ?? '') ?: '—')?></td>
                                <td><?=htmlspecialcharsbx($formatDateShort($row['DATE_END'] ?? '') ?: '—')?></td>
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
