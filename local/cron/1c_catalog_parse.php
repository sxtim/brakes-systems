<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

require_once($_SERVER["DOCUMENT_ROOT"]
    ."/bitrix/modules/main/include/prolog_before.php");

Loader::includeModule('iblock');

$rsData = CIBlockElement::GetList(
    arFilter: [
        'IBLOCK_ID' => 1,
    ],
    arSelectFields: [
        'ID',
        'PROPERTY_MARK',
        'PROPERTY_MODEL',
        'PROPERTY_BODY',
    ],
);

$itemsData = [];
$sectionsName = [];
$sectionsData = [];

while ($data = $rsData->fetch()) {
    if ( ! in_array($data['PROPERTY_MARK_VALUE'], $sectionsName)) {
        $sectionsName[] = $data['PROPERTY_MARK_VALUE'];
    }

    if ( ! in_array($data['PROPERTY_MODEL_VALUE'], $sectionsName)) {
        $sectionsName[] = $data['PROPERTY_MODEL_VALUE'];
    }

    if ( ! in_array($data['PROPERTY_BODY_VALUE'], $sectionsName)) {
        $sectionsName[] = $data['PROPERTY_BODY_VALUE'];
    }

    $itemsData[$data['ID']]['SECTION_1'] = $data['PROPERTY_MARK_VALUE'];
    $itemsData[$data['ID']]['SECTION_2'] = $data['PROPERTY_MODEL_VALUE'];
    $itemsData[$data['ID']]['SECTION_3'] = $data['PROPERTY_BODY_VALUE'];
    $sectionsData[$data['PROPERTY_MARK_VALUE']][$data['PROPERTY_MODEL_VALUE']][$data['PROPERTY_BODY_VALUE']]
        = true;
}

$rsData = SectionTable::getList([
    'filter' => [
        'IBLOCK_ID' => 1,
    ],
    'select' => [
        'ID',
        'NAME',
        'DEPTH_LEVEL',
        'IBLOCK_SECTION_ID',
    ],
]);

$sectionsDb = [];
$sectionsDbIdName = [];

while ($data = $rsData->fetch()) {
    if ( ! in_array($data['NAME'], $sectionsName)) {
        SectionTable::delete($data['ID']);
    }

    if ($sectionsDb[$data['NAME']]['ID']) {
        $sectionsDb[$data['NAME']]['IDS'][$sectionsDb[$data['NAME']]['ID']] = $sectionsDb[$data['NAME']]['IBLOCK_SECTION_ID'];

        unset($sectionsDb[$data['NAME']]['ID']);
        unset($sectionsDb[$data['NAME']]['IBLOCK_SECTION_ID']);

        $sectionsDb[$data['NAME']]['IDS'][$data['ID']] = $data['IBLOCK_SECTION_ID'];
    } else {
        $sectionsDb[$data['NAME']]['ID'] = $data['ID'];
        $sectionsDb[$data['NAME']]['IBLOCK_SECTION_ID'] = $data['IBLOCK_SECTION_ID'];
    }

    if ($data['DEPTH_LEVEL'] > 1) {
        $sectionsDb[$data['NAME']][$data['IBLOCK_SECTION_ID']]
            = $data['IBLOCK_SECTION_ID'];
    }

    $sectionsDbIdName[$data['ID']] = $data['NAME'];
}

foreach ($sectionsData as $lvl1 => $lvls2) {
    if ( ! isset($sectionsDb[$lvl1])) {
        $id = SectionTable::add([
            'IBLOCK_ID' => 1,
            'NAME'      => $lvl1,
            'CODE' => CUtil::translit($lvl1, 'ru'),
            'ACTIVE'   => 'Y',
        ])->getId();

        $sectionsDb[$lvl1]['ID'] = $id;
        $sectionsDbIdName[$id] = $lvl1;
    }

    foreach ($lvls2 as $lvl2 => $lvls3) {
        if (isset($sectionsDb[$lvl2][$sectionsDb[$lvl1]['ID']])) {
            continue;
        }

        $id = SectionTable::add([
            'IBLOCK_ID'         => 1,
            'NAME'              => $lvl2,
            'IBLOCK_SECTION_ID' => $sectionsDb[$lvl1]['ID'],
            'CODE' => CUtil::translit($lvl1 . $lvl2, 'ru'),
            'ACTIVE'   => 'Y',
        ])->getId();

        $sectionsDbIdName[$id] = $lvl2;

        if ($sectionsDb[$lvl2]['ID']) {
            $sectionsDb[$lvl2]['IDS'][$sectionsDb[$lvl2]['ID']] = $sectionsDb[$lvl2]['IBLOCK_SECTION_ID'];
            unset($sectionsDb[$lvl2]['ID']);
            unset($sectionsDb[$lvl2]['IBLOCK_SECTION_ID']);

            $sectionsDb[$lvl2]['IDS'][$id] = $sectionsDb[$lvl1]['ID'];
        } else {
            $sectionsDb[$lvl2]['ID'] = $id;
            $sectionsDb[$lvl2]['IBLOCK_SECTION_ID'] = $sectionsDb[$lvl1]['ID'];
        }

        $sectionsDb[$lvl2][$sectionsDb[$lvl1]['ID']] = $sectionsDb[$lvl1]['ID'];

        foreach ($lvls3 as $lvl3 => $true) {
            if (isset($sectionsDb[$lvl3][$sectionsDb[$lvl2]['ID']])) {
                continue;
            }

            $id = SectionTable::add([
                'IBLOCK_ID'         => 1,
                'NAME'              => $lvl3,
                'IBLOCK_SECTION_ID' => $sectionsDb[$lvl2]['ID'] ?: array_search($sectionsDb[$lvl1]['ID'],  $sectionsDb[$lvl2]['IDS']),
                'CODE' => CUtil::translit($lvl1 . $lvl2 . $lvl3, 'ru'),
                'ACTIVE'   => 'Y',
            ])->getId();

            $sectionsDbIdName[$id] = $lvl3;

            if ($sectionsDb[$lvl3]['ID']) {
                $sectionsDb[$lvl3]['IDS'][$sectionsDb[$lvl3]['ID']] = $sectionsDb[$lvl3]['IBLOCK_SECTION_ID'];
                unset($sectionsDb[$lvl3]['ID']);
                unset($sectionsDb[$lvl3]['IBLOCK_SECTION_ID']);

                $sectionsDb[$lvl3]['IDS'][$id] = $sectionsDb[$lvl1]['ID'];
            } else {
                $sectionsDb[$lvl3]['ID'] = $id;
                $sectionsDb[$lvl3]['IBLOCK_SECTION_ID'] = $sectionsDb[$lvl1]['ID'];
            }

            $sectionsDb[$lvl3][$sectionsDb[$lvl2]['ID']]
                = $sectionsDb[$lvl2]['ID'];
        }
    }
}

foreach ($itemsData as $id => $item) {
    $el = new CIBlockElement();

    if (isset($sectionsDb[$item['SECTION_3']]['IDS'])) {
        if (isset($sectionsDb[$item['SECTION_2']]['IDS'])) {
            $idLvl2 = array_search($sectionsDb[$item['SECTION_1']]['ID'], $sectionsDb[$item['SECTION_2']]['IDS']);
        } else {
            $idLvl2 = $sectionsDb[$item['SECTION_2']]['ID'];
        }

        $idLvl3 = array_search($idLvl2, $sectionsDb[$item['SECTION_3']]['IDS']);

        $result = $el->Update(
            $id,
            [
                'IBLOCK_SECTION_ID' => $idLvl3,
            ]
        );
    } else {
        $result = $el->Update(
            $id,
            [
                'IBLOCK_SECTION_ID' => $sectionsDb[$item['SECTION_3']]['ID'],
            ]
        );
    }
}
