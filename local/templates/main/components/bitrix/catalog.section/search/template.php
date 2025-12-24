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
$cardPartialPath = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/main/components/bitrix/catalog/.default/bitrix/catalog.section/.default/partials/product-card.php';

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
        <p class="main__catalog-prompt">
            Чтобы купить/добавить в избранное/оформить «в 1 клик», сначала выберите автомобиль (поколение) в каталоге.
        </p>
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
                            $basePrice = isset($item['ITEM_PRICES'][0]['PRICE']) ? (float)$item['ITEM_PRICES'][0]['PRICE'] : 0.0;
                            $priceFormatted = number_format($basePrice, 0, '.', ' ') . ' ₽';
                        }

                        $optionsJson = $item['FAVORITES_OPTIONS_JSON'] ?? '{}';
                        $optionsAttr = htmlspecialcharsbx($optionsJson);

                        $detailUrlRaw = (string)($item['DETAIL_PAGE_URL'] ?? '');
                        $isPadsCategory = $detailUrlRaw !== '' && strpos($detailUrlRaw, '/tormoznye_kolodki/') !== false;

                        if ($isPadsCategory) {
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
                                    'label' => 'Бренд',
                                    'value' => $brand,
                                ],
                                [
                                    'label' => 'Тип',
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
                <?php if (!empty($arResult['NAV_STRING'])): ?>
                    <?= $arResult['NAV_STRING'] ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="main-cataloge__empty">
                    <?php if ($query !== ''): ?>
                        <p>По запросу «<?= htmlspecialcharsbx($query) ?>» ничего не найдено.</p>
                        <?php if (!empty($context['sample'])): ?>
                            <ul class="main-cataloge__suggestions">
                                <?php foreach ($context['sample'] as $sample): ?>
                                    <li>
                                        <a href="<?= htmlspecialcharsbx($sample['URL'] ?? '#') ?>">
                                            <?= htmlspecialcharsbx($sample['TITLE'] ?? $sample['URL'] ?? '') ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Введите поисковый запрос, чтобы найти товары.</p>
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
