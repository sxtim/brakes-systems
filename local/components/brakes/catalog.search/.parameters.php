<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    return;
}

$arComponentParameters = [];

include $_SERVER['DOCUMENT_ROOT'] . '/bitrix/components/bitrix/catalog.section/.parameters.php';

$arComponentParameters['PARAMETERS']['QUERY_VARIABLE'] = [
    'PARENT' => 'BASE',
    'NAME' => 'Переменная запроса',
    'TYPE' => 'STRING',
    'DEFAULT' => 'q',
];

$arComponentParameters['PARAMETERS']['FILTER_NAME']['DEFAULT'] = 'catalogSearchFilter';
$arComponentParameters['PARAMETERS']['CATALOG_TEMPLATE'] = [
    'PARENT' => 'BASE',
    'NAME' => 'Шаблон каталога для отображения',
    'TYPE' => 'STRING',
    'DEFAULT' => 'search',
];

$arComponentParameters['PARAMETERS']['SEARCH_LIMIT'] = [
    'PARENT' => 'ADDITIONAL_SETTINGS',
    'NAME' => 'Максимум элементов в выдаче',
    'TYPE' => 'STRING',
    'DEFAULT' => '500',
];

$arComponentParameters['PARAMETERS']['ERROR_ON_EMPTY_STEM'] = [
    'PARENT' => 'ADDITIONAL_SETTINGS',
    'NAME' => 'Перезапускать поиск при пустых словоформах',
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N',
];

$arComponentParameters['PARAMETERS']['NO_WORD_LOGIC'] = [
    'PARENT' => 'ADDITIONAL_SETTINGS',
    'NAME' => 'Отключить логические операторы',
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];

$arComponentParameters['PARAMETERS']['CHECK_DATES'] = [
    'PARENT' => 'ADDITIONAL_SETTINGS',
    'NAME' => 'Искать только активные элементы',
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'Y',
];
