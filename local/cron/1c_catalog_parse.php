<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

function brakes_1c_catalog_parse_run(array $options = []): array
{
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $_SERVER['DOCUMENT_ROOT'] = (string)realpath(__DIR__ . '/../../');
    }

    $iblockId = (int)($options['iblockId'] ?? 1);
    $reactivate = (bool)($options['reactivate'] ?? false);
    $syncOemNumbers = (bool)($options['syncOemNumbers'] ?? true);
    $logPath = (string)($options['logPath'] ?? ($_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log'));
    $logPrefix = (string)($options['logPrefix'] ?? 'cron');
    $oemPropertyCode = (string)($options['oemPropertyCode'] ?? 'OEM_NUMBERS');
    $oemPropertyName = (string)($options['oemPropertyName'] ?? 'Оригинальные номера');

    if (!defined('B_PROLOG_INCLUDED')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    Loader::includeModule('iblock');

    $ensureOemProperty = static function (int $iblockId, string $code, string $name): void {
        $existing = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code])->Fetch();
        if (!$existing) {
            $fields = [
                'NAME' => $name,
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'CODE' => $code,
                // В 1С-обмене значение <Ид> в <ЗначенияСвойств> сопоставляется с XML_ID свойства в Битриксе.
                // Поэтому фиксируем XML_ID равным коду свойства, чтобы 1С могла передавать <Ид>OEM_NUMBERS</Ид>.
                'XML_ID' => $code,
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
                'MULTIPLE' => 'Y',
                'FILTRABLE' => 'N',
                'SEARCHABLE' => 'Y',
            ];

            $ibp = new CIBlockProperty();
            $propertyId = (int)$ibp->Add($fields);
            if ($propertyId <= 0) {
                $error = method_exists($ibp, 'LAST_ERROR') ? (string)$ibp->LAST_ERROR : 'Unknown error';
                throw new RuntimeException('Failed to create property ' . $code . ': ' . $error);
            }
        } elseif (is_array($existing) && empty($existing['XML_ID'])) {
            $ibp = new CIBlockProperty();
            $ibp->Update((int)$existing['ID'], ['XML_ID' => $code]);
        }
    };

    $extractCrossNumbers = static function (string $raw): array {
        $result = [];
        foreach (array_values(array_filter(array_map('trim', explode(';', $raw)), 'strlen')) as $pair) {
            $number = $pair;
            if (strpos($pair, '|') !== false) {
                [$number] = explode('|', $pair, 2);
            }
            $number = trim((string)$number);
            if ($number === '') {
                continue;
            }
            $result[$number] = true;
        }
        return array_keys($result);
    };

    if ($syncOemNumbers) {
        $ensureOemProperty($iblockId, $oemPropertyCode, $oemPropertyName);
    }

    $rsData = CIBlockElement::GetList(
        arFilter: [
            'IBLOCK_ID' => $iblockId,
        ],
        arSelectFields: [
            'ID',
            'ACTIVE',
            'PROPERTY_MARK',
            'PROPERTY_MODEL',
            'PROPERTY_BODY',
        ],
    );

    $itemsData = [];
    $sectionsData = [];
    $orphanElements = [];
    $createdSections = 0;
    $elementsProcessed = 0;
    $combosProcessed = 0;
    $skippedLengthMismatch = 0;
    $elementSectionsLog = [];
    $sectionsToActivate = [];
    $elementsToActivate = [];
    $oemUpdated = 0;
    $oemSkippedMissingCross = 0;
    $oemSkippedUnchanged = 0;
    $oemErrors = 0;

    while ($data = $rsData->fetch()) {
        $elementId = (int)$data['ID'];
        if ($reactivate && ($data['ACTIVE'] ?? 'Y') !== 'Y') {
            $elementsToActivate[$elementId] = true;
        }

        $marks = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MARK_VALUE'])), 'strlen'));
        $models = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MODEL_VALUE'])), 'strlen'));
        $bodies = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_BODY_VALUE'])), 'strlen'));

        $categoryName = null;
        $crossRaw = null;
        $propsRes = CIBlockElement::GetProperty(
            $iblockId,
            (int)$data['ID'],
            ['sort' => 'asc'],
            ['CODE' => 'CML2_TRAITS']
        );
        while ($prop = $propsRes->Fetch()) {
            $desc = (string)($prop['DESCRIPTION'] ?? '');
            if ($desc === 'Категория товара') {
                $value = trim((string)($prop['VALUE'] ?? ''));
                if ($value !== '') {
                    $categoryName = $value;
                }
            } elseif ($syncOemNumbers && $desc === 'Кросс номера') {
                $value = trim((string)($prop['VALUE'] ?? ''));
                if ($value !== '') {
                    $crossRaw = $value;
                }
            }

            if ($categoryName !== null && (!$syncOemNumbers || $crossRaw !== null)) {
                break;
            }
        }
        if ($categoryName === null || $categoryName === '') {
            $categoryName = 'other';
        }

        if ($syncOemNumbers) {
            if ($crossRaw === null || $crossRaw === '') {
                $oemSkippedMissingCross++;
            } else {
                try {
                    $incoming = $extractCrossNumbers($crossRaw);
                    sort($incoming);

                    $existing = [];
                    $existingRes = CIBlockElement::GetProperty(
                        $iblockId,
                        $elementId,
                        ['sort' => 'asc'],
                        ['CODE' => $oemPropertyCode]
                    );
                    while ($p = $existingRes->Fetch()) {
                        $val = trim((string)($p['VALUE'] ?? ''));
                        if ($val !== '') {
                            $existing[$val] = true;
                        }
                    }
                    $existing = array_keys($existing);
                    sort($existing);

                    if ($existing === $incoming) {
                        $oemSkippedUnchanged++;
                    } else {
                        CIBlockElement::SetPropertyValuesEx(
                            $elementId,
                            $iblockId,
                            [$oemPropertyCode => $incoming]
                        );
                        $oemUpdated++;
                    }
                } catch (\Throwable $exception) {
                    $oemErrors++;
                }
            }
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
            'IBLOCK_ID' => $iblockId,
        ],
        'select' => [
            'ID',
            'NAME',
            'IBLOCK_SECTION_ID',
        ],
    ]);

    $sectionsByParent = [];
    while ($data = $rsData->fetch()) {
        $parentId = (int)$data['IBLOCK_SECTION_ID'];
        $sectionsByParent[$parentId][$data['NAME']] = (int)$data['ID'];
    }

    foreach ($sectionsData as $category => $marks) {
        if (!isset($sectionsByParent[0][$category])) {
            $id = SectionTable::add([
                'IBLOCK_ID' => $iblockId,
                'NAME'      => $category,
                'CODE'      => CUtil::translit($category, 'ru'),
                'ACTIVE'    => 'Y',
            ])->getId();

            $createdSections++;
            $sectionsByParent[0][$category] = $id;
        }

        foreach ($marks as $mark => $models) {
            $parentCategoryId = $sectionsByParent[0][$category];
            $markId = $sectionsByParent[$parentCategoryId][$mark] ?? null;

            if (!$markId) {
                $id = SectionTable::add([
                    'IBLOCK_ID'         => $iblockId,
                    'NAME'              => $mark,
                    'IBLOCK_SECTION_ID' => $parentCategoryId,
                    'CODE'              => CUtil::translit($category . '-' . $mark, 'ru'),
                    'ACTIVE'            => 'Y',
                ])->getId();

                $createdSections++;
                $sectionsByParent[$parentCategoryId][$mark] = $id;
                $markId = $id;
            }

            foreach ($models as $model => $bodies) {
                $modelId = $sectionsByParent[$markId][$model] ?? null;

                if (!$modelId) {
                    $id = SectionTable::add([
                        'IBLOCK_ID'         => $iblockId,
                        'NAME'              => $model,
                        'IBLOCK_SECTION_ID' => $markId,
                        'CODE'              => CUtil::translit($category . '-' . $mark . '-' . $model, 'ru'),
                        'ACTIVE'            => 'Y',
                    ])->getId();

                    $createdSections++;
                    $sectionsByParent[$markId][$model] = $id;
                    $modelId = $id;
                }

                foreach ($bodies as $body => $true) {
                    if (isset($sectionsByParent[$modelId][$body])) {
                        continue;
                    }

                    $id = SectionTable::add([
                        'IBLOCK_ID'         => $iblockId,
                        'NAME'              => $body,
                        'IBLOCK_SECTION_ID' => $modelId,
                        'CODE'              => CUtil::translit($category . '-' . $mark . '-' . $model . '-' . $body, 'ru'),
                        'ACTIVE'            => 'Y',
                    ])->getId();

                    $createdSections++;
                    $sectionsByParent[$modelId][$body] = $id;
                }
            }
        }
    }

    $otherSectionId = $sectionsByParent[0]['other'] ?? null;
    if (!$otherSectionId) {
        $otherSectionId = SectionTable::add([
            'IBLOCK_ID' => $iblockId,
            'NAME'      => 'other',
            'CODE'      => 'other',
            'ACTIVE'    => 'Y',
        ])->getId();
        $createdSections++;
        $sectionsByParent[0]['other'] = $otherSectionId;
    }

    foreach ($itemsData as $id => $item) {
        $elementSectionIds = [];

        foreach ($item as $combo) {
            $categoryId = $sectionsByParent[0][$combo['CATEGORY']] ?? null;
            $markId = $categoryId ? ($sectionsByParent[$categoryId][$combo['SECTION_1']] ?? null) : null;
            $modelId = $markId ? ($sectionsByParent[$markId][$combo['SECTION_2']] ?? null) : null;
            $bodyId  = $modelId ? ($sectionsByParent[$modelId][$combo['SECTION_3']] ?? null) : null;

            if ($categoryId) {
                $sectionsToActivate[$categoryId] = true;
            }
            if ($markId) {
                $sectionsToActivate[$markId] = true;
            }
            if ($modelId) {
                $sectionsToActivate[$modelId] = true;
            }

            if ($bodyId) {
                $sectionsToActivate[$bodyId] = true;
                $elementSectionIds[] = $bodyId;
            }
        }

        if ($elementSectionIds) {
            $elementSectionIds = array_unique($elementSectionIds);
            CIBlockElement::SetElementSection($id, $elementSectionIds);
            $elementSectionsLog[] = 'element_id=' . $id . ' sections=' . implode(',', $elementSectionIds);
        } else {
            $orphanElements[(int)$id] = $orphanElements[(int)$id] ?? 'no_sections';
            $elementSectionsLog[] = 'element_id=' . $id . ' sections=none';
        }
    }

    if ($otherSectionId) {
        $sectionsToActivate[(int)$otherSectionId] = true;
    }

    foreach ($orphanElements as $elementId => $reason) {
        CIBlockElement::SetElementSection((int)$elementId, [(int)$otherSectionId]);
        $elementSectionsLog[] = 'element_id=' . (int)$elementId . ' sections=' . (int)$otherSectionId . ' fallback=other reason=' . $reason;
    }

    $sectionsActivated = 0;
    if ($reactivate) {
        foreach (array_keys($sectionsToActivate) as $sectionId) {
            $result = SectionTable::update((int)$sectionId, ['ACTIVE' => 'Y']);
            if ($result->isSuccess()) {
                $sectionsActivated++;
            }
        }

        if ($elementsToActivate) {
            $element = new CIBlockElement();
            foreach (array_keys($elementsToActivate) as $elementId) {
                if ($element->Update((int)$elementId, ['ACTIVE' => 'Y'])) {
                    // no-op
                }
            }
        }

        CIBlockSection::ReSort($iblockId);
    }

    $logLine = date('c')
        . ' src=' . $logPrefix
        . ' iblock=' . $iblockId
        . ' elements=' . $elementsProcessed
        . ' combos=' . $combosProcessed
        . ' sections_created=' . $createdSections
        . ' sections_reactivated=' . $sectionsActivated
        . ' orphans=' . count($orphanElements)
        . ' skipped_mismatch=' . $skippedLengthMismatch
        . ' oem_enabled=' . ($syncOemNumbers ? '1' : '0')
        . ' oem_updated=' . $oemUpdated
        . ' oem_skipped_no_cross=' . $oemSkippedMissingCross
        . ' oem_skipped_unchanged=' . $oemSkippedUnchanged
        . ' oem_errors=' . $oemErrors
        . PHP_EOL
        . implode(PHP_EOL, $elementSectionsLog)
        . PHP_EOL;
    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }

    if (!file_exists($logPath)) {
        @touch($logPath);
    }

    if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
        @chmod($logPath, 0666);
    }

    $written = @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('1c_catalog_parse: failed to append log to ' . $logPath);
    }

    return [
        'elementsProcessed' => $elementsProcessed,
        'combosProcessed' => $combosProcessed,
        'createdSections' => $createdSections,
        'sectionsActivated' => $sectionsActivated,
        'orphans' => count($orphanElements),
        'skippedLengthMismatch' => $skippedLengthMismatch,
        'oem' => [
            'enabled' => $syncOemNumbers,
            'propertyCode' => $oemPropertyCode,
            'updated' => $oemUpdated,
            'skippedMissingCross' => $oemSkippedMissingCross,
            'skippedUnchanged' => $oemSkippedUnchanged,
            'errors' => $oemErrors,
        ],
    ];
}

if (PHP_SAPI === 'cli' || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    brakes_1c_catalog_parse_run([
        'iblockId' => 1,
        'reactivate' => true,
        'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
        'logPrefix' => 'manual',
    ]);
}
