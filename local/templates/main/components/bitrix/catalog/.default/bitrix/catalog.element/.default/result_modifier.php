<?php

use Bitrix\Iblock\Elements\ElementCatalogTable;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if ($arResult['PROPERTIES']['LINK_PHOTO']['VALUE']) {
    $valArr = explode(';', $arResult['PROPERTIES']['LINK_PHOTO']['VALUE']);

    $arResult['GALLERY'] = array_filter($valArr, fn($item) => ! empty($item));
}

if ($arResult['DISPLAY_PROPERTIES']['RECOMMENDED']['VALUE']) {
    $rsData = ElementCatalogTable::getList([
        'filter' => [
            '=ID' => $arResult['DISPLAY_PROPERTIES']['RECOMMENDED']['VALUE'],
        ],
        'select' => [
            'ID',
            'LINK_PHOTO_VAL' => 'LINK_PHOTO.VALUE',
        ],
    ]);

    while ($data = $rsData->fetch()) {
        $arResult['DISPLAY_PROPERTIES']['RECOMMENDED']['LINK_ELEMENT_VALUE'][$data['ID']]['IMG'] = getPreviewImgCatalog($data['LINK_PHOTO_VAL']);
    }
}

$arResult['RECOMMENDED'] = $arResult['DISPLAY_PROPERTIES']['RECOMMENDED']['LINK_ELEMENT_VALUE'];

$this->__component->setResultCacheKeys(['RECOMMENDED']);

