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

        $cardPartialPath = __DIR__ . '/partials/product-card.php';

        foreach ($arResult['ITEMS'] as $item) {
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
