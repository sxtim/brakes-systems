<?php

use App\Brakes\Helper\Image;
use App\Brakes\Helper\Storage;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

class CatalogViewedComponent extends \CBitrixComponent
{
    public function executeComponent(): void
    {
        $appendQueryParam = static function (string $url, string $param, string $value): string {
            if ($url === '' || $param === '') {
                return $url;
            }

            $parts = parse_url($url);
            $path = $parts['path'] ?? $url;
            $query = $parts['query'] ?? '';
            $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

            parse_str($query, $params);
            if (!isset($params[$param]) || (string)$params[$param] === '') {
                $params[$param] = $value;
            }

            $newQuery = http_build_query($params);
            return $path . ($newQuery !== '' ? ('?' . $newQuery) : '') . $fragment;
        };

        $parseFavoriteKey = static function (string $value): array {
            $value = trim($value);
            if ($value === '') {
                return [];
            }

            if (preg_match('/^(\\d+):(s|p|n)(.*)$/', $value, $matches)) {
                $productId = (int)$matches[1];
                $type = $matches[2];
                $tail = $matches[3] ?? '';
                $context = [];

                if ($type === 's') {
                    $sectionId = (int)$tail;
                    if ($sectionId > 0) {
                        $context['section_id'] = $sectionId;
                    }
                } elseif ($type === 'p') {
                    $sectionPath = trim((string)$tail, " \t\n\r\0\x0B/");
                    if ($sectionPath !== '') {
                        $context['section_path'] = $sectionPath;
                    }
                }

                return [
                    'productId' => $productId,
                    'context' => $context,
                ];
            }

            $productId = (int)$value;
            return $productId > 0 ? ['productId' => $productId, 'context' => []] : [];
        };

        $normalizeContext = static function ($value): array {
            if (!is_array($value)) {
                return [];
            }

            $sectionId = isset($value['section_id']) ? (int)$value['section_id'] : (isset($value['sectionId']) ? (int)$value['sectionId'] : 0);
            $sectionPath = isset($value['section_path']) ? (string)$value['section_path'] : (isset($value['sectionPath']) ? (string)$value['sectionPath'] : '');

            $context = [];
            if ($sectionId > 0) {
                $context['section_id'] = $sectionId;
            }
            if ($sectionPath !== '') {
                $sectionPath = trim((string)$sectionPath, " \t\n\r\0\x0B/");
                if ($sectionPath !== '') {
                    $context['section_path'] = $sectionPath;
                }
            }

            return $context;
        };

        $buildContextSectionPath = static function (array $context, int $iblockId): string {
            $path = '';

            if (!empty($context['section_path']) && is_string($context['section_path'])) {
                $path = trim($context['section_path'], " \t\n\r\0\x0B/");
            } elseif (!empty($context['section_id']) && $context['section_id'] > 0 && Loader::includeModule('iblock')) {
                $nav = \CIBlockSection::GetNavChain($iblockId, (int)$context['section_id'], ['CODE']);
                $codes = [];
                while ($row = $nav->Fetch()) {
                    if (!empty($row['CODE'])) {
                        $codes[] = $row['CODE'];
                    }
                }
                if (!empty($codes)) {
                    $path = implode('/', $codes);
                }
            }

            return $path;
        };

        $buildContextLabel = static function (array $context, int $iblockId): string {
            if (empty($context) || empty($context['section_id']) || !Loader::includeModule('iblock')) {
                return '';
            }

            $nav = \CIBlockSection::GetNavChain($iblockId, (int)$context['section_id'], ['NAME']);
            $names = [];
            while ($row = $nav->Fetch()) {
                if (!empty($row['NAME'])) {
                    $names[] = $row['NAME'];
                }
            }
            $names = array_slice($names, -3);
            $names = array_values(array_filter($names, static fn($name) => is_string($name) && trim($name) !== ''));
            return implode(' ', $names);
        };

        $buildDetailUrlWithContext = static function (array $fields, array $context) use ($buildContextSectionPath): string {
            $detailUrl = isset($fields['DETAIL_PAGE_URL']) ? (string)$fields['DETAIL_PAGE_URL'] : '#';
            $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
            $sectionPath = $buildContextSectionPath($context, $iblockId);

            if ($sectionPath === '') {
                return $detailUrl;
            }

            $fieldsWithContext = $fields;
            $fieldsWithContext['SECTION_CODE_PATH'] = $sectionPath;
            $sectionParts = array_values(array_filter(explode('/', $sectionPath), static fn($part) => $part !== ''));
            if (!empty($sectionParts)) {
                $fieldsWithContext['SECTION_CODE'] = end($sectionParts);
            }

            $template = '';
            if ($iblockId > 0 && Loader::includeModule('iblock')) {
                $template = (string)\CIBlock::GetArrayByID($iblockId, 'DETAIL_PAGE_URL');
            }

            if ($template !== '') {
                $resolved = \CIBlock::ReplaceDetailUrl($template, $fieldsWithContext, false, 'E');
                if (strpos($resolved, $sectionPath) !== false) {
                    return $resolved;
                }
            }

            if (strpos($detailUrl, '#') !== false) {
                $resolved = \CIBlock::ReplaceDetailUrl($detailUrl, $fieldsWithContext, false, 'E');
                if (strpos($resolved, $sectionPath) !== false) {
                    return $resolved;
                }
            }

            $elementCode = isset($fields['CODE']) ? (string)$fields['CODE'] : '';
            if ($elementCode !== '') {
                return '/catalog/' . $sectionPath . '/' . $elementCode . '/';
            }

            return $detailUrl;
        };

        $session = Application::getInstance()->getSession();
        $rawViewed = $session->get('CATALOG_ITEM_VIEWED');

        if (!is_array($rawViewed) || $rawViewed === []) {
            return;
        }

        $viewedItems = [];
        foreach ($rawViewed as $key => $value) {
            $productId = 0;
            $context = [];
            $favoriteKey = '';

            if (is_array($value)) {
                $productId = isset($value['ID']) ? (int)$value['ID'] : (isset($value['id']) ? (int)$value['id'] : 0);
                if (isset($value['CONTEXT']) && is_array($value['CONTEXT'])) {
                    $context = $value['CONTEXT'];
                } elseif (isset($value['context']) && is_array($value['context'])) {
                    $context = $value['context'];
                }
                if (isset($value['FAVORITE_KEY'])) {
                    $favoriteKey = (string)$value['FAVORITE_KEY'];
                } elseif (isset($value['favkey'])) {
                    $favoriteKey = (string)$value['favkey'];
                } elseif (isset($value['key'])) {
                    $favoriteKey = (string)$value['key'];
                }
            } else {
                $productId = (int)$value;
                if ($productId <= 0 && is_scalar($key)) {
                    $productId = (int)$key;
                }
            }

            if ($favoriteKey !== '' && $productId <= 0) {
                $parsed = $parseFavoriteKey($favoriteKey);
                $productId = (int)($parsed['productId'] ?? 0);
                $context = $parsed['context'] ?? [];
            }

            if ($productId <= 0) {
                continue;
            }

            $context = $normalizeContext($context);
            if ($favoriteKey === '' && class_exists(\App\Brakes\Helper\FavoritesManager::class)) {
                $favoriteKey = \App\Brakes\Helper\FavoritesManager::buildFavoriteKey($productId, $context);
            }
            if ($favoriteKey === '') {
                $favoriteKey = (string)$productId;
            }

            $viewedItems[] = [
                'key' => $favoriteKey,
                'id' => $productId,
                'context' => $context,
            ];
        }

        if ($viewedItems === []) {
            return;
        }

        $currentKey = (string)Storage::get('ITEM_VIEWED_KEY');
        if ($currentKey !== '') {
            $viewedItems = array_values(array_filter(
                $viewedItems,
                static fn($item) => (string)($item['key'] ?? '') !== $currentKey
            ));
        } else {
            $currentId = (int)Storage::get('ITEM_ID');
            if ($currentId > 0) {
                $viewedItems = array_values(array_filter(
                    $viewedItems,
                    static fn($item) => (int)($item['id'] ?? 0) !== $currentId
                ));
            }
        }

        if ($viewedItems === []) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            return;
        }

        $ids = [];
        foreach ($viewedItems as $item) {
            $id = (int)($item['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        if ($ids === []) {
            return;
        }

        $select = [
            'ID',
            'IBLOCK_ID',
            'CODE',
            'NAME',
            'DETAIL_PAGE_URL',
            'DETAIL_PICTURE',
            'PREVIEW_PICTURE',
            'PROPERTY_LINK_PHOTO',
            'PROPERTY_LINK_PHOTO_FILE',
            'PROPERTY_CML2_ARTICLE',
            'PROPERTY_CML2_MANUFACTURER',
            'PROPERTY_MANUFACTURER',
            'PROPERTY_NUMBER_PISTONS',
            'PROPERTY_INSTALLATION_AXIS',
        ];

        $itemsById = [];
        $result = \CIBlockElement::GetList([], ['ID' => $ids], false, false, $select);
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
        while ($element = $result->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $id = (int)($fields['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = (string)($fields['~NAME'] ?? $fields['NAME'] ?? '');
            $elementCode = (string)($fields['CODE'] ?? '');
            $categoryValue = '';
            if (!empty($properties['CML2_TRAITS']['VALUE']) && is_array($properties['CML2_TRAITS']['VALUE'])) {
                $traitsValues = $properties['CML2_TRAITS']['VALUE'];
                $traitsDesc = $properties['CML2_TRAITS']['DESCRIPTION'] ?? [];
                foreach ($traitsValues as $k => $val) {
                    $nameDesc = $traitsDesc[$k] ?? '';
                    if ($nameDesc === 'Категория товара') {
                        $categoryValue = trim((string)$val);
                        break;
                    }
                }
            }

            $isPadsCategory = $categoryValue === 'Тормозные колодки';
            $isDiscsCategory = $categoryValue === 'Тормозные диски';
            $isShortCardCategory = $isPadsCategory || $isDiscsCategory;

            $articleValue = (string)($properties['CML2_ARTICLE']['VALUE'] ?? '');
            $manufacturerValue = (string)($properties['CML2_MANUFACTURER']['VALUE'] ?? '');
            if ($manufacturerValue === '') {
                $manufacturerValue = (string)($properties['MANUFACTURER']['VALUE'] ?? '');
            }
            $axisValue = (string)($properties['INSTALLATION_AXIS']['VALUE'] ?? '');

            if ($isShortCardCategory) {
                $details = [
                    [
                        'label' => 'Артикул',
                        'value' => $articleValue,
                    ],
                    [
                        'label' => 'Производитель:',
                        'value' => $manufacturerValue,
                    ],
                    [
                        'label' => 'Ось:',
                        'value' => $axisValue,
                    ],
                ];
            } else {
                $details = [
                    [
                        'label' => 'Артикул',
                        'value' => $articleValue,
                    ],
                    [
                        'label' => 'Производитель:',
                        'value' => $manufacturerValue,
                    ],
                    [
                        'label' => 'Кол-во поршней:',
                        'value' => (string)($properties['NUMBER_PISTONS']['VALUE'] ?? ''),
                    ],
                    [
                        'label' => 'Ось:',
                        'value' => $axisValue,
                    ],
                ];
            }

            $pictureData = null;
            $fileValue = $properties['LINK_PHOTO_FILE']['VALUE'] ?? null;
            if (is_array($fileValue)) {
                $fileIds = array_values(array_filter(array_map(static fn($value) => (int)$value, $fileValue)));
            } elseif ($fileValue !== null && $fileValue !== '') {
                $fileIds = [(int)$fileValue];
            } else {
                $fileIds = [];
            }
            $fileId = $fileIds[0] ?? 0;
            if ($fileId > 0) {
                $pictureData = Image::resizeByPreset($fileId, Image::PRESET_CATALOG_TILE);
            }

            if ($pictureData === null || empty($pictureData['src'])) {
                $fallbackFileId = 0;
                $detailPicture = $fields['DETAIL_PICTURE'] ?? null;
                if (is_array($detailPicture) && isset($detailPicture['ID'])) {
                    $fallbackFileId = (int)$detailPicture['ID'];
                } elseif (is_scalar($detailPicture) && (int)$detailPicture > 0) {
                    $fallbackFileId = (int)$detailPicture;
                }

                if ($fallbackFileId <= 0) {
                    $morePhotoValues = $properties['MORE_PHOTO']['VALUE'] ?? null;
                    if (is_array($morePhotoValues) && !empty($morePhotoValues)) {
                        $fallbackFileId = (int)reset($morePhotoValues);
                    } elseif (is_scalar($morePhotoValues) && (int)$morePhotoValues > 0) {
                        $fallbackFileId = (int)$morePhotoValues;
                    }
                }

                if ($fallbackFileId <= 0) {
                    $previewPicture = $fields['PREVIEW_PICTURE'] ?? null;
                    if (is_array($previewPicture) && isset($previewPicture['ID'])) {
                        $fallbackFileId = (int)$previewPicture['ID'];
                    } elseif (is_scalar($previewPicture) && (int)$previewPicture > 0) {
                        $fallbackFileId = (int)$previewPicture;
                    }
                }

                if ($fallbackFileId > 0) {
                    $pictureData = $makeFileItem($fallbackFileId);
                }
            }

            if ($pictureData === null || empty($pictureData['src'])) {
                $fallback = getPreviewImgCatalog((string)($properties['LINK_PHOTO']['VALUE'] ?? ''));
                if (is_string($fallback) && $fallback !== '') {
                    $pictureData = [
                        'src' => $fallback,
                        'width' => 0,
                        'height' => 0,
                        'cached' => false,
                    ];
                }
            }

            $card = [
                'ID' => $id,
                'NAME' => $name,
                'DETAIL_PAGE_URL' => (string)($fields['DETAIL_PAGE_URL'] ?? '#'),
                'CONTEXT_LABEL' => '',
                'CONTEXT_SECTION_ID' => 0,
                'CONTEXT_SECTION_PATH' => '',
                'IMAGE' => $pictureData,
                'IMG' => is_array($pictureData) ? (string)($pictureData['src'] ?? '') : '',
                'DETAILS' => $details,
                'COLORS' => [],
                'PRICE_HTML' => '',
                'OPTIONS_ATTR' => '{}',
                'SELECTED' => [],
                'EXPAND_FEATURES' => true,
                'FAVORITES_VIEW' => false,
                'HIDE_FEATURES' => true,
                'BUY' => [
                    'NAME' => $name,
                    'URL' => $detailUrl,
                ],
            ];

            $itemsById[$id] = [
                'FIELDS' => $fields,
                'PROPERTIES' => $properties,
                'CARD' => $card,
                'ELEMENT_CODE' => $elementCode,
                'CATEGORY_VALUE' => $categoryValue,
            ];
        }

        $this->arResult['ITEMS'] = [];
        foreach ($viewedItems as $viewed) {
            $id = (int)($viewed['id'] ?? 0);
            if ($id <= 0 || !isset($itemsById[$id])) {
                continue;
            }

            $base = $itemsById[$id];
            $fields = $base['FIELDS'] ?? [];
            $context = $normalizeContext($viewed['context'] ?? []);
            $favoriteKey = (string)($viewed['key'] ?? '');
            if ($favoriteKey === '') {
                if (class_exists(\App\Brakes\Helper\FavoritesManager::class)) {
                    $favoriteKey = \App\Brakes\Helper\FavoritesManager::buildFavoriteKey($id, $context);
                } else {
                    $favoriteKey = (string)$id;
                }
            }

            $detailUrl = $buildDetailUrlWithContext($fields, $context);
            $detailUrl = $appendQueryParam($detailUrl, 'from', 'viewed');
            if ($favoriteKey !== '') {
                $detailUrl = $appendQueryParam($detailUrl, 'favkey', $favoriteKey);
            }

            $contextSectionId = isset($context['section_id']) ? (int)$context['section_id'] : 0;
            $contextSectionPath = $buildContextSectionPath($context, (int)($fields['IBLOCK_ID'] ?? 0));
            $contextLabel = $buildContextLabel($context, (int)($fields['IBLOCK_ID'] ?? 0));

            $card = $base['CARD'] ?? [];
            $card['DETAIL_PAGE_URL'] = $detailUrl;
            $card['CONTEXT_LABEL'] = $contextLabel;
            $card['CONTEXT_SECTION_ID'] = $contextSectionId;
            $card['CONTEXT_SECTION_PATH'] = $contextSectionPath;
            $card['FAVORITE_KEY'] = $favoriteKey;
            $card['BUY'] = [
                'NAME' => $card['NAME'] ?? '',
                'URL' => $detailUrl,
            ];

            $this->arResult['ITEMS'][] = $card;
        }

        if ($this->arResult['ITEMS'] === []) {
            return;
        }

        $this->includeComponentTemplate();
    }
}
