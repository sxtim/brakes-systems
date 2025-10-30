<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$context = $arResult['SEARCH_CONTEXT'] ?? [
    'query' => '',
    'ids' => [],
    'sample' => [],
];

$catalogParams = $arResult['CATALOG_PARAMS'] ?? [];
$catalogTemplate = $arParams['CATALOG_TEMPLATE'] ?? 'search';
$filterName = $arResult['FILTER_NAME'] ?? 'arCatalogSearchFilter';

global $APPLICATION;
global $catalogSearchContext;
$catalogSearchContext = $context;

$APPLICATION->IncludeComponent(
    'bitrix:catalog.section',
    $catalogTemplate,
    array_merge(
        $catalogParams,
        [
            'FILTER_NAME' => $filterName,
            'SEARCH_CONTEXT' => $context,
        ]
    ),
    $component,
    ['HIDE_ICONS' => 'Y']
);
