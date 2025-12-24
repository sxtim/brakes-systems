<?php

use App\Brakes\Helper\Highload;
use App\Brakes\Helper\Image;
use Bitrix\Iblock\Elements\ElementCatalogTable;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

 $arResult['GALLERY'] = [];
 $fileProperty = $arResult['PROPERTIES']['LINK_PHOTO_FILE']['VALUE'] ?? [];
 if (is_array($fileProperty) && !empty($fileProperty)) {
     foreach ($fileProperty as $fileId) {
         if (!$fileId) {
             continue;
         }
 
         $main = Image::resizeByPreset($fileId, Image::PRESET_PRODUCT_GALLERY_MAIN);
         $thumb = Image::resizeByPreset($fileId, Image::PRESET_PRODUCT_GALLERY_THUMB);
         $path = is_array($main) && !empty($main['original'])
             ? $main['original']
             : \CFile::GetPath($fileId);
 
         $arResult['GALLERY'][] = [
             'id' => (int)$fileId,
             'main' => $main,
             'thumb' => $thumb,
             'original' => $path,
         ];
     }
 } elseif (!empty($arResult['PROPERTIES']['LINK_PHOTO']['VALUE'])) {
     $valArr = explode(';', $arResult['PROPERTIES']['LINK_PHOTO']['VALUE']);
     $paths = array_filter(array_map(static fn($item) => trim((string)$item), $valArr));
 
     foreach ($paths as $path) {
         $arResult['GALLERY'][] = [
             'id' => null,
             'main' => [
                 'src' => $path,
                 'width' => 0,
                 'height' => 0,
                 'cached' => false,
             ],
             'thumb' => [
                 'src' => $path,
                 'width' => 0,
                 'height' => 0,
                 'cached' => false,
             ],
             'original' => $path,
         ];
     }
 }

// Fallback: если LINK_PHOTO_FILE / LINK_PHOTO пусты, пробуем штатные картинки:
// MORE_PHOTO (Картинки галереи) → DETAIL_PICTURE → PREVIEW_PICTURE.
if ($arResult['GALLERY'] === []) {
    $appendFileToGallery = static function (int $fileId) use (&$arResult): void {
        if ($fileId <= 0) {
            return;
        }

        $main = Image::resizeByPreset($fileId, Image::PRESET_PRODUCT_GALLERY_MAIN);
        $thumb = Image::resizeByPreset($fileId, Image::PRESET_PRODUCT_GALLERY_THUMB);
        $path = is_array($main) && !empty($main['original'])
            ? $main['original']
            : \CFile::GetPath($fileId);

        $arResult['GALLERY'][] = [
            'id' => $fileId,
            'main' => $main,
            'thumb' => $thumb,
            'original' => $path,
        ];
    };

    $getPropertyFileIds = static function (string $code) use ($arParams, $arResult): array {
        $iblockId = (int)($arParams['IBLOCK_ID'] ?? 0);
        $elementId = (int)($arResult['ID'] ?? 0);
        if ($iblockId <= 0 || $elementId <= 0) {
            return [];
        }

        $values = [];
        $res = \CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            ['sort' => 'asc', 'id' => 'asc'],
            ['CODE' => $code]
        );
        while ($row = $res->Fetch()) {
            $fileId = (int)($row['VALUE'] ?? 0);
            if ($fileId > 0) {
                $values[] = $fileId;
            }
        }

        return array_values(array_unique($values));
    };

    $morePhotoValues = $arResult['PROPERTIES']['MORE_PHOTO']['VALUE'] ?? null;
    $morePhotoIds = [];
    if (is_array($morePhotoValues)) {
        $morePhotoIds = array_values(array_filter(array_map('intval', $morePhotoValues)));
    } elseif (is_scalar($morePhotoValues) && (int)$morePhotoValues > 0) {
        $morePhotoIds = [(int)$morePhotoValues];
    } else {
        $morePhotoIds = $getPropertyFileIds('MORE_PHOTO');
    }

    foreach ($morePhotoIds as $fileId) {
        $appendFileToGallery((int)$fileId);
    }

    if ($arResult['GALLERY'] === []) {
        $detailPicture = $arResult['DETAIL_PICTURE'] ?? null;
        $detailFileId = 0;
        if (is_array($detailPicture) && isset($detailPicture['ID'])) {
            $detailFileId = (int)$detailPicture['ID'];
        } elseif (is_scalar($detailPicture) && (int)$detailPicture > 0) {
            $detailFileId = (int)$detailPicture;
        }

        if ($detailFileId <= 0) {
            $previewPicture = $arResult['PREVIEW_PICTURE'] ?? null;
            if (is_array($previewPicture) && isset($previewPicture['ID'])) {
                $detailFileId = (int)$previewPicture['ID'];
            } elseif (is_scalar($previewPicture) && (int)$previewPicture > 0) {
                $detailFileId = (int)$previewPicture;
            }
        }

        $appendFileToGallery($detailFileId);
    }
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

if ($arResult['DISPLAY_PROPERTIES']['DELIVERY']['VALUE']) {
    $rsData = Highload::getClassEntity('b_hlbd_delivery')::getList([
        'order' => [
            'UF_SORT' => 'asc',
        ],
        'filter' => [
            'UF_XML_ID' => $arResult['DISPLAY_PROPERTIES']['DELIVERY']['VALUE'],
        ],
        'select' => [
            'UF_NAME',
            'UF_FILE',
            'UF_LINK',
            'UF_DESCRIPTION',
        ],
    ]);

    while ($data = $rsData->fetch()) {
        $data['UF_FILE'] = CFile::GetPath($data['UF_FILE']);
        $arResult['DELIVERY'] = $data;
    }
}

$arResult['RECOMMENDED'] = $arResult['DISPLAY_PROPERTIES']['RECOMMENDED']['LINK_ELEMENT_VALUE'];

$this->__component->setResultCacheKeys(['RECOMMENDED']);
