<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$context = $arResult['SEARCH_CONTEXT'] ?? [
    'query' => (string)($_REQUEST['q'] ?? ''),
    'ids' => [],
    'sample' => [],
];

$query = trim((string)$context['query']);
$items = $arResult['ITEMS'] ?? [];
$hasContextItems = false;
foreach ($items as $item) {
    $contextSectionId = (int)($item['CONTEXT_SECTION_ID'] ?? 0);
    $contextSectionPath = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
    $contextParts = [];
    if ($contextSectionPath !== '') {
        $contextParts = array_values(array_filter(explode('/', trim($contextSectionPath, '/')), 'strlen'));
    } else {
        $detailUrl = (string)($item['DETAIL_PAGE_URL'] ?? '');
        if ($detailUrl !== '') {
            $path = (string)parse_url($detailUrl, PHP_URL_PATH);
            $path = trim($path, '/');
            if ($path !== '') {
                $parts = array_values(array_filter(explode('/', $path), 'strlen'));
                if (isset($parts[0]) && $parts[0] === 'catalog' && count($parts) > 2) {
                    $contextParts = array_slice($parts, 1, -1);
                }
            }
        }
    }
    $contextDepth = count($contextParts);
    if ($contextSectionId > 0 || $contextDepth >= 4) {
        $hasContextItems = true;
        break;
    }
}
$cardPartialPath = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/main/components/bitrix/catalog/.default/bitrix/catalog.section/.default/partials/product-card.php';

$appendQueryParam = static function (string $url, string $param, string $value): string {
    if ($url === '' || $param === '') {
        return $url;
    }

    $parts = parse_url($url);
    $path = $parts['path'] ?? '';
    if ($path === '') {
        $path = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    }
    if ($path === '') {
        $path = $url;
    }
    $query = $parts['query'] ?? '';
    $fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

    parse_str($query, $params);
    if (!isset($params[$param]) || (string)$params[$param] === '') {
        $params[$param] = $value;
    }

    $newQuery = http_build_query($params);
    return $path . ($newQuery !== '' ? ('?' . $newQuery) : '') . $fragment;
};

$navString = (string)($arResult['NAV_STRING'] ?? '');
$navResult = $arResult['NAV_RESULT'] ?? null;
$navNum = 0;
if (is_object($navResult) && isset($navResult->NavNum)) {
    $navNum = (int)$navResult->NavNum;
}
if ($navNum > 1 && $navString !== '') {
    $navString = str_replace(
        ['PAGEN_1=', 'SIZEN_1=', 'SHOWALL_1='],
        ['PAGEN_' . $navNum . '=', 'SIZEN_' . $navNum . '=', 'SHOWALL_' . $navNum . '='],
        $navString
    );
}
if ($query !== '' && $navString !== '') {
    $navString = preg_replace_callback('/href="([^"]+)"/i', static function (array $matches) use ($appendQueryParam, $query): string {
        $href = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5);
        $href = $appendQueryParam($href, 'q', $query);
        return 'href="' . htmlspecialcharsbx($href) . '"';
    }, $navString);
}
?>
<div class="search-page">
    <div class="search-page__container">
        <div class="search-page__header">
            <h1 class="main__title search-page__title">
                <?php if ($query !== ''): ?>
                    <?= htmlspecialcharsbx('Результаты поиска по запросу: ' . $query) ?>
                <?php else: ?>
                    Найденные товары
                <?php endif; ?>
            </h1>
            <a class="search-page__catalog-link" href="/catalog/">Вернуться в каталог</a>
        </div>
        <div class="main__cataloge main-cataloge">
            <?php if (!empty($items)): ?>
                <div class="main-cataloge__body view-grid">
                    <?php
                    foreach ($items as $item) {
                        $priceFormatted = null;
                        if (!empty($item['FAVORITES_PRICE']['PRICE_FORMATTED'])) {
                            $priceFormatted = htmlspecialcharsback($item['FAVORITES_PRICE']['PRICE_FORMATTED']);
                        }
                        if ($priceFormatted === null) {
                            $basePriceFormatted = (string)($item['BASE_PRICE_FORMATTED'] ?? '');
                            $priceFormatted = $basePriceFormatted !== '' ? $basePriceFormatted : '0';
                        }

                        $optionsJson = $item['FAVORITES_OPTIONS_JSON'] ?? '{}';
                        $optionsAttr = htmlspecialcharsbx($optionsJson);

                        $contextSectionId = (int)($item['CONTEXT_SECTION_ID'] ?? 0);
                        $contextSectionPath = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
                        $contextLabel = (string)($item['CONTEXT_LABEL'] ?? '');

                        $detailUrlRaw = (string)($item['DETAIL_PAGE_URL'] ?? '');
                        $isPadsCategory = $detailUrlRaw !== '' && strpos($detailUrlRaw, '/tormoznye_kolodki/') !== false;
                        $isDiscsCategory = $detailUrlRaw !== '' && strpos($detailUrlRaw, '/tormoznye_diski/') !== false;
                        $isShortCardCategory = $isPadsCategory || $isDiscsCategory;

                        if ($isShortCardCategory) {
                            $brand = (string)($item['PROPERTIES']['CML2_MANUFACTURER']['VALUE'] ?? '');
                            if ($brand === '') {
                                $brand = (string)($item['PROPERTIES']['MANUFACTURER']['VALUE'] ?? '');
                            }

                            $details = [
                                [
                                    'label' => 'Артикул',
                                    'value' => $item['PROPERTIES']['CML2_ARTICLE']['VALUE'] ?? '',
                                ],
                                [
                                    'label' => 'Производитель:',
                                    'value' => $brand,
                                ],
                                [
                                    'label' => 'Ось',
                                    'value' => $item['PROPERTIES']['INSTALLATION_AXIS']['VALUE'] ?? '',
                                ],
                            ];
                        } else {
                            $details = [
                                [
                                    'label' => 'Артикул',
                                    'value' => $item['PROPERTIES']['CML2_ARTICLE']['VALUE'] ?? '',
                                ],
                                [
                                    'label' => 'Производитель:',
                                    'value' => $item['PROPERTIES']['MANUFACTURER']['VALUE'] ?? '',
                                ],
                                [
                                    'label' => 'Кол-во поршней:',
                                    'value' => $item['PROPERTIES']['NUMBER_PISTONS']['VALUE'] ?? '',
                                ],
                                [
                                    'label' => 'Ось:',
                                    'value' => $item['PROPERTIES']['INSTALLATION_AXIS']['VALUE'] ?? '',
                                ],
                            ];
                        }

                        $colors = [];
                        if (!empty($item['DISPLAY_PROPERTIES']['COLOR']['VALUE']) && is_array($item['DISPLAY_PROPERTIES']['COLOR']['VALUE'])) {
                            foreach ($item['DISPLAY_PROPERTIES']['COLOR']['VALUE'] as $index => $value) {
                                $xmlId = $item['DISPLAY_PROPERTIES']['COLOR']['VALUE_XML_ID'][$index] ?? null;
                                if ($xmlId === null) {
                                    continue;
                                }
                                $colors[] = [
                                    'xmlId' => $xmlId,
                                ];
                            }
                        }

                        $cardData = [
                            'ID' => $item['ID'],
                            'NAME' => $item['NAME'],
                            'DETAIL_PAGE_URL' => $appendQueryParam((string)($item['DETAIL_PAGE_URL'] ?? ''), 'from', 'search'),
                            'CONTEXT_SECTION_ID' => $contextSectionId,
                            'CONTEXT_SECTION_PATH' => $contextSectionPath,
                            'CONTEXT_LABEL' => $contextLabel,
                            'CATALOG_QUANTITY' => $item['CATALOG_QUANTITY'] ?? null,
                            'CATALOG_AVAILABLE' => $item['CATALOG_AVAILABLE'] ?? null,
                            'CATALOG' => $item['CATALOG'] ?? null,
                            'PRODUCT' => $item['PRODUCT'] ?? null,
                            'IMAGE' => $item['IMAGE'] ?? null,
                            'IMG' => $item['IMG'] ?? '',
                            'PRICE_HTML' => $priceFormatted,
                            'OPTIONS_ATTR' => $optionsAttr,
                            'DETAILS' => $details,
                            'COLORS' => $colors,
                            'SELECTED' => $item['FAVORITES_SELECTED_OPTIONS'] ?? [],
                            'EXPAND_FEATURES' => false,
                            'BUY' => [
                                'NAME' => $item['NAME'],
                                'URL' => $appendQueryParam((string)($item['DETAIL_PAGE_URL'] ?? ''), 'from', 'search'),
                            ],
                        ];

                        if (file_exists($cardPartialPath)) {
                            $card = $cardData;
                            include $cardPartialPath;
                            unset($card);
                        }
                    }
                    ?>
                </div>
                <?php if ($navString !== ''): ?>
                    <?= $navString ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-cataloge__empty">
                    <?php if ($query !== ''): ?>
                        <p>По запросу «<?= htmlspecialcharsbx($query) ?>» ничего не найдено.</p>
                    <?php else: ?>
                        <p>Ничего не найдено.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
                    [
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
                        "VARIABLE_ALIASES" => [
                            "RESULT_ID" => "RESULT_ID",
                            "WEB_FORM_ID" => "WEB_FORM_ID"
                        ],
                        "WEB_FORM_ID" => "QUICK_ORDER"
                    ],
                    $component,
                    ['HIDE_ICONS' => 'Y']
                );?>
            </div>
        </div>
    </div>
</div>
