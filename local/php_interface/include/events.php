<?php

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\EventManager;
use Bitrix\Main\Diag\FileLogger;
use Bitrix\Main\Type\DateTime;
use Rosinkas\Entity\RequestCoinsTable;

EventManager::getInstance()->addEventHandler(
    'catalog',
    'OnSuccessCatalogImport1C',
    function ($event) {


//        $logger = new FileLogger(
//            $_SERVER['DOCUMENT_ROOT'].'/logs/1c_exchange/'.(new DateTime(
//            ))->format('dmy').'.log'
//        );
//
//        $logger->debug(
//            '{date}' . PHP_EOL . '{data}' . PHP_EOL,
//            [
//                'data' => [
//                    'event' => $event,
//                ],
//            ]
//        );
    }
);
