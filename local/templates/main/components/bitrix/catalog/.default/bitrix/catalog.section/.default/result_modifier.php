<?php

use App\Brakes\Helper\FavoritesManager;
use App\Brakes\Helper\Image;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$makeFileItem = static function (int $fileId): ?array {
    if ($fileId <= 0) {
        return null;
    }

    $fileArray = \CFile::GetFileArray($fileId);
    $src = is_array($fileArray) && !empty($fileArray['SRC'])
        ? (string)$fileArray['SRC']
        : (string)\CFile::GetPath($fileId);
    if ($src === '') {
        return null;
    }

    return [
        'src' => $src,
        'width' => (int)($fileArray['WIDTH'] ?? 0),
        'height' => (int)($fileArray['HEIGHT'] ?? 0),
        'cached' => false,
    ];
};

foreach ($arResult['ITEMS'] as $i => $item) {
    $imageData = null;
    $fileValues = $item['PROPERTIES']['LINK_PHOTO_FILE']['VALUE'] ?? [];

    if (is_array($fileValues) && !empty($fileValues)) {
        $fileId = (int)reset($fileValues);
        if ($fileId > 0) {
            $imageData = Image::resizeByPreset($fileId, Image::PRESET_CATALOG_TILE);
        }
    }

    if ($imageData === null || empty($imageData['src'])) {
        // LINK_PHOTO_FILE пуст: пробуем файлы 1С (DETAIL → MORE_PHOTO → PREVIEW).
        $fallbackFileId = 0;

        $detailPicture = $item['DETAIL_PICTURE'] ?? null;
        if (is_array($detailPicture) && isset($detailPicture['ID'])) {
            $fallbackFileId = (int)$detailPicture['ID'];
        } elseif (is_scalar($detailPicture) && (int)$detailPicture > 0) {
            $fallbackFileId = (int)$detailPicture;
        }

        $morePhotoValues = $item['PROPERTIES']['MORE_PHOTO']['VALUE'] ?? null;
        if ($fallbackFileId <= 0) {
            if (is_array($morePhotoValues) && !empty($morePhotoValues)) {
                $fallbackFileId = (int)reset($morePhotoValues);
            } elseif (is_scalar($morePhotoValues) && (int)$morePhotoValues > 0) {
                $fallbackFileId = (int)$morePhotoValues;
            } else {
                // На случай если свойство не попало в выборку компонента
                $iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
                $elementId = (int)($item['ID'] ?? 0);
                if ($iblockId > 0 && $elementId > 0) {
                    $res = \CIBlockElement::GetProperty(
                        $iblockId,
                        $elementId,
                        ['sort' => 'asc', 'id' => 'asc'],
                        ['CODE' => 'MORE_PHOTO']
                    );
                    if ($row = $res->Fetch()) {
                        $fallbackFileId = (int)($row['VALUE'] ?? 0);
                    }
                }
            }
        }

        if ($fallbackFileId <= 0) {
            $previewPicture = $item['PREVIEW_PICTURE'] ?? null;
            if (is_array($previewPicture) && isset($previewPicture['ID'])) {
                $fallbackFileId = (int)$previewPicture['ID'];
            } elseif (is_scalar($previewPicture) && (int)$previewPicture > 0) {
                $fallbackFileId = (int)$previewPicture;
            }
        }

        if ($fallbackFileId > 0) {
            $imageData = $makeFileItem($fallbackFileId);
        }
    }

    if ($imageData === null || empty($imageData['src'])) {
        $source = $item['PROPERTIES']['LINK_PHOTO']['VALUE'] ?? '';
        if (is_string($source) && $source !== '') {
            $paths = array_filter(array_map('trim', explode(';', $source)));
            if (!empty($paths)) {
                $fallback = reset($paths);
                if (is_string($fallback) && $fallback !== '') {
                    $imageData = [
                        'src' => $fallback,
                        'width' => 0,
                        'height' => 0,
                        'cached' => false,
                    ];
                }
            }
        }
    }

    if ($imageData !== null) {
        $arResult['ITEMS'][$i]['IMAGE'] = $imageData;
        $arResult['ITEMS'][$i]['IMG'] = $imageData['src'] ?? '';
    }

}

// Expand items by body-level sections to provide a strict mark/model/body context.
$iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
if ($iblockId > 0 && !empty($arResult['ITEMS']) && class_exists('CIBlockElement') && class_exists('CIBlockSection')) {
    $contextSectionId = (int)($arParams['SECTION_ID'] ?? 0);
    if ($contextSectionId <= 0) {
        $contextSectionId = (int)($arResult['SECTION']['ID'] ?? 0);
    }
    $allowedSectionIds = null;
    if ($contextSectionId > 0) {
        $currentSection = \CIBlockSection::GetList(
            [],
            ['IBLOCK_ID' => $iblockId, 'ID' => $contextSectionId],
            false,
            ['ID', 'LEFT_MARGIN', 'RIGHT_MARGIN']
        )->Fetch();
        if ($currentSection && isset($currentSection['LEFT_MARGIN'], $currentSection['RIGHT_MARGIN'])) {
            $allowedSectionIds = [];
            $sectionsRes = \CIBlockSection::GetList(
                [],
                [
                    'IBLOCK_ID' => $iblockId,
                    '>=LEFT_MARGIN' => $currentSection['LEFT_MARGIN'],
                    '<=RIGHT_MARGIN' => $currentSection['RIGHT_MARGIN'],
                ],
                false,
                ['ID']
            );
            while ($sectionRow = $sectionsRes->Fetch()) {
                $sectionId = (int)($sectionRow['ID'] ?? 0);
                if ($sectionId > 0) {
                    $allowedSectionIds[$sectionId] = true;
                }
            }
        }
    }
    $elementIds = [];
    foreach ($arResult['ITEMS'] as $item) {
        $elementId = (int)($item['ID'] ?? 0);
        if ($elementId > 0) {
            $elementIds[] = $elementId;
        }
    }
    $elementIds = array_values(array_unique($elementIds));

    if ($elementIds !== []) {
        $groupsByElement = [];
        $groupsRes = \CIBlockElement::GetElementGroups(
            $elementIds,
            true,
            ['ID', 'IBLOCK_ELEMENT_ID', 'IBLOCK_SECTION_ID', 'XML_ID', 'EXTERNAL_ID', 'NAME', 'CODE', 'DEPTH_LEVEL']
        );
        while ($group = $groupsRes->Fetch()) {
            $elementId = (int)($group['IBLOCK_ELEMENT_ID'] ?? 0);
            $sectionId = (int)($group['ID'] ?? 0);
            if ($elementId <= 0 || $sectionId <= 0) {
                continue;
            }
            $groupsByElement[$elementId][] = $group;
        }

        $buildSectionContext = static function (int $sectionId) use ($iblockId): array {
            static $cache = [];
            if (isset($cache[$sectionId])) {
                return $cache[$sectionId];
            }

            $chainRows = [];
            $chainRes = \CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME', 'CODE'], true);
            if (is_array($chainRes)) {
                $chainRows = $chainRes;
            } elseif ($chainRes instanceof \CDBResult) {
                while ($row = $chainRes->Fetch()) {
                    $chainRows[] = $row;
                }
            }

            $codes = [];
            $names = [];
            foreach ($chainRows as $row) {
                $code = isset($row['CODE']) ? (string)$row['CODE'] : '';
                $name = isset($row['NAME']) ? (string)$row['NAME'] : '';
                if ($code !== '') {
                    $codes[] = $code;
                }
                if ($name !== '') {
                    $names[] = $name;
                }
            }

            $contextLabel = '';
            if ($names !== []) {
                $contextLabel = implode(' ', array_slice($names, -3));
            }

            $cache[$sectionId] = [
                'section_id' => $sectionId,
                'section_path' => $codes !== [] ? implode('/', $codes) : '',
                'context_label' => $contextLabel,
            ];

            return $cache[$sectionId];
        };

        $getElementCode = static function (array $item): string {
            $elementCode = (string)($item['CODE'] ?? '');
            if ($elementCode !== '') {
                return $elementCode;
            }

            $detailUrl = (string)($item['DETAIL_PAGE_URL'] ?? '');
            if ($detailUrl !== '') {
                $path = (string)parse_url($detailUrl, PHP_URL_PATH);
                $path = trim($path, '/');
                if ($path !== '') {
                    $parts = explode('/', $path);
                    $elementCode = (string)end($parts);
                }
            }

            return $elementCode;
        };

        $expandedItems = [];
        foreach ($arResult['ITEMS'] as $item) {
            $elementId = (int)($item['ID'] ?? 0);
            if ($elementId <= 0) {
                $expandedItems[] = $item;
                continue;
            }

            $groups = $groupsByElement[$elementId] ?? [];
            if ($groups === []) {
                $expandedItems[] = $item;
                continue;
            }

            $bodySectionIds = [];
            foreach ($groups as $group) {
                $xmlId = (string)($group['XML_ID'] ?? $group['EXTERNAL_ID'] ?? '');
                if ($xmlId !== '' && strncmp($xmlId, 'BRKS:BODY:', 10) === 0) {
                    $sectionId = (int)($group['ID'] ?? 0);
                    if (is_array($allowedSectionIds) && !isset($allowedSectionIds[$sectionId])) {
                        continue;
                    }
                    if ($sectionId > 0) {
                        $bodySectionIds[$sectionId] = true;
                    }
                }
            }

            if ($bodySectionIds === []) {
                if (is_array($allowedSectionIds)) {
                    continue;
                }
                $expandedItems[] = $item;
                continue;
            }

            $elementCode = $getElementCode($item);
            foreach (array_keys($bodySectionIds) as $sectionId) {
                $context = $buildSectionContext($sectionId);
                $contextPath = (string)($context['section_path'] ?? '');
                $contextLabel = (string)($context['context_label'] ?? '');

                $newItem = $item;
                $newItem['CONTEXT_SECTION_ID'] = $sectionId;
                $newItem['CONTEXT_SECTION_PATH'] = $contextPath;
                $newItem['CONTEXT_LABEL'] = $contextLabel;
                $newItem['CONTEXT_UNIQUE_ID'] = $elementId . '_' . $sectionId;

                if ($elementCode !== '' && $contextPath !== '') {
                    $detailUrl = '/catalog/' . $contextPath . '/' . $elementCode . '/';
                    $newItem['DETAIL_PAGE_URL'] = $detailUrl;
                    $newItem['~DETAIL_PAGE_URL'] = $detailUrl;
                }

                $expandedItems[] = $newItem;
            }
        }

        $arResult['ITEMS'] = $expandedItems;
    }
}

$formatCurrency = static function (float $value, string $currency): string {
    if (\Bitrix\Main\Loader::includeModule('currency') && class_exists(\CCurrencyLang::class)) {
        return \CCurrencyLang::CurrencyFormat($value, $currency, true);
    }

    return number_format($value, 0, '.', ' ') . ' ' . $currency;
};

 $resolveItemPrice = static function (array $item) use ($formatCurrency): ?array {
     $priceRow = null;
     $prices = $item['ITEM_PRICES'] ?? null;
     if (is_array($prices) && $prices !== []) {
         $selected = isset($item['ITEM_PRICE_SELECTED']) ? (int)$item['ITEM_PRICE_SELECTED'] : null;
         if ($selected !== null && isset($prices[$selected]) && is_array($prices[$selected])) {
             $priceRow = $prices[$selected];
         } else {
             $priceRow = reset($prices);
         }
     }

     if ($priceRow === null && isset($item['MIN_PRICE']) && is_array($item['MIN_PRICE'])) {
         $min = $item['MIN_PRICE'];
         $value = isset($min['VALUE']) && is_numeric($min['VALUE']) ? (float)$min['VALUE'] : null;
         if ($value !== null) {
             $currency = isset($min['CURRENCY']) && is_string($min['CURRENCY']) ? (string)$min['CURRENCY'] : 'RUB';
             $formatted = isset($min['PRINT_VALUE']) && is_string($min['PRINT_VALUE'])
                 ? $min['PRINT_VALUE']
                 : $formatCurrency($value, $currency);
             return [
                 'VALUE' => $value,
                 'CURRENCY' => $currency,
                 'FORMATTED' => $formatted,
             ];
         }
     }

     if (is_array($priceRow)) {
         $value = isset($priceRow['PRICE']) && is_numeric($priceRow['PRICE']) ? (float)$priceRow['PRICE'] : null;
         if ($value !== null) {
             $currency = isset($priceRow['CURRENCY']) && is_string($priceRow['CURRENCY']) ? (string)$priceRow['CURRENCY'] : 'RUB';
             $formatted = null;
             if (isset($priceRow['PRINT_PRICE']) && is_string($priceRow['PRINT_PRICE'])) {
                 $formatted = $priceRow['PRINT_PRICE'];
             } elseif (isset($priceRow['PRICE_FORMATTED']) && is_string($priceRow['PRICE_FORMATTED'])) {
                 $formatted = $priceRow['PRICE_FORMATTED'];
             }
             if ($formatted === null) {
                 $formatted = $formatCurrency($value, $currency);
             }
             return [
                 'VALUE' => $value,
                 'CURRENCY' => $currency,
                 'FORMATTED' => $formatted,
             ];
         }
     }

     return null;
 };

if (!empty($arResult['ITEMS']) && \Bitrix\Main\Loader::includeModule('catalog')) {
    $missingIds = [];
    foreach ($arResult['ITEMS'] as $index => $item) {
        $itemId = (int)($item['ID'] ?? 0);
        if ($itemId <= 0) {
            continue;
        }

        $resolved = $resolveItemPrice($item);
        if ($resolved !== null) {
            $arResult['ITEMS'][$index]['BASE_PRICE_VALUE'] = $resolved['VALUE'];
            $arResult['ITEMS'][$index]['BASE_PRICE_CURRENCY'] = $resolved['CURRENCY'];
            $arResult['ITEMS'][$index]['BASE_PRICE_FORMATTED'] = $resolved['FORMATTED'];
            continue;
        }

        $missingIds[$itemId] = true;
    }

    if ($missingIds !== []) {
        $baseGroup = \CCatalogGroup::GetBaseGroup();
        if (is_array($baseGroup) && isset($baseGroup['ID'])) {
            $priceMap = [];
            $res = \CPrice::GetList(
                [],
                [
                    'CATALOG_GROUP_ID' => (int)$baseGroup['ID'],
                    '@PRODUCT_ID' => array_keys($missingIds),
                ],
                false,
                false,
                ['PRODUCT_ID', 'PRICE', 'CURRENCY']
            );
            while ($row = $res->Fetch()) {
                $productId = (int)($row['PRODUCT_ID'] ?? 0);
                if ($productId <= 0 || !isset($row['PRICE'])) {
                    continue;
                }
                $priceMap[$productId] = [
                    'PRICE' => (float)$row['PRICE'],
                    'CURRENCY' => $row['CURRENCY'] ?? 'RUB',
                ];
            }

            foreach ($arResult['ITEMS'] as $index => $item) {
                $itemId = (int)($item['ID'] ?? 0);
                if ($itemId <= 0 || !isset($missingIds[$itemId])) {
                    continue;
                }
                if (!isset($priceMap[$itemId])) {
                    $arResult['ITEMS'][$index]['BASE_PRICE_VALUE'] = 0.0;
                    $arResult['ITEMS'][$index]['BASE_PRICE_CURRENCY'] = 'RUB';
                    $arResult['ITEMS'][$index]['BASE_PRICE_FORMATTED'] = '0';
                    continue;
                }

                $value = $priceMap[$itemId]['PRICE'];
                $currency = (string)$priceMap[$itemId]['CURRENCY'];
                $arResult['ITEMS'][$index]['BASE_PRICE_VALUE'] = $value;
                $arResult['ITEMS'][$index]['BASE_PRICE_CURRENCY'] = $currency;
                $arResult['ITEMS'][$index]['BASE_PRICE_FORMATTED'] = $formatCurrency($value, $currency);
            }
        }
    }
}

if (class_exists(FavoritesManager::class) && !empty($arResult['ITEMS'])) {
    $state = FavoritesManager::getClientState();
    $favoritesMeta = (!empty($state['meta']) && is_array($state['meta'])) ? $state['meta'] : [];
    $favoriteKeys = array_keys($favoritesMeta);
    $favoritesDataMap = [];

    if ($favoriteKeys !== []) {
        $favoritesData = FavoritesManager::getFavoritesProductsData($favoriteKeys);
        foreach ($favoritesData as $favoriteItem) {
            $key = isset($favoriteItem['FAVORITES_KEY']) ? (string)$favoriteItem['FAVORITES_KEY'] : '';
            if ($key !== '') {
                $favoritesDataMap[$key] = $favoriteItem;
            }
        }
    }

    if ($favoritesDataMap !== []) {
        foreach ($arResult['ITEMS'] as $i => $item) {
            $productId = (int)($item['ID'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $contextSectionId = (int)($item['CONTEXT_SECTION_ID'] ?? 0);
            $contextSectionPath = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
            $favoriteKey = FavoritesManager::buildFavoriteKey($productId, [
                'section_id' => $contextSectionId,
                'section_path' => $contextSectionPath,
            ]);

            if ($favoriteKey === '' || !isset($favoritesDataMap[$favoriteKey])) {
                continue;
            }

            $favoriteData = $favoritesDataMap[$favoriteKey];

            if (!empty($favoriteData['PRICE_DATA']) && is_array($favoriteData['PRICE_DATA'])) {
                $arResult['ITEMS'][$i]['FAVORITES_PRICE'] = $favoriteData['PRICE_DATA'];
            } elseif (!empty($favoriteData['PRICE_HTML'])) {
                $arResult['ITEMS'][$i]['FAVORITES_PRICE'] = [
                    'PRICE_FORMATTED' => $favoriteData['PRICE_HTML'],
                ];
            }

            if (!empty($favoriteData['OPTIONS_UNWRAPPED']) && is_array($favoriteData['OPTIONS_UNWRAPPED'])) {
                $optionsRaw = $favoriteData['OPTIONS_UNWRAPPED'];
                $arResult['ITEMS'][$i]['FAVORITES_OPTIONS'] = $optionsRaw;
                $arResult['ITEMS'][$i]['FAVORITES_OPTIONS_JSON'] = json_encode(
                    ['options' => $optionsRaw],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }

            if (!empty($favoriteData['SELECTED_OPTIONS']) && is_array($favoriteData['SELECTED_OPTIONS'])) {
                $arResult['ITEMS'][$i]['FAVORITES_SELECTED_OPTIONS'] = $favoriteData['SELECTED_OPTIONS'];
            }
        }
    }
}
