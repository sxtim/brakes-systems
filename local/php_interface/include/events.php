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
                'reactivate' => true,
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
            // Rests file is typically the last step in catalog exchange; safe point to run parser if mode=complete is absent.
            brakes_1c_parse_schedule([
                'iblockId' => 1,
                'reactivate' => true,
                'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
                'logPrefix' => 'OnSuccessCatalogImport1C:rests',
            ], [
                'source' => 'OnSuccessCatalogImport1C:rests',
                'file' => $absFileName,
            ], true);
            return;
        }
    }
);
