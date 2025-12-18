<?php

// Защита от массовой деактивации при 1С-обмене (mode=deactivate&timestamp=...),
// т.к. у нас есть собственное дерево разделов "категория → марка → модель → кузов",
// которого нет в 1С, и оно может быть случайно "погашено" стандартным механизмом.
if (
    !empty($_SERVER['SCRIPT_NAME'])
    && substr((string)$_SERVER['SCRIPT_NAME'], -strlen('/bitrix/admin/1c_exchange.php')) === '/bitrix/admin/1c_exchange.php'
    && (($_REQUEST['type'] ?? '') === 'catalog')
    && (($_REQUEST['mode'] ?? '') === 'deactivate')
) {
    $logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/cron/1c_exchange_guard.log';
    $logLine = date('c')
        . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-')
        . ' qs=' . ($_SERVER['QUERY_STRING'] ?? '-')
        . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
        . PHP_EOL;
    @file_put_contents($logPath, $logLine, FILE_APPEND);

    header('Content-Type: text/plain; charset=windows-1251');
    echo "success\n";
    exit;
}

if (!\Bitrix\Main\Loader::includeModule('pull'))
{
    \Bitrix\Main\Config\Option::set('main', 'use_pull', 'N');
}

require_once __DIR__ . '/include/func.php';
require_once __DIR__ . '/include/events.php';

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'App\Brakes\Helper\Storage' => '/local/app/Brakes/Helper/Storage.php',
    'App\Brakes\Helper\Highload' => '/local/app/Brakes/Helper/Highload.php',
    'App\Brakes\Helper\Favorites' => '/local/app/Brakes/Helper/Favorites.php',
    'App\Brakes\Helper\Image' => '/local/app/Brakes/Helper/Image.php',
    'App\Brakes\Helper\ImageMigrator' => '/local/app/Brakes/Helper/ImageMigrator.php',
    'App\Brakes\Helper\FavoritesManager' => '/local/app/Brakes/Helper/FavoritesManager.php',
    'App\Brakes\Auth\Sms' => '/local/app/Brakes/Auth/Sms.php',
    'App\Brakes\Pricing\Configurator' => '/local/app/Brakes/Pricing/Configurator.php',
]);

AddEventHandler('main', 'OnBeforeProlog', static function (): void {
    \App\Brakes\Helper\FavoritesManager::handleProlog();
});

// После завершения 1С-импорта пересобираем привязки и активируем используемые ветки разделов.
AddEventHandler('catalog', 'OnCompleteCatalogImport1C', static function (array $params = [], string $absFileName = ''): void {
    if (!\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }

    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/cron/1c_catalog_parse.php';
    if (function_exists('brakes_1c_catalog_parse_run')) {
        brakes_1c_catalog_parse_run([
            'iblockId' => 1,
            'reactivate' => true,
            'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
            'logPrefix' => 'OnCompleteCatalogImport1C',
        ]);
    }
});

/*
AddEventHandler('iblock', 'OnAfterIBlockElementAdd', static function (array &$fields): void {
    if ((int)($fields['IBLOCK_ID'] ?? 0) !== \App\Brakes\Helper\ImageMigrator::IBLOCK_ID) {
        return;
    }

    $elementId = (int)($fields['ID'] ?? 0);
    if ($elementId <= 0) {
        return;
    }

    \App\Brakes\Helper\ImageMigrator::migrateElement($elementId);
});

AddEventHandler('iblock', 'OnAfterIBlockElementUpdate', static function (array &$fields): void {
    if ((int)($fields['IBLOCK_ID'] ?? 0) !== \App\Brakes\Helper\ImageMigrator::IBLOCK_ID) {
        return;
    }

    if (!empty($fields['RESULT']) && $fields['RESULT'] === false) {
        return;
    }

    $elementId = (int)($fields['ID'] ?? 0);
    if ($elementId <= 0) {
        return;
    }

    \App\Brakes\Helper\ImageMigrator::migrateElement($elementId);
});
*/
