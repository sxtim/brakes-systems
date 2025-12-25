<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

foreach ($arResult['SECTIONS'] as $i => $item) {
    $arResult['SECTIONS'][$i]['SVG'] = CFile::GetPath($item['UF_SVG']);
}

// Hide technical "other" section only in the header catalog menu.
// We remove the whole subtree starting from the top-level section with CODE=other.
$filtered = [];
$skipDepth = null;
foreach ($arResult['SECTIONS'] as $section) {
    $depth = (int)($section['DEPTH_LEVEL'] ?? 0);
    $code = (string)($section['CODE'] ?? '');

    if ($skipDepth !== null) {
        if ($depth <= $skipDepth) {
            $skipDepth = null;
        } else {
            continue;
        }
    }

    if ($depth === 1 && $code === 'other') {
        $skipDepth = 1;
        continue;
    }

    $filtered[] = $section;
}

$arResult['SECTIONS'] = $filtered;
