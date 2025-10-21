<?php

use App\Brakes\Helper\FavoritesManager;
use App\Brakes\Helper\Image;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$favoritesMeta = [];
$favoritesDataMap = [];
if (class_exists(FavoritesManager::class)) {
    $state = FavoritesManager::getClientState();
    if (!empty($state['meta']) && is_array($state['meta'])) {
        $favoritesMeta = $state['meta'];
    }

    if ($favoritesMeta !== []) {
        $favoriteIds = array_map(static fn($key) => (int)$key, array_keys($favoritesMeta));
        $favoriteIds = array_values(array_filter($favoriteIds, static fn($id) => $id > 0));

        if ($favoriteIds !== []) {
            $favoritesData = FavoritesManager::getFavoritesProductsData($favoriteIds);
            foreach ($favoritesData as $favoriteItem) {
                $id = (int)($favoriteItem['ID'] ?? 0);
                if ($id > 0) {
                    $favoritesDataMap[$id] = $favoriteItem;
                }
            }
        }
    }
}

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

    $productId = (int)$item['ID'];
    if ($productId <= 0 || !isset($favoritesDataMap[$productId])) {
        continue;
    }

    $favoriteData = $favoritesDataMap[$productId];

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
