<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

foreach ($arResult['ITEMS'] as $i => $item) {
    if ($item['PROPERTIES']['LINK_PHOTO']['VALUE']) {
        $arResult['ITEMS'][$i]['IMG'] = getPreviewImgCatalog($item['PROPERTIES']['LINK_PHOTO']['VALUE']);
    }
}
