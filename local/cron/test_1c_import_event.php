<?php

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $_SERVER['DOCUMENT_ROOT'] = (string)realpath(__DIR__ . '/../../');
}

if (!defined('B_PROLOG_INCLUDED')) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
}

$opts = [];
if (PHP_SAPI === 'cli') {
    $opts = getopt('', ['file:', 'event:']);
}

$file = $opts['file'] ?? '';
if ($file === '' && PHP_SAPI === 'cli' && !empty($argv[1])) {
    $file = (string)$argv[1];
}

$eventName = $opts['event'] ?? 'OnSuccessCatalogImport1C';

if ($file === '') {
    $message = "Usage:\n"
        . "  php local/cron/test_1c_import_event.php --file=/full/path/import___.xml\n"
        . "  php local/cron/test_1c_import_event.php --file=/full/path/import___.xml --event=OnCompleteCatalogImport1C\n";
    if (PHP_SAPI !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo $message;
    exit(1);
}

if ($file[0] !== '/') {
    $file = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . ltrim($file, '/');
}

$params = [
    'IBLOCK_TYPE' => 'catalog',
    'SITE_LIST' => [defined('SITE_ID') ? SITE_ID : 's1'],
    'INTERVAL' => 0,
];

foreach (GetModuleEvents('catalog', $eventName, true) as $arEvent) {
    ExecuteModuleEventEx($arEvent, [$params, $file]);
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}
echo "OK: fired {$eventName} for {$file}\n";
