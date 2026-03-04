<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    return;
}

$arComponentParameters = [
    'PARAMETERS' => [
        'SETTINGS_IBLOCK_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Код инфоблока настроек главной',
            'TYPE' => 'STRING',
            'DEFAULT' => 'home_main_settings',
        ],
        'SETTINGS_ELEMENT_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Код элемента настроек',
            'TYPE' => 'STRING',
            'DEFAULT' => 'main',
        ],
        'PRODUCTS_IBLOCK_CODE' => [
            'PARENT' => 'BASE',
            'NAME' => 'Код инфоблока товаров главной',
            'TYPE' => 'STRING',
            'DEFAULT' => 'home_main_products',
        ],
        'CACHE_TIME' => ['DEFAULT' => 3600],
    ],
];

