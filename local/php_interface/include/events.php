<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Type\DateTime;
use Rosinkas\Entity\RequestCoinsTable;

EventManager::getInstance()->addEventHandler(
    'catalog',
    'OnSuccessCatalogImport1C',
    function (array $params = [], string $absFileName = ''): void {
        if (!function_exists('brakes_1c_parse_schedule')) {
            return;
        }

        if ($absFileName === '') {
            return;
        }

        $baseName = basename($absFileName);
        if (!preg_match('/^import___.+\\.xml$/i', $baseName)) {
            return;
        }

        brakes_1c_parse_schedule([
            'iblockId' => 1,
            'reactivate' => true,
            'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
            'logPrefix' => 'OnSuccessCatalogImport1C',
        ], [
            'source' => 'OnSuccessCatalogImport1C',
            'file' => $absFileName,
        ]);

        if (function_exists('brakes_1c_images_schedule')) {
            brakes_1c_images_schedule($absFileName, [
                'source' => 'OnSuccessCatalogImport1C',
            ]);
        }
    }
);
