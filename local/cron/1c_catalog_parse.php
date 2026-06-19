<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

function brakes_1c_catalog_parse_run(array $options = []): array
{
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $root = realpath(__DIR__ . '/../../');
        if ($root === false) {
            $root = realpath(getcwd());
        }
        if ($root === false) {
            $root = dirname(__DIR__, 2);
        }
        $_SERVER['DOCUMENT_ROOT'] = (string)$root;
    }

    if (PHP_SAPI === 'cli' && date_default_timezone_get() === 'Etc/UTC') {
        $systemTimezone = trim((string)@file_get_contents('/etc/timezone'));
        if ($systemTimezone !== '') {
            @date_default_timezone_set($systemTimezone);
        }
    }

    $iblockId = (int)($options['iblockId'] ?? 1);
    // Backward-compatible switch:
    // - reactivate=true previously meant: activate used sections + activate inactive elements.
    // - now you can control them separately via reactivateSections/reactivateElements.
    $reactivate = (bool)($options['reactivate'] ?? false);
    $reactivateSections = array_key_exists('reactivateSections', $options) ? (bool)$options['reactivateSections'] : $reactivate;
    $reactivateElements = array_key_exists('reactivateElements', $options) ? (bool)$options['reactivateElements'] : $reactivate;
    $syncOemNumbers = (bool)($options['syncOemNumbers'] ?? true);
    $syncCategoryProperty = (bool)($options['syncCategoryProperty'] ?? true);
    $logPath = (string)($options['logPath'] ?? ($_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log'));
    $logPrefix = (string)($options['logPrefix'] ?? 'cron');
    $oemPropertyCode = (string)($options['oemPropertyCode'] ?? 'OEM_NUMBERS');
    $oemPropertyName = (string)($options['oemPropertyName'] ?? 'Оригинальные номера');
    $categoryPropertyCode = (string)($options['categoryPropertyCode'] ?? 'PRODUCT_CATEGORY');
    $categoryPropertyName = (string)($options['categoryPropertyName'] ?? 'Категория товара');

    if (!defined('B_PROLOG_INCLUDED')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    Loader::includeModule('iblock');

    if (!function_exists('brakes_1c_parse_log_event')) {
        function brakes_1c_parse_log_event(string $severity, string $message, array $context = []): void
        {
            if (!class_exists('CEventLog')) {
                return;
            }

            $description = $message;
            if ($context) {
                $description .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            CEventLog::Add([
                'SEVERITY' => $severity,
                'AUDIT_TYPE_ID' => 'BRKS_1C_PARSE',
                'MODULE_ID' => 'brakes',
                'ITEM_ID' => '1c_catalog_parse',
                'DESCRIPTION' => $description,
            ]);
        }
    }

    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    // Always try to write logs (best-effort), like before.
    $logWritable = true;

    $lockName = (string)($options['lockName'] ?? 'brakes_1c_catalog_parse');
    $lockTimeout = (int)($options['lockTimeout'] ?? 0);
    $lockAcquired = true;
    $lockError = null;
    $connection = null;
    $dbType = '';

    try {
        $connection = \Bitrix\Main\Application::getConnection();
        $dbType = method_exists($connection, 'getType') ? (string)$connection->getType() : '';
        if (in_array($dbType, ['mysql', 'mysqli'], true)) {
            $helper = $connection->getSqlHelper();
            $safeLock = $helper->forSql($lockName);
            $lockAcquired = ((int)$connection->queryScalar("SELECT GET_LOCK('{$safeLock}', {$lockTimeout})") === 1);
        }
    } catch (\Throwable $exception) {
        $lockAcquired = false;
        $lockError = $exception->getMessage();
    }

    if (!$lockAcquired) {
        $message = '1c_catalog_parse: lock not acquired';
        if ($lockError !== null) {
            $message .= ' error=' . $lockError;
        }
        if ($logWritable) {
            @file_put_contents(
                $logPath,
                date('c') . ' src=' . $logPrefix . ' status=skipped_lock' . ($lockError ? ' error=' . $lockError : '') . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
        brakes_1c_parse_log_event('WARNING', $message);
        return [
            'status' => 'skipped_lock',
            'error' => $lockError,
        ];
    }

    try {
    $normalizeCategory = static function (?string $raw): array {
        $value = trim((string)$raw);
        if ($value === '') {
            return ['code' => 'other', 'name' => 'other'];
        }

        $valueLower = mb_strtolower($value);
        $valueLower = str_replace(['ё'], ['е'], $valueLower);

        if (str_contains($valueLower, 'колод')) {
            return ['code' => 'tormoznye_kolodki', 'name' => 'Тормозные колодки'];
        }
        if (str_contains($valueLower, 'диск')) {
            return ['code' => 'tormoznye_diski', 'name' => 'Тормозные диски'];
        }
        if (str_contains($valueLower, 'систем')) {
            return ['code' => 'tormoznye_sistemy', 'name' => 'Тормозные системы'];
        }

        return ['code' => 'other', 'name' => 'other'];
    };

    $slugify = static function (string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $slug = (string)CUtil::translit(
            $value,
            'ru',
            [
                'max_len' => 100,
                'change_case' => 'L',
                'replace_space' => '_',
                'replace_other' => '_',
                'delete_repeat_replace' => true,
            ]
        );
        $slug = trim($slug, "_- \t\n\r\0\x0B");
        return $slug;
    };

    $makeSectionXmlId = static function (string $type, array $parts): string {
        $clean = [];
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part !== '') {
                $clean[] = $part;
            }
        }
        return 'BRKS:' . $type . ':' . implode(':', $clean);
    };

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

    $ensureCategoryProperty = static function (int $iblockId, string $code, string $name): void {
        $existing = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, '=CODE' => $code])->Fetch();
        if (!$existing) {
            $fields = [
                'NAME' => $name,
                'ACTIVE' => 'Y',
                'SORT' => 500,
                'CODE' => $code,
                'XML_ID' => $code,
                'PROPERTY_TYPE' => 'S',
                'IBLOCK_ID' => $iblockId,
                'MULTIPLE' => 'N',
                'FILTRABLE' => 'N',
                'SEARCHABLE' => 'Y',
            ];

            $ibp = new CIBlockProperty();
            $propertyId = (int)$ibp->Add($fields);
            if ($propertyId <= 0) {
                $error = method_exists($ibp, 'LAST_ERROR') ? (string)$ibp->LAST_ERROR : 'Unknown error';
                throw new RuntimeException('Failed to create property ' . $code . ': ' . $error);
            }
            return;
        }

        $updates = [];
        if (empty($existing['XML_ID'])) {
            $updates['XML_ID'] = $code;
        }
        if (($existing['SEARCHABLE'] ?? 'N') !== 'Y') {
            $updates['SEARCHABLE'] = 'Y';
        }
        if ($updates) {
            $ibp = new CIBlockProperty();
            $ibp->Update((int)$existing['ID'], $updates);
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
    if ($syncCategoryProperty) {
        $ensureCategoryProperty($iblockId, $categoryPropertyCode, $categoryPropertyName);
    }

    $warnings = [];
    $createdSections = 0;
    $sectionsUpdated = 0;
    $codeConflictsFixed = 0;

    $sectionsByXmlId = [];
    $sectionsByParentAndCode = [];
    $sectionsByParentAndName = [];
    $ensuredXmlIds = [];
    $attemptedXmlIds = [];
    $warningOnce = [];

    $rsSections = SectionTable::getList([
        'filter' => [
            'IBLOCK_ID' => $iblockId,
        ],
        'select' => [
            'ID',
            'NAME',
            'CODE',
            'XML_ID',
            'IBLOCK_SECTION_ID',
        ],
    ]);
    while ($row = $rsSections->fetch()) {
        $sectionId = (int)$row['ID'];
        $parentId = (int)$row['IBLOCK_SECTION_ID'];
        $name = (string)($row['NAME'] ?? '');
        $code = (string)($row['CODE'] ?? '');
        $xmlId = trim((string)($row['XML_ID'] ?? ''));

        if ($xmlId !== '') {
            $sectionsByXmlId[$xmlId][] = $sectionId;
        }
        if ($code !== '') {
            $sectionsByParentAndCode[$parentId][$code][] = $sectionId;
        }
        if ($name !== '') {
            $sectionsByParentAndName[$parentId][$name][] = $sectionId;
        }
    }

    $bs = new CIBlockSection();
    $ensureSection = static function (array $fields) use (
        $iblockId,
        &$warnings,
        &$warningOnce,
        &$sectionsByXmlId,
        &$sectionsByParentAndCode,
        &$sectionsByParentAndName,
        &$ensuredXmlIds,
        &$attemptedXmlIds,
        &$createdSections,
        &$sectionsUpdated,
        &$codeConflictsFixed,
        $bs
    ): int {
        $xmlId = trim((string)($fields['XML_ID'] ?? ''));
        $parentId = (int)($fields['IBLOCK_SECTION_ID'] ?? 0);
        $code = (string)($fields['CODE'] ?? '');
        $name = (string)($fields['NAME'] ?? '');

        $makeUniqueCode = static function (string $baseCode, int $sectionId) use ($iblockId): string {
            $maxLen = 255;
            $suffix = '__dup' . $sectionId;
            $prefixMax = max(1, $maxLen - strlen($suffix));
            $candidate = substr($baseCode, 0, $prefixMax) . $suffix;
            $candidate = trim($candidate, "_- \t\n\r\0\x0B");
            if ($candidate === '') {
                $candidate = 'dup' . $sectionId;
            }

            $i = 0;
            $unique = $candidate;
            while (true) {
                $conflict = SectionTable::getList([
                    'filter' => [
                        '=IBLOCK_ID' => $iblockId,
                        '=CODE' => $unique,
                    ],
                    'select' => ['ID'],
                    'limit' => 1,
                ])->fetch();
                if (!$conflict) {
                    break;
                }
                $i++;
                $suffix2 = '__dup' . $sectionId . '_' . $i;
                $prefixMax2 = max(1, $maxLen - strlen($suffix2));
                $unique = substr($baseCode, 0, $prefixMax2) . $suffix2;
                $unique = trim($unique, "_- \t\n\r\0\x0B");
                if ($unique === '') {
                    $unique = 'dup' . $sectionId . '_' . $i;
                }
            }
            return $unique;
        };

        $tryResolveDuplicateCode = static function (int $targetId, string $desiredCode) use (
            $iblockId,
            $makeUniqueCode,
            &$warnings,
            &$warningOnce,
            &$codeConflictsFixed,
            $bs
        ): bool {
            $desiredCode = trim($desiredCode);
            if ($desiredCode === '') {
                return false;
            }

            $conflict = SectionTable::getList([
                'filter' => [
                    '=IBLOCK_ID' => $iblockId,
                    '=CODE' => $desiredCode,
                    '!=ID' => $targetId,
                ],
                'select' => ['ID', 'XML_ID'],
                'limit' => 1,
            ])->fetch();
            if (!$conflict) {
                return false;
            }

            $conflictId = (int)$conflict['ID'];
            $conflictXml = trim((string)($conflict['XML_ID'] ?? ''));
            if ($conflictXml !== '' && str_starts_with($conflictXml, 'BRKS:')) {
                $msg = 'code_conflict_with_brks target=' . $targetId . ' code=' . $desiredCode . ' conflict_id=' . $conflictId . ' conflict_xml=' . $conflictXml;
                if (!isset($warningOnce[$msg])) {
                    $warningOnce[$msg] = true;
                    $warnings[] = $msg;
                }
                return false;
            }

            $newCode = $makeUniqueCode($desiredCode, $conflictId);
            $res = $bs->Update($conflictId, ['CODE' => $newCode], false);
            if (!$res) {
                $msg = 'code_conflict_rename_failed target=' . $targetId . ' code=' . $desiredCode . ' conflict_id=' . $conflictId . ' new_code=' . $newCode . ' error=' . (string)$bs->LAST_ERROR;
                if (!isset($warningOnce[$msg])) {
                    $warningOnce[$msg] = true;
                    $warnings[] = $msg;
                }
                return false;
            }

            $codeConflictsFixed++;
            return true;
        };

        $existingIds = $xmlId !== '' ? ($sectionsByXmlId[$xmlId] ?? []) : [];
        if (count($existingIds) > 1) {
            sort($existingIds);
            $msg = 'duplicate_xml_id xml_id=' . $xmlId . ' ids=' . implode(',', $existingIds) . ' pick=' . $existingIds[0];
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
        }
        if ($existingIds) {
            $id = (int)min($existingIds);
            if ($xmlId !== '' && isset($ensuredXmlIds[$xmlId])) {
                return $id;
            }
            if ($xmlId !== '' && isset($attemptedXmlIds[$xmlId])) {
                return $id;
            }
            if ($xmlId !== '') {
                $attemptedXmlIds[$xmlId] = true;
            }
            $updateFields = $fields;
            $updateFields['IBLOCK_ID'] = $iblockId;
            $res = $bs->Update($id, $updateFields, false);
            if (!$res && $code !== '' && str_contains((string)$bs->LAST_ERROR, 'символьным кодом')) {
                if ($tryResolveDuplicateCode($id, $code)) {
                    $res = $bs->Update($id, $updateFields, false);
                }
            }
            if ($res) {
                $sectionsUpdated++;
                if ($xmlId !== '') {
                    $ensuredXmlIds[$xmlId] = true;
                }
            } else {
                $msg = 'section_update_failed id=' . $id . ' xml_id=' . $xmlId . ' error=' . (string)$bs->LAST_ERROR;
                if (!isset($warningOnce[$msg])) {
                    $warningOnce[$msg] = true;
                    $warnings[] = $msg;
                }
            }
            return $id;
        }

        $candidates = [];
        if ($code !== '' && isset($sectionsByParentAndCode[$parentId][$code])) {
            $candidates = $sectionsByParentAndCode[$parentId][$code];
        } elseif ($name !== '' && isset($sectionsByParentAndName[$parentId][$name])) {
            $candidates = $sectionsByParentAndName[$parentId][$name];
        }

        if (count($candidates) > 1) {
            sort($candidates);
            $msg = 'duplicate_section_candidates parent=' . $parentId . ' code=' . $code . ' name=' . $name . ' ids=' . implode(',', $candidates) . ' pick=' . $candidates[0];
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
        }
        if ($candidates) {
            $id = (int)min($candidates);
            if ($xmlId !== '' && isset($attemptedXmlIds[$xmlId])) {
                return $id;
            }
            if ($xmlId !== '') {
                $attemptedXmlIds[$xmlId] = true;
            }
            $updateFields = $fields;
            $updateFields['IBLOCK_ID'] = $iblockId;
            $res = $bs->Update($id, $updateFields, false);
            if (!$res && $code !== '' && str_contains((string)$bs->LAST_ERROR, 'символьным кодом')) {
                if ($tryResolveDuplicateCode($id, $code)) {
                    $res = $bs->Update($id, $updateFields, false);
                }
            }
            if ($res) {
                $sectionsUpdated++;
                if ($xmlId !== '') {
                    $ensuredXmlIds[$xmlId] = true;
                }
            } else {
                $msg = 'section_update_failed id=' . $id . ' xml_id=' . $xmlId . ' error=' . (string)$bs->LAST_ERROR;
                if (!isset($warningOnce[$msg])) {
                    $warningOnce[$msg] = true;
                    $warnings[] = $msg;
                }
            }

            if ($xmlId !== '') {
                $sectionsByXmlId[$xmlId][] = $id;
            }
            if ($code !== '') {
                $sectionsByParentAndCode[$parentId][$code][] = $id;
            }
            if ($name !== '') {
                $sectionsByParentAndName[$parentId][$name][] = $id;
            }
            return $id;
        }

        $addFields = $fields;
        $addFields['IBLOCK_ID'] = $iblockId;
        $id = (int)$bs->Add($addFields, false);
        if ($id <= 0) {
            $msg = 'section_add_failed parent=' . $parentId . ' code=' . $code . ' xml_id=' . $xmlId . ' error=' . (string)$bs->LAST_ERROR;
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
            return 0;
        }
        $createdSections++;
        if ($xmlId !== '') {
            $ensuredXmlIds[$xmlId] = true;
        }

        if ($xmlId !== '') {
            $sectionsByXmlId[$xmlId][] = $id;
        }
        if ($code !== '') {
            $sectionsByParentAndCode[$parentId][$code][] = $id;
        }
        if ($name !== '') {
            $sectionsByParentAndName[$parentId][$name][] = $id;
        }

        return $id;
    };

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
    $orphanElements = [];
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
    $categoryUpdated = 0;
    $categorySkippedEmpty = 0;
    $categorySkippedUnchanged = 0;
    $categoryErrors = 0;

    while ($data = $rsData->fetch()) {
        $elementId = (int)$data['ID'];
        if ($reactivateElements && ($data['ACTIVE'] ?? 'Y') !== 'Y') {
            $elementsToActivate[$elementId] = true;
        }

        $marks = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MARK_VALUE'])), 'strlen'));
        $models = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_MODEL_VALUE'])), 'strlen'));
        $bodies = array_values(array_filter(array_map('trim', explode(';', (string)$data['PROPERTY_BODY_VALUE'])), 'strlen'));

        $categoryTrait = null;
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
                    $categoryTrait = $value;
                }
            } elseif ($syncOemNumbers && $desc === 'Кросс номера') {
                $value = trim((string)($prop['VALUE'] ?? ''));
                if ($value !== '') {
                    $crossRaw = $value;
                }
            }

            if ($categoryTrait !== null && (!$syncOemNumbers || $crossRaw !== null)) {
                break;
            }
        }
        $category = $normalizeCategory($categoryTrait);

        if ($syncCategoryProperty) {
            $categoryValue = $category['code'] === 'other' ? '' : (string)$category['name'];
            try {
                $existingValue = '';
                $existingRes = CIBlockElement::GetProperty(
                    $iblockId,
                    $elementId,
                    ['sort' => 'asc'],
                    ['CODE' => $categoryPropertyCode]
                );
                while ($p = $existingRes->Fetch()) {
                    $val = trim((string)($p['VALUE'] ?? ''));
                    if ($val !== '') {
                        $existingValue = $val;
                        break;
                    }
                }

                if ($categoryValue === '') {
                    if ($existingValue === '') {
                        $categorySkippedEmpty++;
                    } else {
                        CIBlockElement::SetPropertyValuesEx(
                            $elementId,
                            $iblockId,
                            [$categoryPropertyCode => false]
                        );
                        $categoryUpdated++;
                    }
                } elseif ($existingValue === $categoryValue) {
                    $categorySkippedUnchanged++;
                } else {
                    CIBlockElement::SetPropertyValuesEx(
                        $elementId,
                        $iblockId,
                        [$categoryPropertyCode => $categoryValue]
                    );
                    $categoryUpdated++;
                }
            } catch (\Throwable $exception) {
                $categoryErrors++;
            }
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

            $markSlug = $slugify($mark);
            $modelSlug = $slugify($model);
            $bodySlug = $slugify($body);
            if ($markSlug === '' || $modelSlug === '' || $bodySlug === '') {
                continue;
            }

            $itemsData[$elementId][] = [
                'CATEGORY_CODE' => $category['code'],
                'CATEGORY_NAME' => $category['name'],
                'MARK_NAME' => $mark,
                'MARK_SLUG' => $markSlug,
                'MODEL_NAME' => $model,
                'MODEL_SLUG' => $modelSlug,
                'BODY_NAME' => $body,
                'BODY_SLUG' => $bodySlug,
            ];
        }

        if (!$hasValidCombo) {
            $orphanElements[$elementId] = 'empty_values';
        }
    }

    $categorySectionIds = [];
    $getCategorySectionId = static function (string $categoryCode, string $categoryName) use (&$categorySectionIds, $makeSectionXmlId, $ensureSection): int {
        if (isset($categorySectionIds[$categoryCode])) {
            return (int)$categorySectionIds[$categoryCode];
        }
        $xmlId = $makeSectionXmlId('CAT', [$categoryCode]);
        $id = $ensureSection([
            'ACTIVE' => 'Y',
            'SORT' => 500,
            'IBLOCK_SECTION_ID' => 0,
            'NAME' => $categoryName,
            'CODE' => $categoryCode,
            'XML_ID' => $xmlId,
        ]);
        if ($id > 0) {
            $categorySectionIds[$categoryCode] = $id;
        }
        return (int)$id;
    };

    $otherSectionId = $getCategorySectionId('other', 'other');

    foreach ($itemsData as $id => $item) {
        $elementSectionIds = [];

        foreach ($item as $combo) {
            $categoryCode = (string)$combo['CATEGORY_CODE'];
            $categoryName = (string)$combo['CATEGORY_NAME'];

            $categoryId = $getCategorySectionId($categoryCode, $categoryName);
            if ($categoryId > 0) {
                $sectionsToActivate[$categoryId] = true;
            }

            $markSlug = (string)$combo['MARK_SLUG'];
            $modelSlug = (string)$combo['MODEL_SLUG'];
            $bodySlug = (string)$combo['BODY_SLUG'];

            $markId = 0;
            if ($categoryId > 0) {
                $markXmlId = $makeSectionXmlId('MARK', [$categoryCode, $markSlug]);
                $markCode = $categoryCode . '_' . $markSlug;
                $markId = $ensureSection([
                    'ACTIVE' => 'Y',
                    'SORT' => 500,
                    'IBLOCK_SECTION_ID' => $categoryId,
                    'NAME' => (string)$combo['MARK_NAME'],
                    'CODE' => $markCode,
                    'XML_ID' => $markXmlId,
                ]);
                if ($markId > 0) {
                    $sectionsToActivate[$markId] = true;
                }
            }

            $modelId = 0;
            if ($markId > 0) {
                $modelXmlId = $makeSectionXmlId('MODEL', [$categoryCode, $markSlug, $modelSlug]);
                $modelCode = $categoryCode . '_' . $markSlug . '_' . $modelSlug;
                $modelId = $ensureSection([
                    'ACTIVE' => 'Y',
                    'SORT' => 500,
                    'IBLOCK_SECTION_ID' => $markId,
                    'NAME' => (string)$combo['MODEL_NAME'],
                    'CODE' => $modelCode,
                    'XML_ID' => $modelXmlId,
                ]);
                if ($modelId > 0) {
                    $sectionsToActivate[$modelId] = true;
                }
            }

            $bodyId = 0;
            if ($modelId > 0) {
                $bodyXmlId = $makeSectionXmlId('BODY', [$categoryCode, $markSlug, $modelSlug, $bodySlug]);
                $bodyCode = $categoryCode . '_' . $markSlug . '_' . $modelSlug . '_' . $bodySlug;
                $bodyId = $ensureSection([
                    'ACTIVE' => 'Y',
                    'SORT' => 500,
                    'IBLOCK_SECTION_ID' => $modelId,
                    'NAME' => (string)$combo['BODY_NAME'],
                    'CODE' => $bodyCode,
                    'XML_ID' => $bodyXmlId,
                ]);
                if ($bodyId > 0) {
                    $sectionsToActivate[$bodyId] = true;
                }
            }

            if ($bodyId) {
                $elementSectionIds[] = $bodyId;
            }
        }

        if ($elementSectionIds) {
            $elementSectionIds = array_unique($elementSectionIds);
            $changed = CIBlockElement::SetElementSection($id, $elementSectionIds);
            if ($changed) {
                if (class_exists(\Bitrix\Iblock\PropertyIndex\Manager::class)) {
                    \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($iblockId, $id);
                }
            }
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
        $changed = CIBlockElement::SetElementSection((int)$elementId, [(int)$otherSectionId]);
        if ($changed) {
            if (class_exists(\Bitrix\Iblock\PropertyIndex\Manager::class)) {
                \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($iblockId, (int)$elementId);
            }
        }
        $elementSectionsLog[] = 'element_id=' . (int)$elementId . ' sections=' . (int)$otherSectionId . ' fallback=other reason=' . $reason;
    }

    $sectionsActivated = 0;
    if ($reactivateSections) {
        foreach (array_keys($sectionsToActivate) as $sectionId) {
            $res = $bs->Update((int)$sectionId, ['ACTIVE' => 'Y'], false);
            if ($res) {
                $sectionsActivated++;
            } else {
                $warnings[] = 'section_activate_failed id=' . (int)$sectionId . ' error=' . (string)$bs->LAST_ERROR;
            }
        }

        if ($reactivateElements && $elementsToActivate) {
            $element = new CIBlockElement();
            foreach (array_keys($elementsToActivate) as $elementId) {
                if ($element->Update((int)$elementId, ['ACTIVE' => 'Y'])) {
                    // no-op
                }
            }
        }

        CIBlockSection::ReSort($iblockId);
    }

    if ($createdSections > 0 && !$reactivate) {
        CIBlockSection::ReSort($iblockId);
    }

    $logLine = date('c')
        . ' src=' . $logPrefix
        . ' iblock=' . $iblockId
        . ' elements=' . $elementsProcessed
        . ' combos=' . $combosProcessed
        . ' sections_created=' . $createdSections
        . ' sections_updated=' . $sectionsUpdated
        . ' code_conflicts_fixed=' . $codeConflictsFixed
        . ' sections_reactivated=' . $sectionsActivated
        . ' orphans=' . count($orphanElements)
        . ' skipped_mismatch=' . $skippedLengthMismatch
        . ' oem_enabled=' . ($syncOemNumbers ? '1' : '0')
        . ' oem_updated=' . $oemUpdated
        . ' oem_skipped_no_cross=' . $oemSkippedMissingCross
        . ' oem_skipped_unchanged=' . $oemSkippedUnchanged
        . ' oem_errors=' . $oemErrors
        . ' category_enabled=' . ($syncCategoryProperty ? '1' : '0')
        . ' category_updated=' . $categoryUpdated
        . ' category_skipped_empty=' . $categorySkippedEmpty
        . ' category_skipped_unchanged=' . $categorySkippedUnchanged
        . ' category_errors=' . $categoryErrors
        . ' warnings=' . count($warnings)
        . PHP_EOL
        . implode(PHP_EOL, $elementSectionsLog)
        . PHP_EOL;
    if ($warnings) {
        $logLine .= implode(PHP_EOL, array_map(static fn ($w) => 'warn ' . $w, $warnings)) . PHP_EOL;
    }
    if ($logWritable) {
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
    }

    } finally {
        if ($connection && in_array($dbType, ['mysql', 'mysqli'], true)) {
            try {
                $helper = $connection->getSqlHelper();
                $safeLock = $helper->forSql($lockName);
                $connection->queryExecute("SELECT RELEASE_LOCK('{$safeLock}')");
            } catch (\Throwable $exception) {
                // ignore lock release errors
            }
        }
    }

    return [
        'elementsProcessed' => $elementsProcessed,
        'combosProcessed' => $combosProcessed,
        'createdSections' => $createdSections,
        'updatedSections' => $sectionsUpdated,
        'codeConflictsFixed' => $codeConflictsFixed,
        'sectionsActivated' => $sectionsActivated,
        'orphans' => count($orphanElements),
        'skippedLengthMismatch' => $skippedLengthMismatch,
        'warnings' => count($warnings),
        'oem' => [
            'enabled' => $syncOemNumbers,
            'propertyCode' => $oemPropertyCode,
            'updated' => $oemUpdated,
            'skippedMissingCross' => $oemSkippedMissingCross,
            'skippedUnchanged' => $oemSkippedUnchanged,
            'errors' => $oemErrors,
        ],
        'category' => [
            'enabled' => $syncCategoryProperty,
            'propertyCode' => $categoryPropertyCode,
            'updated' => $categoryUpdated,
            'skippedEmpty' => $categorySkippedEmpty,
            'skippedUnchanged' => $categorySkippedUnchanged,
            'errors' => $categoryErrors,
        ],
    ];
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    $result = brakes_1c_catalog_parse_run([
        'iblockId' => 1,
        'reactivateSections' => true,
        'reactivateElements' => false,
        'logPrefix' => 'manual',
    ]);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}
