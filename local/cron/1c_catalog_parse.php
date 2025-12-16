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
$itemCategories = [];
$orphanElements = [];
$createdSections = 0;
$elementsProcessed = 0;
$combosProcessed = 0;
$skippedLengthMismatch = 0;
$elementSectionsLog = [];

while ($data = $rsData->fetch()) {
    $elementId = (int)$data['ID'];
    $marks = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MARK_VALUE'])), 'strlen'));
    $models = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MODEL_VALUE'])), 'strlen'));
    $bodies = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_BODY_VALUE'])), 'strlen'));

    // Категория товара (например, «Тормозные диски», «Колодки»).
    // Берём из CML2_TRAITS с описанием "Категория товара", при отсутствии отправляем в "other".
    $categoryName = null;
    $propsRes = CIBlockElement::GetProperty(
        1,
        (int)$data['ID'],
        ['sort' => 'asc'],
        ['CODE' => 'CML2_TRAITS']
    );
    while ($prop = $propsRes->Fetch()) {
        if (($prop['DESCRIPTION'] ?? '') === 'Категория товара') {
            $value = trim((string)($prop['VALUE'] ?? ''));
            if ($value !== '') {
                $categoryName = $value;
            }
            break;
        }
    }
    if ($categoryName === null || $categoryName === '') {
        $categoryName = 'other';
    }
    $itemCategories[$elementId] = $categoryName;
    if ( ! in_array($categoryName, $sectionsName, true)) {
        $sectionsName[] = $categoryName;
    }

    if (count($marks) !== count($models) || count($marks) !== count($bodies)) {
        $skippedLengthMismatch++;
        $orphanElements[$elementId] = 'length_mismatch';
        continue;
    }

    $elementsProcessed++;
    $hasValidCombo = false;

    foreach ($marks as $i => $mark) {
        $model = $models[$i];
        $body = $bodies[$i];

        if ($mark === '' || $model === '' || $body === '') {
            continue;
        }

        $combosProcessed++;
        $hasValidCombo = true;

        if ( ! in_array($mark, $sectionsName, true)) {
            $sectionsName[] = $mark;
        }

        if ( ! in_array($model, $sectionsName, true)) {
            $sectionsName[] = $model;
        }

        if ( ! in_array($body, $sectionsName, true)) {
            $sectionsName[] = $body;
        }

        $itemsData[$elementId][] = [
            'CATEGORY' => $categoryName,
            'SECTION_1' => $mark,
            'SECTION_2' => $model,
            'SECTION_3' => $body,
        ];

        $sectionsData[$categoryName][$mark][$model][$body] = true;
    }

    if (!$hasValidCombo) {
        $orphanElements[$elementId] = 'empty_values';
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

foreach ($sectionsData as $category => $marks) {
    if ( ! isset($sectionsByParent[0][$category])) {
        $id = SectionTable::add([
            'IBLOCK_ID' => 1,
            'NAME'      => $category,
            'CODE' => CUtil::translit($category, 'ru'),
            'ACTIVE'   => 'Y',
        ])->getId();

        $createdSections++;
        $sectionsDb[$category]['ID'] = $id;
        $sectionsDbIdName[$id] = $category;
        $sectionsByParent[0][$category] = $id;
    }

    foreach ($marks as $mark => $models) {
        $parentCategoryId = $sectionsByParent[0][$category];
        $markId = $sectionsByParent[$parentCategoryId][$mark] ?? null;

        // Создаем раздел марки под категорией, если его еще нет
        if (!$markId) {
            $id = SectionTable::add([
                'IBLOCK_ID'         => 1,
                'NAME'              => $mark,
                'IBLOCK_SECTION_ID' => $parentCategoryId,
                'CODE'              => CUtil::translit($category . '-' . $mark, 'ru'),
                'ACTIVE'            => 'Y',
            ])->getId();

            $createdSections++;
            $sectionsDbIdName[$id] = $mark;
            $sectionsByParent[$parentCategoryId][$mark] = $id;
            $markId = $id;

            if ($sectionsDb[$mark]['ID']) {
                $sectionsDb[$mark]['IDS'][$sectionsDb[$mark]['ID']] = $sectionsDb[$mark]['IBLOCK_SECTION_ID'];
                unset($sectionsDb[$mark]['ID']);
                unset($sectionsDb[$mark]['IBLOCK_SECTION_ID']);

                $sectionsDb[$mark]['IDS'][$id] = $sectionsDb[$category]['ID'];
            } else {
                $sectionsDb[$mark]['ID'] = $id;
                $sectionsDb[$mark]['IBLOCK_SECTION_ID'] = $sectionsDb[$category]['ID'];
            }

            $sectionsDb[$mark][$sectionsDb[$category]['ID']] = $sectionsDb[$category]['ID'];
        }

        foreach ($models as $model => $bodies) {
            $modelId = $sectionsByParent[$markId][$model] ?? null;

            // Создаем раздел модели, если его еще нет под текущей маркой
            if (!$modelId) {
                $id = SectionTable::add([
                    'IBLOCK_ID'         => 1,
                    'NAME'              => $model,
                    'IBLOCK_SECTION_ID' => $markId,
                    'CODE'              => CUtil::translit($category . '-' . $mark . '-' . $model, 'ru'),
                    'ACTIVE'            => 'Y',
                ])->getId();

                $createdSections++;
                $sectionsDbIdName[$id] = $model;
                $sectionsByParent[$markId][$model] = $id;
                $modelId = $id;

                if ($sectionsDb[$model]['ID']) {
                    $sectionsDb[$model]['IDS'][$sectionsDb[$model]['ID']] = $sectionsDb[$model]['IBLOCK_SECTION_ID'];
                    unset($sectionsDb[$model]['ID']);
                    unset($sectionsDb[$model]['IBLOCK_SECTION_ID']);

                    $sectionsDb[$model]['IDS'][$id] = $sectionsDb[$category]['ID'];
                } else {
                    $sectionsDb[$model]['ID'] = $id;
                    $sectionsDb[$model]['IBLOCK_SECTION_ID'] = $sectionsDb[$category]['ID'];
                }

                $sectionsDb[$model][$sectionsDb[$mark]['ID']]
                    = $sectionsDb[$mark]['ID'];
            }

            // Для каждой комбинации кузова под этой моделью создаем недостающие разделы
            foreach ($bodies as $body => $true) {
                if (isset($sectionsByParent[$modelId][$body])) {
                    continue;
                }

                $id = SectionTable::add([
                    'IBLOCK_ID'         => 1,
                    'NAME'              => $body,
                    'IBLOCK_SECTION_ID' => $modelId,
                    'CODE'              => CUtil::translit($category . '-' . $mark . '-' . $model . '-' . $body, 'ru'),
                    'ACTIVE'            => 'Y',
                ])->getId();

                $createdSections++;
                $sectionsDbIdName[$id] = $body;
                $sectionsByParent[$modelId][$body] = $id;

                if ($sectionsDb[$body]['ID']) {
                    $sectionsDb[$body]['IDS'][$sectionsDb[$body]['ID']] = $sectionsDb[$body]['IBLOCK_SECTION_ID'];
                    unset($sectionsDb[$body]['ID']);
                    unset($sectionsDb[$body]['IBLOCK_SECTION_ID']);

                    $sectionsDb[$body]['IDS'][$id] = $sectionsDb[$category]['ID'];
                } else {
                    $sectionsDb[$body]['ID'] = $id;
                    $sectionsDb[$body]['IBLOCK_SECTION_ID'] = $sectionsDb[$category]['ID'];
                }

                $sectionsDb[$body][$sectionsDb[$model]['ID']]
                    = $sectionsDb[$model]['ID'];
            }
        }
    }
}

foreach ($itemsData as $id => $item) {
    $el = new CIBlockElement();
    $elementSectionIds = [];

    foreach ($item as $combo) {
        $categoryId = $sectionsByParent[0][$combo['CATEGORY']] ?? null;
        $markId = $categoryId ? ($sectionsByParent[$categoryId][$combo['SECTION_1']] ?? null) : null;
        $modelId = $markId ? ($sectionsByParent[$markId][$combo['SECTION_2']] ?? null) : null;
        $bodyId  = $modelId ? ($sectionsByParent[$modelId][$combo['SECTION_3']] ?? null) : null;

        if ($bodyId) {
            $elementSectionIds[] = $bodyId;
        }
    }

    if ($elementSectionIds) {
        $elementSectionIds = array_unique($elementSectionIds);
        // Синхронизируем список разделов элемента с рассчитанными,
        // не добавляя дубликаты и не оставляя старые "хвосты".
        CIBlockElement::SetElementSection($id, $elementSectionIds);
        $elementSectionsLog[] = 'element_id=' . $id . ' sections=' . implode(',', $elementSectionIds);
    } else {
        $orphanElements[(int)$id] = $orphanElements[(int)$id] ?? 'no_sections';
        $elementSectionsLog[] = 'element_id=' . $id . ' sections=none';
    }
}

$otherSectionId = $sectionsByParent[0]['other'] ?? null;
if (!$otherSectionId) {
    $otherSectionId = SectionTable::add([
        'IBLOCK_ID' => 1,
        'NAME'      => 'other',
        'CODE'      => 'other',
        'ACTIVE'    => 'Y',
    ])->getId();
    $createdSections++;
    $sectionsDbIdName[$otherSectionId] = 'other';
    $sectionsByParent[0]['other'] = $otherSectionId;
}

foreach ($orphanElements as $elementId => $reason) {
    CIBlockElement::SetElementSection((int)$elementId, [(int)$otherSectionId]);
    $elementSectionsLog[] = 'element_id=' . (int)$elementId . ' sections=' . (int)$otherSectionId . ' fallback=other reason=' . $reason;
}

$logLine = date('c')
    . ' elements=' . $elementsProcessed
    . ' combos=' . $combosProcessed
    . ' sections_created=' . $createdSections
    . ' orphans=' . count($orphanElements)
    . ' skipped_mismatch=' . $skippedLengthMismatch
    . PHP_EOL
    . implode(PHP_EOL, $elementSectionsLog)
    . PHP_EOL;
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log', $logLine, FILE_APPEND);
