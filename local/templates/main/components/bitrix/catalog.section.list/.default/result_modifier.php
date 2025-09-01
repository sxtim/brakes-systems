<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$newResult = [];

foreach ($arResult['SECTIONS'] as $i => $item) {
    $newResult[$item['ID']] = $item;
}

foreach ($newResult as $id => $item) {
    if ($item['IBLOCK_SECTION_ID'] && $newResult[$item['IBLOCK_SECTION_ID']]) {
        $newResult[$item['IBLOCK_SECTION_ID']]['CHILDREN'][] = $item;
    }
}
