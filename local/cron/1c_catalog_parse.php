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
$createdSections = 0;
$elementsProcessed = 0;
$combosProcessed = 0;
$skippedLengthMismatch = 0;
$elementSectionsLog = [];

while ($data = $rsData->fetch()) {
    $marks = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MARK_VALUE'])), 'strlen'));
    $models = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MODEL_VALUE'])), 'strlen'));
    $bodies = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_BODY_VALUE'])), 'strlen'));

    if (count($marks) !== count($models) || count($marks) !== count($bodies)) {
        $skippedLengthMismatch++;
        continue;
    }

    $elementsProcessed++;

    foreach ($marks as $i => $mark) {
        $model = $models[$i];
        $body = $bodies[$i];

        if ($mark === '' || $model === '' || $body === '') {
            continue;
        }

        $combosProcessed++;

        if ( ! in_array($mark, $sectionsName)) {
            $sectionsName[] = $mark;
        }

        if ( ! in_array($model, $sectionsName)) {
            $sectionsName[] = $model;
        }

        if ( ! in_array($body, $sectionsName)) {
            $sectionsName[] = $body;
        }

        $itemsData[$data['ID']][] = [
            'SECTION_1' => $mark,
            'SECTION_2' => $model,
            'SECTION_3' => $body,
        ];

        $sectionsData[$mark][$model][$body] = true;
    }
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
$sectionsByParent = [];

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
    $parentId = (int)$data['IBLOCK_SECTION_ID'];
    if ($parentId === 0) {
        $parentId = 0;
    }
    $sectionsByParent[$parentId][$data['NAME']] = $data['ID'];
}

foreach ($sectionsData as $lvl1 => $lvls2) {
    if ( ! isset($sectionsByParent[0][$lvl1])) {
        $id = SectionTable::add([
            'IBLOCK_ID' => 1,
            'NAME'      => $lvl1,
            'CODE' => CUtil::translit($lvl1, 'ru'),
            'ACTIVE'   => 'Y',
        ])->getId();

        $createdSections++;
        $sectionsDb[$lvl1]['ID'] = $id;
        $sectionsDbIdName[$id] = $lvl1;
        $sectionsByParent[0][$lvl1] = $id;
    }

    foreach ($lvls2 as $lvl2 => $lvls3) {
        $parentId = $sectionsByParent[0][$lvl1];
        if (isset($sectionsByParent[$parentId][$lvl2])) {
            continue;
        }

        $id = SectionTable::add([
            'IBLOCK_ID'         => 1,
            'NAME'              => $lvl2,
            'IBLOCK_SECTION_ID' => $parentId,
            'CODE' => CUtil::translit($lvl1 . $lvl2, 'ru'),
            'ACTIVE'   => 'Y',
        ])->getId();

        $createdSections++;
        $sectionsDbIdName[$id] = $lvl2;
        $sectionsByParent[$parentId][$lvl2] = $id;

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
            $parentLvl2 = $sectionsByParent[$parentId][$lvl2] ?? null;
            if (!$parentLvl2) {
                continue;
            }
            if (isset($sectionsByParent[$parentLvl2][$lvl3])) {
                continue;
            }

            $id = SectionTable::add([
                'IBLOCK_ID'         => 1,
                'NAME'              => $lvl3,
                'IBLOCK_SECTION_ID' => $parentLvl2,
                'CODE' => CUtil::translit($lvl1 . $lvl2 . $lvl3, 'ru'),
                'ACTIVE'   => 'Y',
            ])->getId();

            $createdSections++;
            $sectionsDbIdName[$id] = $lvl3;
            $sectionsByParent[$parentLvl2][$lvl3] = $id;

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
    $elementSectionIds = [];

    foreach ($item as $combo) {
        $lvl1Id = $sectionsByParent[0][$combo['SECTION_1']] ?? null;
        $lvl2Id = $lvl1Id ? ($sectionsByParent[$lvl1Id][$combo['SECTION_2']] ?? null) : null;
        $lvl3Id = $lvl2Id ? ($sectionsByParent[$lvl2Id][$combo['SECTION_3']] ?? null) : null;

        if ($lvl3Id) {
            $elementSectionIds[] = $lvl3Id;
        }
    }

    if ($elementSectionIds) {
        CIBlockElement::SetElementSection($id, array_unique($elementSectionIds), true);
        $elementSectionsLog[] = 'element_id=' . $id . ' sections=' . implode(',', $elementSectionIds);
    } else {
        $elementSectionsLog[] = 'element_id=' . $id . ' sections=none';
    }
}

$logLine = date('c')
    . ' elements=' . $elementsProcessed
    . ' combos=' . $combosProcessed
    . ' sections_created=' . $createdSections
    . ' skipped_mismatch=' . $skippedLengthMismatch
    . PHP_EOL
    . implode(PHP_EOL, $elementSectionsLog)
    . PHP_EOL;
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log', $logLine, FILE_APPEND);
