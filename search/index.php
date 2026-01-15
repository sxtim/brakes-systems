<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';
$APPLICATION->SetTitle('Поиск по товарам');

$catalogFilterName = 'catalogSearchFilter';
$searchQuery = trim((string)($_REQUEST['q'] ?? ''));
$catalogPagerParamsName = 'catalogSearchPagerParams';
$GLOBALS[$catalogPagerParamsName] = [
    'q' => $searchQuery,
];

// Ensure separate pager numbers for search.page and catalog.section.
global $NavNum;
$navNumBackup = isset($NavNum) ? (int)$NavNum : null;
$NavNum = 0;

$APPLICATION->IncludeComponent(
    'bitrix:search.page',
    'catalog_ids',
    [
        'CHECK_DATES' => 'Y',
        'RESTART' => 'Y',
        'NO_WORD_LOGIC' => 'Y',
        'USE_LANGUAGE_GUESS' => 'N',
        'USE_SEARCH_RESULT_ORDER' => 'Y',
        'DEFAULT_SORT' => 'rank',
        'PAGE_RESULT_COUNT' => '500',
        'arrFILTER' => ['iblock_1c_catalog'],
        'arrFILTER_iblock_1c_catalog' => ['all'],
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '3600',
        'DISPLAY_TOP_PAGER' => 'N',
        'DISPLAY_BOTTOM_PAGER' => 'N',
        'PAGER_SHOW_ALWAYS' => 'N',
        'SHOW_WHERE' => 'N',
        'SHOW_WHEN' => 'N',
        'USE_RATING' => 'N',
        'USE_SUGGEST' => 'N',
        'QUERY' => $searchQuery,
        'CATALOG_FILTER_NAME' => $catalogFilterName,
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);

// search.page consumes PAGEN_1; catalog.section should use PAGEN_2.
$NavNum = 1;

$catalogSectionParams = [
    'IBLOCK_TYPE' => '1c_catalog',
    'IBLOCK_ID' => '1',
    'FILTER_NAME' => $catalogFilterName,
    'ELEMENT_SORT_FIELD' => 'sort',
    'ELEMENT_SORT_ORDER' => 'asc',
    'ELEMENT_SORT_FIELD2' => 'id',
    'ELEMENT_SORT_ORDER2' => 'desc',
    'SECTION_URL' => '/catalog/#SECTION_CODE_PATH#/',
    'DETAIL_URL' => '/catalog/#SECTION_CODE_PATH#/#ELEMENT_CODE#/',
    'BASKET_URL' => '/personal/cart/',
    'ACTION_VARIABLE' => 'action',
    'PRODUCT_ID_VARIABLE' => 'id',
    'PRODUCT_QUANTITY_VARIABLE' => 'quantity',
    'PRODUCT_PROPS_VARIABLE' => 'prop',
    'SECTION_ID_VARIABLE' => 'SECTION_ID',
    'DISPLAY_COMPARE' => 'N',
    'PAGE_ELEMENT_COUNT' => '20',
    'LINE_ELEMENT_COUNT' => '3',
    'PROPERTY_CODE' => [
        'PRODUCT_CATEGORY',
    ],
    'OFFERS_FIELD_CODE' => [
        'PREVIEW_PICTURE',
        'DETAIL_PICTURE',
    ],
    'OFFERS_PROPERTY_CODE' => [],
    'OFFERS_SORT_FIELD' => 'sort',
    'OFFERS_SORT_ORDER' => 'asc',
    'OFFERS_SORT_FIELD2' => 'id',
    'OFFERS_SORT_ORDER2' => 'desc',
    'OFFERS_LIMIT' => '5',
    'PRICE_CODE' => [
        'РРЦ',
    ],
    'USE_PRICE_COUNT' => 'N',
    'SHOW_PRICE_COUNT' => '1',
    'PRICE_VAT_INCLUDE' => 'Y',
    'USE_PRODUCT_QUANTITY' => 'Y',
    'CACHE_TYPE' => 'A',
    'CACHE_TIME' => '36000000',
    'CACHE_FILTER' => 'Y',
    'CACHE_GROUPS' => 'Y',
    'DISPLAY_TOP_PAGER' => 'N',
    'DISPLAY_BOTTOM_PAGER' => 'Y',
    'PAGER_TITLE' => 'Товары',
    'PAGER_SHOW_ALWAYS' => 'N',
    'PAGER_TEMPLATE' => '',
    'PAGER_PARAMS_NAME' => $catalogPagerParamsName,
    'PAGER_BASE_LINK_ENABLE' => 'Y',
    'PAGER_BASE_LINK' => '/search/?q=' . urlencode($searchQuery),
    'PAGER_DESC_NUMBERING' => 'N',
    'PAGER_DESC_NUMBERING_CACHE_TIME' => '36000',
    'PAGER_SHOW_ALL' => 'N',
    'HIDE_NOT_AVAILABLE' => 'N',
    'CONVERT_CURRENCY' => 'Y',
    'CURRENCY_ID' => 'RUB',
    'OFFERS_CART_PROPERTIES' => [],
    'ADD_SECTIONS_CHAIN' => 'N',
    'SET_TITLE' => 'N',
    'SET_BROWSER_TITLE' => 'N',
    'SET_META_KEYWORDS' => 'N',
    'SET_META_DESCRIPTION' => 'N',
    'SET_LAST_MODIFIED' => 'N',
    'INCLUDE_SUBSECTIONS' => 'Y',
    'SHOW_ALL_WO_SECTION' => 'Y',
    'MESSAGE_404' => '',
    'SET_STATUS_404' => 'N',
    'SHOW_404' => 'N',
    'SEF_MODE' => 'N',
];

$APPLICATION->IncludeComponent(
    'bitrix:catalog.section',
    'search',
    $catalogSectionParams,
    false
);

if ($navNumBackup === null) {
    unset($NavNum);
} else {
    $NavNum = $navNumBackup;
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
