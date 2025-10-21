<?php

/**
 * Cron entry point for migrating catalog images from string links to Bitrix file storage.
 *
 * Usage (CLI):
 *   php local/cron/image_migrator.php --start-id=0 --chunk=200 --limit=0
 *   php local/cron/image_migrator.php --force --delete-source
 *   php local/cron/image_migrator.php --id=1234
 */

define('NO_KEEP_STATISTIC', true);
define('NOT_CHECK_PERMISSIONS', true);

if (!isset($_SERVER['DOCUMENT_ROOT']) || $_SERVER['DOCUMENT_ROOT'] === '') {
    $_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/../..');
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use App\Brakes\Helper\ImageMigrator;
use Bitrix\Main\Loader;

if (!Loader::includeModule('iblock')) {
    fwrite(STDERR, "iblock module is not available\n");
    exit(1);
}

$options = getopt('', [
    'id::',
    'start-id::',
    'chunk::',
    'limit::',
    'force',
    'dry-run',
    'delete-source',
]);

$targetId = isset($options['id']) ? (int)$options['id'] : null;
$startId = isset($options['start-id']) ? (int)$options['start-id'] : 0;
$chunk = isset($options['chunk']) ? (int)$options['chunk'] : 200;
$limit = isset($options['limit']) ? (int)$options['limit'] : 0;
$force = array_key_exists('force', $options);
$dryRun = array_key_exists('dry-run', $options);
$deleteSource = array_key_exists('delete-source', $options);

if ($targetId !== null) {
    if ($targetId <= 0) {
        fwrite(STDERR, "Invalid element id provided\n");
        exit(1);
    }

    $result = ImageMigrator::migrateElement($targetId, [
        'force' => $force,
        'dryRun' => $dryRun,
        'deleteSource' => $deleteSource,
    ]);

    $message = sprintf(
        "[%d] status=%s processed=%d",
        $result['elementId'],
        $result['status'],
        $result['processed']
    );
    if (!empty($result['errors'])) {
        $message .= ' errors=' . implode('; ', $result['errors']);
    }

    echo $message . PHP_EOL;
    exit(0);
}

$summary = ImageMigrator::migrateAll([
    'startId' => $startId,
    'chunk' => $chunk,
    'limit' => $limit,
    'force' => $force,
    'dryRun' => $dryRun,
    'deleteSource' => $deleteSource,
]);

$output = [
    'processed' => $summary['processed'],
    'migrated' => $summary['migrated'],
    'skipped' => $summary['skipped'],
    'mode' => $summary['dryRun'] ? 'dry-run' : 'apply',
];

foreach ($output as $key => $value) {
    echo $key . '=' . $value . PHP_EOL;
}

if (!empty($summary['errors'])) {
    echo "errors=" . implode('; ', $summary['errors']) . PHP_EOL;
}

echo "done" . PHP_EOL;

