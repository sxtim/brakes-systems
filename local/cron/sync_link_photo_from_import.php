<?php

/**
 * Sync LINK_PHOTO from an existing 1C import XML and migrate files into LINK_PHOTO_FILE.
 *
 * Usage:
 *   php local/cron/sync_link_photo_from_import.php --xml=upload/1c_catalog38/import___.xml
 *   php local/cron/sync_link_photo_from_import.php --xml=upload/1c_catalog38/import___.xml --force
 *   php local/cron/sync_link_photo_from_import.php --xml=upload/1c_catalog38/import___.xml --dry-run
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

if (!function_exists('brakes_1c_images_extract_links') || !function_exists('brakes_1c_images_sync_from_import')) {
    fwrite(STDERR, "1C image sync functions are not available\n");
    exit(1);
}

$options = getopt('', [
    'xml:',
    'iblock-id::',
    'force',
    'dry-run',
]);

$xmlPath = (string)($options['xml'] ?? '');
$iblockId = isset($options['iblock-id']) ? (int)$options['iblock-id'] : 1;
$force = array_key_exists('force', $options);
$dryRun = array_key_exists('dry-run', $options);

if ($xmlPath === '') {
    fwrite(STDERR, "Usage: --xml=upload/1c_catalog38/import___.xml [--iblock-id=1] [--force] [--dry-run]\n");
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

$linksByXmlId = brakes_1c_images_extract_links($xmlPath);
if ($linksByXmlId === []) {
    fwrite(STDERR, "No photo links found in XML\n");
    exit(1);
}

$elementMap = [];
$chunks = array_chunk(array_keys($linksByXmlId), 500);
foreach ($chunks as $chunk) {
    $res = \CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=XML_ID' => $chunk],
        false,
        false,
        ['ID', 'XML_ID']
    );
    while ($row = $res->Fetch()) {
        $elementMap[(string)$row['XML_ID']] = (int)$row['ID'];
    }
}

$matched = 0;
foreach (array_keys($linksByXmlId) as $xmlId) {
    if (!empty($elementMap[$xmlId])) {
        $matched++;
    }
}

echo "xml={$xmlPath}\n";
echo "found=" . count($linksByXmlId) . "\n";
echo "matched={$matched}\n";

if ($dryRun) {
    echo "mode=dry-run\n";
    exit(0);
}

$result = brakes_1c_images_sync_from_import($xmlPath, [
    'iblockId' => $iblockId,
    'force' => $force,
]);

echo "updated=" . (int)($result['updated'] ?? 0) . "\n";
echo "migrated=" . (int)($result['migrated'] ?? 0) . "\n";
echo "skipped=" . (int)($result['skipped'] ?? 0) . "\n";
echo "mode=apply\n";
echo "done\n";
