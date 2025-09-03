<?php

use Bitrix\Iblock\SectionTable;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$rsData = SectionTable::getList([
    'order' => [
        'SORT' => 'asc',
    ],
    'filter' => [
        'DEPTH_LEVEL' => 3,
    ],
    'select' => [
        'IBLOCK_ID',
        'ID',
        'CODE',
        'SECTION_PAGE_URL' => 'IBLOCK.SECTION_PAGE_URL',
    ],
    'limit' => 1,
]);

if ($data = $rsData->fetch()) {
    $section = CIBlock::ReplaceDetailUrl(
        $data['SECTION_PAGE_URL'],
        $data,
        false,
        'S'
    );

    if ($section) {
        localRedirect($section);
    }
}


