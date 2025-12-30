<?php

use Bitrix\Iblock\SectionElementTable;
use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;

function brakes_iblock_section_deduplicate_run(array $options = []): array
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
    $logPrefix = (string)($options['logPrefix'] ?? 'section_dedupe');

    if (!defined('B_PROLOG_INCLUDED')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    Loader::includeModule('iblock');

    $lockDir = dirname($logPath);
    if (!is_dir($lockDir)) {
        @mkdir($lockDir, 0775, true);
    }
    $lockPath = (string)($options['lockPath'] ?? ($lockDir . '/section_dedupe.lock'));
    $lockHandle = @fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        throw new RuntimeException('section_dedupe: failed to open lock file ' . $lockPath);
    }
    if (!flock($lockHandle, LOCK_EX)) {
        throw new RuntimeException('section_dedupe: failed to acquire lock ' . $lockPath);
    }

    $countElements = static function (int $sectionId): int {
        if (class_exists(SectionElementTable::class) && method_exists(SectionElementTable::class, 'getCount')) {
            return (int)SectionElementTable::getCount(['=IBLOCK_SECTION_ID' => $sectionId]);
        }

        global $DB;
        $row = $DB->Query(
            'SELECT COUNT(*) CNT FROM b_iblock_section_element WHERE IBLOCK_SECTION_ID=' . (int)$sectionId,
            false,
            'section_dedupe count elements'
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
            'section_dedupe count children'
        )->Fetch();
        return (int)($row['CNT'] ?? 0);
    };

    $sections = [];
    $byCode = [];
    $existingCodes = [];
    $rs = SectionTable::getList([
        'filter' => [
            '=IBLOCK_ID' => $iblockId,
        ],
        'select' => [
            'ID',
            'IBLOCK_SECTION_ID',
            'CODE',
            'XML_ID',
            'NAME',
        ],
    ]);
    while ($row = $rs->fetch()) {
        $id = (int)$row['ID'];
        $parentId = (int)$row['IBLOCK_SECTION_ID'];
        $code = trim((string)($row['CODE'] ?? ''));
        $xmlId = trim((string)($row['XML_ID'] ?? ''));
        $name = (string)($row['NAME'] ?? '');

        $sections[$id] = [
            'ID' => $id,
            'PARENT_ID' => $parentId,
            'CODE' => $code,
            'XML_ID' => $xmlId,
            'NAME' => $name,
        ];
        if ($code !== '') {
            $byCode[$code][] = $id;
            $existingCodes[$code] = true;
        }
    }

    $makeUniqueCode = static function (string $baseCode, int $sectionId) use (&$existingCodes): string {
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
        while (isset($existingCodes[$unique])) {
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

    $groupsFound = 0;
    $sectionsRenamed = 0;
    $errors = 0;
    $logLines = [];

    $bs = new CIBlockSection();

    foreach ($byCode as $code => $ids) {
        if (count($ids) <= 1) {
            continue;
        }

        $groupsFound++;

        $candidates = [];
        foreach ($ids as $id) {
            $xmlId = $sections[$id]['XML_ID'] ?? '';
            $hasBrksXml = (is_string($xmlId) && str_starts_with($xmlId, 'BRKS:')) ? 1 : 0;
            $children = $countChildren($iblockId, (int)$id);
            $elements = $countElements((int)$id);
            $score = ($hasBrksXml * 1000000) + ($children * 1000) + $elements;
            $candidates[] = [
                'ID' => (int)$id,
                'PARENT_ID' => (int)($sections[$id]['PARENT_ID'] ?? 0),
                'XML_ID' => (string)$xmlId,
                'SCORE' => (int)$score,
                'CHILDREN' => (int)$children,
                'ELEMENTS' => (int)$elements,
                'NAME' => (string)($sections[$id]['NAME'] ?? ''),
            ];
        }

        usort(
            $candidates,
            static function (array $a, array $b): int {
                if ($a['SCORE'] !== $b['SCORE']) {
                    return $b['SCORE'] <=> $a['SCORE'];
                }
                return $a['ID'] <=> $b['ID'];
            }
        );

        $canon = $candidates[0];
        $canonId = (int)$canon['ID'];

        $logLines[] = 'group code=' . $code
            . ' ids=' . implode(',', array_map(static fn ($c) => (string)$c['ID'], $candidates))
            . ' canon=' . $canonId;

        foreach ($candidates as $candidate) {
            $id = (int)$candidate['ID'];
            if ($id === $canonId) {
                continue;
            }

            $newCode = $makeUniqueCode($code, $id);
            $logLines[] = ($apply ? 'rename' : 'rename_dry')
                . ' id=' . $id
                . ' parent=' . (int)$candidate['PARENT_ID']
                . ' code=' . $code
                . ' new_code=' . $newCode
                . ' elements=' . (int)$candidate['ELEMENTS']
                . ' children=' . (int)$candidate['CHILDREN']
                . ' xml_id=' . (string)$candidate['XML_ID']
                . ' name=' . (string)$candidate['NAME'];

            if (!$apply) {
                continue;
            }

            $res = $bs->Update($id, ['CODE' => $newCode], false);
            if ($res) {
                $sectionsRenamed++;
                $existingCodes[$newCode] = true;
            } else {
                $errors++;
                $logLines[] = 'error section_update_failed id=' . $id . ' error=' . (string)$bs->LAST_ERROR;
            }
        }
    }

    $logLine = date('c')
        . ' src=' . $logPrefix
        . ' iblock=' . $iblockId
        . ' apply=' . ($apply ? '1' : '0')
        . ' groups_found=' . $groupsFound
        . ' sections_renamed=' . $sectionsRenamed
        . ' errors=' . $errors
        . PHP_EOL
        . implode(PHP_EOL, $logLines)
        . PHP_EOL;

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
        'groupsFound' => $groupsFound,
        'sectionsRenamed' => $sectionsRenamed,
        'errors' => $errors,
        'documentRoot' => (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
        'logPath' => $logPath,
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

    $opts = getopt('', ['iblock:', 'apply', 'dry-run', 'log:']);
    $iblockId = isset($opts['iblock']) ? (int)$opts['iblock'] : 1;
    $apply = isset($opts['apply']) && !isset($opts['dry-run']);
    $logPath = isset($opts['log']) ? (string)$opts['log'] : '';

    $runOptions = [
        'iblockId' => $iblockId,
        'apply' => $apply,
        'logPrefix' => 'cli',
    ];
    if ($logPath !== '') {
        $runOptions['logPath'] = $logPath;
    }

    $result = brakes_iblock_section_deduplicate_run($runOptions);
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
}
