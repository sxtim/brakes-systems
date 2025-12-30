<?php

use Bitrix\Iblock\SectionElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

/**
 * Cleanup legacy duplicate sections created before deterministic XML_ID scheme.
 *
 * Strategy:
 * - consider sections with CODE ending in "__dup<ID>" (or "__dup<ID>_<n>") as duplicates;
 * - find canonical section by base CODE (suffix stripped);
 * - move element bindings from duplicate to canonical;
 * - move unique child sections to canonical;
 * - delete duplicate sections if they have no children and no element bindings.
 *
 * This is intentionally conservative: it never renames/deletes sections with BRKS:* XML_ID.
 */
function brakes_iblock_section_cleanup_run(array $options = []): array
{
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = realpath(__DIR__ . '/../../');
        if ($docRoot === false || $docRoot === '') {
            $docRoot = dirname(__DIR__, 2);
        }
        $_SERVER['DOCUMENT_ROOT'] = (string)$docRoot;
    }

    $iblockId = (int)($options['iblockId'] ?? 1);
    $apply = (bool)($options['apply'] ?? false);
    $logPath = (string)($options['logPath'] ?? ($_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log'));
    $logPrefix = (string)($options['logPrefix'] ?? 'section_cleanup');
    $limitSections = max(0, (int)($options['limitSections'] ?? 0)); // 0 = no limit

    if (!defined('B_PROLOG_INCLUDED')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    Loader::includeModule('iblock');

    $lockDir = dirname($logPath);
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0775, true);
    }
    $lockPath = (string)($options['lockPath'] ?? ($lockDir . '/section_cleanup.lock'));
    $lockHandle = @fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        throw new RuntimeException('section_cleanup: failed to open lock file ' . $lockPath);
    }
    if (!flock($lockHandle, LOCK_EX)) {
        throw new RuntimeException('section_cleanup: failed to acquire lock ' . $lockPath);
    }

    $isDupCode = static function (string $code): bool {
        return (bool)preg_match('/__dup\\d+(?:_\\d+)?$/', $code);
    };
    $baseCodeFromDup = static function (string $dupCode): string {
        return (string)preg_replace('/__dup\\d+(?:_\\d+)?$/', '', $dupCode);
    };

    $countElements = static function (int $sectionId): int {
        if (class_exists(SectionElementTable::class) && method_exists(SectionElementTable::class, 'getCount')) {
            return (int)SectionElementTable::getCount(['=IBLOCK_SECTION_ID' => $sectionId]);
        }

        global $DB;
        $row = $DB->Query(
            'SELECT COUNT(*) CNT FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID=' . (int)$sectionId,
            false,
            'section_cleanup count elements'
        )->Fetch();
        return (int)($row['CNT'] ?? 0);
    };

    $countChildren = static function (int $iblockId, int $sectionId): int {
        if (class_exists(SectionTable::class) && method_exists(SectionTable::class, 'getCount')) {
            return (int)SectionTable::getCount([
                '=IBLOCK_ID' => $iblockId,
                '=IBLOCK_SECTION_ID' => $sectionId,
            ]);
        }

        global $DB;
        $row = $DB->Query(
            'SELECT COUNT(*) CNT FROM b_iblock_section WHERE IBLOCK_ID=' . (int)$iblockId . ' AND IBLOCK_SECTION_ID=' . (int)$sectionId,
            false,
            'section_cleanup count children'
        )->Fetch();
        return (int)($row['CNT'] ?? 0);
    };

    $sections = [];
    $codeToId = [];
    $dupIds = [];

    $rs = SectionTable::getList([
        'filter' => [
            '=IBLOCK_ID' => $iblockId,
        ],
        'select' => [
            'ID',
            'CODE',
            'XML_ID',
            'NAME',
            'IBLOCK_SECTION_ID',
            'DEPTH_LEVEL',
        ],
    ]);
    while ($row = $rs->fetch()) {
        $id = (int)$row['ID'];
        $code = trim((string)($row['CODE'] ?? ''));
        $xmlId = trim((string)($row['XML_ID'] ?? ''));
        $sections[$id] = [
            'ID' => $id,
            'CODE' => $code,
            'XML_ID' => $xmlId,
            'NAME' => (string)($row['NAME'] ?? ''),
            'PARENT_ID' => (int)($row['IBLOCK_SECTION_ID'] ?? 0),
            'DEPTH_LEVEL' => (int)($row['DEPTH_LEVEL'] ?? 0),
        ];
        if ($code !== '') {
            $codeToId[$code] = $id;
        }
        if ($code !== '' && $isDupCode($code)) {
            $dupIds[] = $id;
        }
    }

    usort(
        $dupIds,
        static function (int $a, int $b) use ($sections): int {
            $da = (int)($sections[$a]['DEPTH_LEVEL'] ?? 0);
            $db = (int)($sections[$b]['DEPTH_LEVEL'] ?? 0);
            if ($da !== $db) {
                return $db <=> $da; // deeper first
            }
            return $b <=> $a;
        }
    );

    $bs = new CIBlockSection();
    $be = new CIBlockElement();
    $warnings = [];
    $warningOnce = [];
    $logLines = [];

    $sectionsConsidered = 0;
    $elementsMoved = 0;
    $childrenMoved = 0;
    $sectionsDeleted = 0;
    $deleteSkippedHasChildren = 0;
    $deleteSkippedHasElements = 0;
    $deleteSkippedNoCanon = 0;
    $skippedBrksXml = 0;
    $errors = 0;

    foreach ($dupIds as $dupId) {
        if ($limitSections > 0 && $sectionsConsidered >= $limitSections) {
            break;
        }

        $sectionsConsidered++;
        $dup = $sections[$dupId] ?? null;
        if (!$dup) {
            continue;
        }

        $dupCode = (string)$dup['CODE'];
        $dupXml = (string)$dup['XML_ID'];
        if ($dupXml !== '' && str_starts_with($dupXml, 'BRKS:')) {
            $skippedBrksXml++;
            $msg = 'skip_brks_xml id=' . $dupId . ' code=' . $dupCode . ' xml_id=' . $dupXml;
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
            continue;
        }

        $baseCode = $baseCodeFromDup($dupCode);
        if ($baseCode === '' || $baseCode === $dupCode) {
            continue;
        }

        $canonId = (int)($codeToId[$baseCode] ?? 0);
        if ($canonId <= 0) {
            $deleteSkippedNoCanon++;
            $msg = 'no_canon_for_dup id=' . $dupId . ' code=' . $dupCode . ' base=' . $baseCode;
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
            continue;
        }

        $canonXml = (string)($sections[$canonId]['XML_ID'] ?? '');
        if ($canonXml === '' || !str_starts_with($canonXml, 'BRKS:')) {
            $msg = 'canon_not_brks id=' . $canonId . ' code=' . $baseCode . ' xml_id=' . $canonXml . ' dup=' . $dupId;
            if (!isset($warningOnce[$msg])) {
                $warningOnce[$msg] = true;
                $warnings[] = $msg;
            }
        }

        $logLines[] = 'dup id=' . $dupId . ' code=' . $dupCode . ' -> canon=' . $canonId . ' code=' . $baseCode;

        // Move element bindings.
        $elementIds = [];
        if (class_exists(SectionElementTable::class) && method_exists(SectionElementTable::class, 'getList')) {
            $rel = SectionElementTable::getList([
                'filter' => ['=IBLOCK_SECTION_ID' => $dupId],
                'select' => ['IBLOCK_ELEMENT_ID'],
            ]);
            while ($r = $rel->fetch()) {
                $elementIds[] = (int)$r['IBLOCK_ELEMENT_ID'];
            }
        } else {
            global $DB;
            $rsEl = $DB->Query(
                'SELECT IBLOCK_ELEMENT_ID FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID=' . (int)$dupId,
                false,
                'section_cleanup fetch element bindings'
            );
            while ($r = $rsEl->Fetch()) {
                $elementIds[] = (int)$r['IBLOCK_ELEMENT_ID'];
            }
        }
        $elementIds = array_values(array_unique(array_filter($elementIds)));

        foreach ($elementIds as $elementId) {
            $groups = [];
            $dbGroups = CIBlockElement::GetElementGroups($elementId, true, ['ID']);
            while ($g = $dbGroups->Fetch()) {
                $groups[] = (int)$g['ID'];
            }

            $groups = array_values(array_unique(array_filter($groups)));
            $newGroups = [];
            $changed = false;
            foreach ($groups as $sid) {
                if ($sid === $dupId) {
                    $sid = $canonId;
                    $changed = true;
                }
                $newGroups[$sid] = true;
            }
            $newGroups = array_keys($newGroups);
            sort($newGroups);

            if (!$changed) {
                continue;
            }

            $mainSectionId = null;
            $row = $be->GetByID($elementId)->Fetch();
            if (is_array($row)) {
                $mainSectionId = (int)($row['IBLOCK_SECTION_ID'] ?? 0);
                if ($mainSectionId === $dupId) {
                    $mainSectionId = $canonId;
                }
                if ($mainSectionId <= 0) {
                    $mainSectionId = null;
                }
            }

            $logLines[] = ($apply ? 'move_element' : 'move_element_dry')
                . ' element=' . $elementId
                . ' from=' . $dupId
                . ' to=' . $canonId
                . ' main=' . (string)($mainSectionId ?? 'null');

            if (!$apply) {
                $elementsMoved++;
                continue;
            }

            $ok = CIBlockElement::SetElementSection($elementId, $newGroups, $mainSectionId);
            if (!$ok) {
                $errors++;
                $logLines[] = 'error element_rebind_failed element=' . $elementId . ' from=' . $dupId . ' to=' . $canonId;
                continue;
            }
            $elementsMoved++;

            if (class_exists(\Bitrix\Iblock\PropertyIndex\Manager::class)) {
                \Bitrix\Iblock\PropertyIndex\Manager::updateElementIndex($iblockId, $elementId);
            }
        }

        // Move unique child sections to canonical (only if they are not dup sections).
        $children = [];
        $rsChild = SectionTable::getList([
            'filter' => [
                '=IBLOCK_ID' => $iblockId,
                '=IBLOCK_SECTION_ID' => $dupId,
            ],
            'select' => ['ID', 'CODE', 'XML_ID', 'NAME'],
        ]);
        while ($c = $rsChild->fetch()) {
            $children[] = [
                'ID' => (int)$c['ID'],
                'CODE' => trim((string)($c['CODE'] ?? '')),
                'XML_ID' => trim((string)($c['XML_ID'] ?? '')),
                'NAME' => (string)($c['NAME'] ?? ''),
            ];
        }

        foreach ($children as $child) {
            $childId = (int)$child['ID'];
            $childCode = (string)$child['CODE'];
            $childXml = (string)$child['XML_ID'];
            if ($childXml !== '' && str_starts_with($childXml, 'BRKS:')) {
                // Unexpected: BRKS section under dup branch; do not touch.
                $msg = 'brks_child_under_dup dup=' . $dupId . ' child=' . $childId . ' code=' . $childCode . ' xml_id=' . $childXml;
                if (!isset($warningOnce[$msg])) {
                    $warningOnce[$msg] = true;
                    $warnings[] = $msg;
                }
                continue;
            }

            if ($childCode !== '' && $isDupCode($childCode)) {
                // It will be processed separately (depth-first).
                continue;
            }

            $logLines[] = ($apply ? 'move_child' : 'move_child_dry')
                . ' child=' . $childId
                . ' from_parent=' . $dupId
                . ' to_parent=' . $canonId
                . ' code=' . $childCode;

            if (!$apply) {
                $childrenMoved++;
                continue;
            }

            $ok = $bs->Update($childId, ['IBLOCK_SECTION_ID' => $canonId], false);
            if (!$ok) {
                $errors++;
                $logLines[] = 'error child_move_failed child=' . $childId . ' error=' . (string)$bs->LAST_ERROR;
                continue;
            }
            $childrenMoved++;
        }

        // Delete duplicate section if now safe.
        $currentChildren = $countChildren($iblockId, $dupId);
        $currentElements = $countElements($dupId);

        if ($currentChildren > 0) {
            $deleteSkippedHasChildren++;
            continue;
        }
        if ($currentElements > 0) {
            $deleteSkippedHasElements++;
            continue;
        }

        $logLines[] = ($apply ? 'delete' : 'delete_dry') . ' id=' . $dupId . ' code=' . $dupCode;
        if (!$apply) {
            $sectionsDeleted++;
            continue;
        }

        if (!CIBlockSection::Delete($dupId)) {
            $errors++;
            $logLines[] = 'error section_delete_failed id=' . $dupId;
            continue;
        }
        $sectionsDeleted++;
    }

    if ($apply && ($childrenMoved > 0 || $sectionsDeleted > 0)) {
        CIBlockSection::ReSort($iblockId);
    }

    $logLine = date('c')
        . ' src=' . $logPrefix
        . ' iblock=' . $iblockId
        . ' apply=' . ($apply ? '1' : '0')
        . ' dup_total=' . count($dupIds)
        . ' considered=' . $sectionsConsidered
        . ' elements_moved=' . $elementsMoved
        . ' children_moved=' . $childrenMoved
        . ' deleted=' . $sectionsDeleted
        . ' skipped_no_canon=' . $deleteSkippedNoCanon
        . ' skipped_has_children=' . $deleteSkippedHasChildren
        . ' skipped_has_elements=' . $deleteSkippedHasElements
        . ' skipped_brks_xml=' . $skippedBrksXml
        . ' warnings=' . count($warnings)
        . ' errors=' . $errors
        . PHP_EOL
        . implode(PHP_EOL, $logLines)
        . PHP_EOL;
    if ($warnings) {
        $logLine .= implode(PHP_EOL, array_map(static fn ($w) => 'warn ' . $w, $warnings)) . PHP_EOL;
    }

    $logDir = dirname($logPath);
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    if (!file_exists($logPath)) {
        @touch($logPath);
    }
    @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

    @flock($lockHandle, LOCK_UN);
    @fclose($lockHandle);

    return [
        'iblockId' => $iblockId,
        'apply' => $apply,
        'dupTotal' => count($dupIds),
        'considered' => $sectionsConsidered,
        'elementsMoved' => $elementsMoved,
        'childrenMoved' => $childrenMoved,
        'deleted' => $sectionsDeleted,
        'skippedNoCanon' => $deleteSkippedNoCanon,
        'skippedHasChildren' => $deleteSkippedHasChildren,
        'skippedHasElements' => $deleteSkippedHasElements,
        'skippedBrksXml' => $skippedBrksXml,
        'warnings' => count($warnings),
        'errors' => $errors,
        'logPath' => $logPath,
        'documentRoot' => (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
    ];
}

if (PHP_SAPI === 'cli' || realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = realpath(__DIR__ . '/../../');
        if ($docRoot === false || $docRoot === '') {
            $docRoot = dirname(__DIR__, 2);
        }
        $_SERVER['DOCUMENT_ROOT'] = (string)$docRoot;
    }

    $opts = getopt('', ['iblock:', 'apply', 'dry-run', 'log:', 'limit-sections:']);
    $iblockId = isset($opts['iblock']) ? (int)$opts['iblock'] : 1;
    $apply = isset($opts['apply']) && !isset($opts['dry-run']);
    $logPath = isset($opts['log']) ? (string)$opts['log'] : '';
    $limitSections = isset($opts['limit-sections']) ? (int)$opts['limit-sections'] : 0;

    $runOptions = [
        'iblockId' => $iblockId,
        'apply' => $apply,
        'logPrefix' => 'cli_cleanup',
        'limitSections' => $limitSections,
    ];
    if ($logPath !== '') {
        $runOptions['logPath'] = $logPath;
    }

    $result = brakes_iblock_section_cleanup_run($runOptions);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}

