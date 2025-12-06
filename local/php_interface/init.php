<?php

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

