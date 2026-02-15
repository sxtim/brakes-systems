<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Type\DateTime;
use Rosinkas\Entity\RequestCoinsTable;

EventManager::getInstance()->addEventHandler(
    'catalog',
    'OnSuccessCatalogImport1C',
    function ($params = null, $absFileName = ''): void {
        if (!function_exists('brakes_1c_parse_schedule')) {
            return;
        }

        $absFileName = (string)$absFileName;
        if ($absFileName === '') {
            return;
        }

        $baseName = basename($absFileName);
        if (preg_match('/^import___.+\\.xml$/i', $baseName)) {
            // Import XML can arrive in multiple parts; mark pending and run parser later (complete/rests/fallback).
            brakes_1c_parse_schedule([
                'iblockId' => 1,
                'reactivateSections' => true,
                'reactivateElements' => false,
                'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
                'logPrefix' => 'OnSuccessCatalogImport1C',
            ], [
                'source' => 'OnSuccessCatalogImport1C',
                'file' => $absFileName,
            ], false);

            if (function_exists('brakes_1c_images_schedule')) {
                brakes_1c_images_schedule($absFileName, [
                    'source' => 'OnSuccessCatalogImport1C',
                ]);
            }
            return;
        }

        if (preg_match('/^rests___.+\\.xml$/i', $baseName)) {
            if (class_exists(\Bitrix\Main\Config\Option::class)) {
                \Bitrix\Main\Config\Option::set('brakes', '1c_last_rests_ts', (string)time());
                \Bitrix\Main\Config\Option::set('brakes', '1c_last_rests_file', (string)$absFileName);
            }

            // Rests file is typically the last step in catalog exchange.
            // We DO NOT run parser here to avoid touching element TIMESTAMP_X before mode=deactivate.
            // The parser is executed on mode=complete; if complete is absent, fallback will run later.
            return;
        }
    }
);
