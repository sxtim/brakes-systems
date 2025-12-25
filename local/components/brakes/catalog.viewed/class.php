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

        $session = Application::getInstance()->getSession();
        $ids = $session->get('CATALOG_ITEM_VIEWED');

        if (!is_array($ids) || $ids === []) {
            return;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_values(array_filter($ids, static fn($id) => $id > 0));
        if ($ids === []) {
            return;
        }

        $currentId = (int)Storage::get('ITEM_ID');
        if ($currentId > 0) {
            $ids = array_values(array_filter($ids, static fn($id) => $id !== $currentId));
        }

        if ($ids === []) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            return;
        }

        $select = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'DETAIL_PAGE_URL',
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
        while ($element = $result->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $id = (int)($fields['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = (string)($fields['~NAME'] ?? $fields['NAME'] ?? '');
            $detailUrl = (string)($fields['DETAIL_PAGE_URL'] ?? '#');
            $detailUrl = \CIBlock::ReplaceDetailUrl($detailUrl, $fields, false, 'E');
            $detailUrl = $appendQueryParam($detailUrl, 'from', 'viewed');

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
                'DETAIL_PAGE_URL' => $detailUrl,
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

            $itemsById[$id] = $card;
        }

        $this->arResult['ITEMS'] = [];
        foreach ($ids as $id) {
            if (isset($itemsById[$id])) {
                $this->arResult['ITEMS'][] = $itemsById[$id];
            }
        }

        if ($this->arResult['ITEMS'] === []) {
            return;
        }

        $this->includeComponentTemplate();
    }
}
