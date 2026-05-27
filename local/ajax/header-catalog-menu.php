<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);
define('PUBLIC_AJAX_MODE', true);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

global $APPLICATION;

header('Content-Type: text/html; charset=' . LANG_CHARSET);
header('X-Robots-Tag: noindex');

$APPLICATION->IncludeComponent(
    'bitrix:catalog.section.list',
    '',
    [
        'ADDITIONAL_COUNT_ELEMENTS_FILTER' => 'additionalCountFilter',
        'VIEW_MODE' => 'TEXT',
        'SHOW_PARENT_NAME' => 'Y',
        'IBLOCK_TYPE' => 'catalog',
        'IBLOCK_ID' => 1,
        'SECTION_ID' => '',
        'SECTION_CODE' => '',
        'SECTION_URL' => '',
        'COUNT_ELEMENTS' => 'Y',
        'COUNT_ELEMENTS_FILTER' => 'CNT_ACTIVE',
        'HIDE_SECTIONS_WITH_ZERO_COUNT_ELEMENTS' => 'N',
        'TOP_DEPTH' => '4',
        'SECTION_FIELDS' => '',
        'SECTION_USER_FIELDS' => [
            'UF_SVG',
        ],
        'ADD_SECTIONS_CHAIN' => 'Y',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => '36000000',
        'CACHE_NOTES' => '',
        'CACHE_GROUPS' => 'Y',
    ]
);
