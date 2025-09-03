<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

foreach ($arResult['SECTIONS'] as $i => $item) {
    $arResult['SECTIONS'][$i]['SVG'] = CFile::GetPath($item['UF_SVG']);
}
