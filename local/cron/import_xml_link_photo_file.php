<?php

/**
 * Backfill LINK_PHOTO_FILE from an existing 1C import XML without running a full exchange.
 *
 * Usage:
 *   php local/cron/import_xml_link_photo_file.php --xml=/upload/1c_catalog42/import___.xml --xml-id=<PRODUCT_XML_ID>
 *   php local/cron/import_xml_link_photo_file.php --xml=/upload/1c_catalog42/import___.xml --element-id=1234 --force
 *   php local/cron/import_xml_link_photo_file.php --xml=/upload/1c_catalog42/import___.xml --all --force
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
    $_SERVER['DOCUMENT_ROOT'] = (string)realpath(__DIR__ . '/../..');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module is not available\n");
    exit(1);
}

$options = getopt('', [
    'xml:',
    'xml-id::',
    'element-id::',
    'iblock-id::',
    'all',
    'force',
    'dry-run',
]);

$xmlPath = (string)($options['xml'] ?? '');
$xmlId = trim((string)($options['xml-id'] ?? ''));
$elementId = isset($options['element-id']) ? (int)$options['element-id'] : 0;
$iblockId = isset($options['iblock-id']) ? (int)$options['iblock-id'] : 1;
$all = array_key_exists('all', $options);
$force = array_key_exists('force', $options);
$dryRun = array_key_exists('dry-run', $options);

if ($xmlPath === '') {
    fwrite(STDERR, "Usage: --xml=/upload/1c_catalog42/import___.xml [--xml-id=<XML_ID>|--element-id=<ID>|--all] [--force] [--dry-run]\n");
    exit(1);
}

if ($xmlPath[0] !== '/') {
    $xmlPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($xmlPath, '/');
}

if (!is_file($xmlPath)) {
    $siteRelativePath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($xmlPath, '/');
    if (is_file($siteRelativePath)) {
        $xmlPath = $siteRelativePath;
    }
}

if (!is_file($xmlPath)) {
    fwrite(STDERR, "XML file not found: {$xmlPath}\n");
    exit(1);
}

if ($iblockId <= 0) {
    fwrite(STDERR, "Invalid iblock id\n");
    exit(1);
}

if ($all && ($elementId > 0 || $xmlId !== '')) {
    fwrite(STDERR, "Use either --all or --xml-id/--element-id\n");
    exit(1);
}

if (!$all && $elementId <= 0 && $xmlId === '') {
    fwrite(STDERR, "Provide --xml-id, --element-id or --all\n");
    exit(1);
}

$walkProducts = static function (string $fileName, callable $callback): void {
    $reader = new \XMLReader();
    if (!$reader->open($fileName)) {
        throw new \RuntimeException('Failed to open XML');
    }

    while ($reader->read()) {
        if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'Товар') {
            continue;
        }

        $xml = $reader->readOuterXML();
        if ($xml === '') {
            continue;
        }

        try {
            $node = new \SimpleXMLElement($xml);
        } catch (\Throwable $exception) {
            continue;
        }

        $currentXmlId = trim((string)($node->Ид ?? ''));
        if ($currentXmlId === '') {
            continue;
        }

        $pictures = [];
        foreach ($node->Картинка as $pictureNode) {
            $value = trim((string)$pictureNode);
            if ($value !== '') {
                $pictures[] = $value;
            }
        }

        $shouldContinue = $callback($currentXmlId, array_values(array_unique($pictures)));
        if ($shouldContinue === false) {
            break;
        }
    }

    $reader->close();
};

$normalizeSourcePath = static function (string $baseDir, string $path): ?string {
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '') {
        return null;
    }

    $relative = ltrim($path, '/');
    $candidate = rtrim($baseDir, '/') . '/' . $relative;
    if (is_file($candidate)) {
        return $candidate;
    }

    $candidate = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $relative;
    if (is_file($candidate)) {
        return $candidate;
    }

    return null;
};

$baseDir = dirname($xmlPath);
$loadCurrentTargetRows = static function (int $currentIblockId, int $currentElementId): array {
    $currentRows = [];
    $propRes = \CIBlockElement::GetProperty(
        $currentIblockId,
        $currentElementId,
        ['sort' => 'asc', 'id' => 'asc'],
        ['CODE' => 'LINK_PHOTO_FILE']
    );
    while ($prop = $propRes->Fetch()) {
        $currentRows[] = [
            'fileId' => (int)($prop['VALUE'] ?? 0),
            'valueId' => (int)($prop['PROPERTY_VALUE_ID'] ?? 0),
        ];
    }

    return $currentRows;
};

$applyPictures = static function (int $currentElementId, string $currentXmlId, array $picturePaths) use ($iblockId, $force, $dryRun, $baseDir, $normalizeSourcePath, $loadCurrentTargetRows): array {
    if ($picturePaths === []) {
        return [
            'status' => 'no_pictures',
            'elementId' => $currentElementId,
            'xmlId' => $currentXmlId,
        ];
    }

    $currentTargetRows = $loadCurrentTargetRows($iblockId, $currentElementId);
    $currentFileIds = array_filter(array_map(
        static fn(array $row): int => (int)($row['fileId'] ?? 0),
        $currentTargetRows
    ));
    if (!$force && $currentFileIds !== []) {
        return [
            'status' => 'skip_filled',
            'elementId' => $currentElementId,
            'xmlId' => $currentXmlId,
        ];
    }

    $fileValues = [];
    $resolvedFiles = [];
    $errors = [];

    foreach ($picturePaths as $index => $picturePath) {
        $absolutePath = $normalizeSourcePath($baseDir, $picturePath);
        if ($absolutePath === null) {
            $errors[] = "file not found: {$picturePath}";
            continue;
        }

        $fileArray = \CFile::MakeFileArray($absolutePath);
        if (!is_array($fileArray)) {
            $errors[] = "failed to prepare file: {$absolutePath}";
            continue;
        }

        $fileArray['MODULE_ID'] = 'iblock';
        $fileValues['n' . $index] = [
            'VALUE' => $fileArray,
            'DESCRIPTION' => '',
        ];
        $resolvedFiles[] = $absolutePath;
    }

    if ($fileValues === []) {
        return [
            'status' => 'error',
            'elementId' => $currentElementId,
            'xmlId' => $currentXmlId,
            'errors' => $errors,
        ];
    }

    if ($dryRun) {
        return [
            'status' => 'dry_run',
            'elementId' => $currentElementId,
            'xmlId' => $currentXmlId,
            'files' => $resolvedFiles,
            'errors' => $errors,
            'count' => count($fileValues),
        ];
    }

    $propertyValue = [];
    foreach ($currentTargetRows as $row) {
        $valueId = (int)($row['valueId'] ?? 0);
        if ($valueId <= 0) {
            continue;
        }

        $propertyValue[$valueId] = [
            'VALUE' => [
                'del' => 'Y',
            ],
        ];
    }
    foreach ($fileValues as $key => $value) {
        $propertyValue[$key] = $value;
    }

    \CIBlockElement::SetPropertyValueCode($currentElementId, 'LINK_PHOTO_FILE', $propertyValue);

    return [
        'status' => 'updated',
        'elementId' => $currentElementId,
        'xmlId' => $currentXmlId,
        'files' => $resolvedFiles,
        'errors' => $errors,
        'count' => count($fileValues),
    ];
};

if ($all) {
    $elementMap = [];
    $elementRes = \CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], false, false, ['ID', 'XML_ID']);
    while ($element = $elementRes->Fetch()) {
        $currentXmlId = trim((string)($element['XML_ID'] ?? ''));
        if ($currentXmlId !== '') {
            $elementMap[$currentXmlId] = (int)$element['ID'];
        }
    }

    $stats = [
        'updated' => 0,
        'dry_run' => 0,
        'skip_filled' => 0,
        'no_pictures' => 0,
        'not_found' => 0,
        'error' => 0,
    ];

    $walkProducts($xmlPath, static function (string $currentXmlId, array $picturePaths) use (&$stats, $elementMap, $applyPictures): void {
        $elementId = $elementMap[$currentXmlId] ?? 0;
        if ($elementId <= 0) {
            $stats['not_found']++;
            echo "status=not_found xml_id={$currentXmlId}\n";
            return;
        }

        $result = $applyPictures($elementId, $currentXmlId, $picturePaths);
        $status = (string)$result['status'];
        if (isset($stats[$status])) {
            $stats[$status]++;
        }

        echo "status={$status} element_id={$elementId} xml_id={$currentXmlId}";
        if (isset($result['count'])) {
            echo " pictures=" . (int)$result['count'];
        }
        echo "\n";

        foreach (($result['files'] ?? []) as $resolvedFile) {
            echo "source={$resolvedFile}\n";
        }

        foreach (($result['errors'] ?? []) as $error) {
            echo "warning={$error}\n";
        }
    });

    echo "summary_updated={$stats['updated']}\n";
    echo "summary_dry_run={$stats['dry_run']}\n";
    echo "summary_skip_filled={$stats['skip_filled']}\n";
    echo "summary_no_pictures={$stats['no_pictures']}\n";
    echo "summary_not_found={$stats['not_found']}\n";
    echo "summary_error={$stats['error']}\n";
    exit(0);
}

$elementFilter = ['IBLOCK_ID' => $iblockId];
if ($elementId > 0) {
    $elementFilter['ID'] = $elementId;
} else {
    $elementFilter['=XML_ID'] = $xmlId;
}

$elementRes = \CIBlockElement::GetList([], $elementFilter, false, false, ['ID', 'IBLOCK_ID', 'XML_ID', 'NAME']);
$element = $elementRes->Fetch();
if (!is_array($element)) {
    fwrite(STDERR, "Element not found\n");
    exit(1);
}

$elementId = (int)$element['ID'];
$xmlId = trim((string)$element['XML_ID']);

if ($xmlId === '') {
    fwrite(STDERR, "Element XML_ID is empty\n");
    exit(1);
}

$picturePaths = [];
$walkProducts($xmlPath, static function (string $currentXmlId, array $currentPictures) use (&$picturePaths, $xmlId): bool {
    if ($currentXmlId !== $xmlId) {
        return true;
    }

    $picturePaths = $currentPictures;
    return false;
});

if ($picturePaths === []) {
    fwrite(STDERR, "No <Картинка> nodes found for XML_ID={$xmlId}\n");
    exit(1);
}

$result = $applyPictures($elementId, $xmlId, $picturePaths);
echo "element_id={$elementId}\n";
echo "xml_id={$xmlId}\n";
if (isset($result['count'])) {
    echo "pictures=" . (int)$result['count'] . "\n";
}
foreach (($result['files'] ?? []) as $resolvedFile) {
    echo "source={$resolvedFile}\n";
}
foreach (($result['errors'] ?? []) as $error) {
    echo "warning={$error}\n";
}
echo "status=" . (string)$result['status'] . "\n";
