<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div class="main__cataloge main-cataloge">
    <div class="main-cataloge__top">
        <div class="main-cataloge__select custom-select-container" id="sort-select">
            <button class="select-toggle" aria-haspopup="listbox" aria-expanded="false">Сортировать по</button>
            <ul class="select-options" role="listbox">
                <li class="option" data-value="price_asc">По цене (возростание)</li>
                <li class="option" data-value="price_desc">По цене (снижение)</li>
                <li class="option" data-value="size">По размеру</li>
                <li class="option" data-value="year">По году выпуска</li>
            </ul>
        </div>
        <div class="main-cataloge__top-controls">
            <button class="main-cataloge__btn-grid active main-cataloge__btn" data-view="grid"></button>
            <button class="main-cataloge__btn-list main-cataloge__btn" data-view="list"></button>
        </div>
    </div>
    <div class="main-cataloge__body view-grid">
        <?php
        $defaultContextSectionId = (int)($arResult['ID'] ?? 0);
        $defaultContextSectionPath = '';
        if ($defaultContextSectionId > 0 && !empty($arParams['IBLOCK_ID'])) {
            $navChain = \CIBlockSection::GetNavChain((int)$arParams['IBLOCK_ID'], $defaultContextSectionId, ['ID', 'CODE']);
            $codes = [];
            while ($row = $navChain->Fetch()) {
                if (!empty($row['CODE'])) {
                    $codes[] = $row['CODE'];
                }
            }
            if (!empty($codes)) {
                $defaultContextSectionPath = implode('/', $codes);
            }
        }
        $cardPartialPath = __DIR__ . '/partials/product-card.php';

        foreach ($arResult['ITEMS'] as $item) {
            $contextSectionId = (int)($item['CONTEXT_SECTION_ID'] ?? 0);
            if ($contextSectionId <= 0) {
                $contextSectionId = $defaultContextSectionId;
            }
            $contextSectionPath = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
            if ($contextSectionPath === '') {
                $contextSectionPath = $defaultContextSectionPath;
            }
            $contextLabel = (string)($item['CONTEXT_LABEL'] ?? '');

            $detailUrlRaw = (string)($item['DETAIL_PAGE_URL'] ?? '');
            $isPadsCategory = ($contextSectionPath !== '' && strpos($contextSectionPath, 'tormoznye_kolodki') === 0)
                || ($detailUrlRaw !== '' && strpos($detailUrlRaw, '/tormoznye_kolodki/') !== false);
            $isDiscsCategory = ($contextSectionPath !== '' && strpos($contextSectionPath, 'tormoznye_diski') === 0)
                || ($detailUrlRaw !== '' && strpos($detailUrlRaw, '/tormoznye_diski/') !== false);
            $isShortCardCategory = $isPadsCategory || $isDiscsCategory;

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

            $details = [];
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
                'DETAIL_PAGE_URL' => $item['DETAIL_PAGE_URL'],
                'CONTEXT_SECTION_ID' => $contextSectionId,
                'CONTEXT_SECTION_PATH' => $contextSectionPath,
                'CONTEXT_LABEL' => $contextLabel,
                'CATALOG_QUANTITY' => $item['CATALOG_QUANTITY'] ?? null,
                'CATALOG_AVAILABLE' => $item['CATALOG_AVAILABLE'] ?? null,
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
                    'URL' => $item['DETAIL_PAGE_URL'],
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
</div>
<?php

echo $arResult['NAV_STRING'];
?>
<div data-fls-popup="speedBuy" aria-hidden="true" class="popup">
    <div data-fls-popup-wrapper="" class="popup__wrapper">
        <div data-fls-popup-body="" class="popup__body">
            <button data-fls-popup-close="" type="button" class="popup__close">
                <svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.850056" d="M1.7333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
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
