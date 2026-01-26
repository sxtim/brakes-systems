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
    $logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log';
    $logLine = date('c')
        . ' src=1c_exchange_guard'
        . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-')
        . ' qs=' . ($_SERVER['QUERY_STRING'] ?? '-')
        . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
        . PHP_EOL;
    @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

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
    'App\Brakes\Helper\BasketManager' => '/local/app/Brakes/Helper/BasketManager.php',
    'App\Brakes\Auth\Sms' => '/local/app/Brakes/Auth/Sms.php',
    'App\Brakes\Pricing\Configurator' => '/local/app/Brakes/Pricing/Configurator.php',
]);

AddEventHandler('main', 'OnBeforeProlog', static function (): void {
    \App\Brakes\Helper\FavoritesManager::handleProlog();
});

AddEventHandler('sale', 'OnSaleBasketItemBeforeSaved', static function ($event): void {
    $item = null;

    if ($event instanceof \Bitrix\Main\Event) {
        $item = $event->getParameter('ENTITY');
        if (!$item instanceof \Bitrix\Sale\BasketItemBase) {
            $item = $event->getParameter('ITEM');
        }
    } elseif (is_array($event)) {
        $item = $event['ENTITY'] ?? $event['ITEM'] ?? null;
    }

    if (!$item instanceof \Bitrix\Sale\BasketItemBase) {
        return;
    }

    try {
        \App\Brakes\Helper\BasketManager::syncCustomPrice($item);
    } catch (\Throwable $exception) {
        // ignore pricing errors to avoid blocking basket save
    }
});

AddEventHandler('sale', 'OnSaleComponentOrderJsData', static function (array &$arResult, array &$arParams): void {
    if (empty($arResult['JS_DATA']['GRID']['ROWS']) || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }

    $rows = &$arResult['JS_DATA']['GRID']['ROWS'];
    $productIds = [];
    foreach ($rows as $row) {
        $productId = (int)($row['data']['PRODUCT_ID'] ?? 0);
        if ($productId > 0) {
            $productIds[$productId] = true;
        }
    }

    if ($productIds === []) {
        return;
    }

    $elements = [];
    $elementRes = \CIBlockElement::GetList(
        [],
        ['ID' => array_keys($productIds)],
        false,
        false,
        ['ID', 'IBLOCK_ID', 'DETAIL_PICTURE', 'PREVIEW_PICTURE']
    );
    while ($element = $elementRes->Fetch()) {
        $elements[(int)$element['ID']] = $element;
    }

    if ($elements === []) {
        return;
    }

    $propCache = [];
    $getPropertyValues = static function (int $iblockId, int $elementId, string $code) use (&$propCache): array {
        $cacheKey = $iblockId . ':' . $elementId . ':' . $code;
        if (isset($propCache[$cacheKey])) {
            return $propCache[$cacheKey];
        }

        $values = [];
        $res = \CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            ['sort' => 'asc', 'id' => 'asc'],
            ['CODE' => $code]
        );
        while ($row = $res->Fetch()) {
            $value = $row['VALUE'] ?? null;
            if (is_array($value)) {
                foreach ($value as $valItem) {
                    $valItem = is_scalar($valItem) ? trim((string)$valItem) : '';
                    if ($valItem !== '') {
                        $values[] = $valItem;
                    }
                }
            } else {
                $value = is_scalar($value) ? trim((string)$value) : '';
                if ($value !== '') {
                    $values[] = $value;
                }
            }
        }

        $propCache[$cacheKey] = $values;

        return $values;
    };

    $resolveFileSrc = static function (int $fileId): ?string {
        if ($fileId <= 0) {
            return null;
        }

        $file = \CFile::GetFileArray($fileId);
        if (is_array($file) && !empty($file['SRC'])) {
            return $file['SRC'];
        }

        $path = (string)\CFile::GetPath($fileId);
        return $path !== '' ? $path : null;
    };

    foreach ($rows as &$row) {
        $productId = (int)($row['data']['PRODUCT_ID'] ?? 0);
        if ($productId <= 0 || empty($elements[$productId])) {
            continue;
        }

        $element = $elements[$productId];
        $iblockId = (int)($element['IBLOCK_ID'] ?? 0);
        if ($iblockId <= 0) {
            continue;
        }

        $src = null;

        $linkPhotoFiles = $getPropertyValues($iblockId, $productId, 'LINK_PHOTO_FILE');
        foreach ($linkPhotoFiles as $fileId) {
            $fileId = (int)$fileId;
            $src = $resolveFileSrc($fileId);
            if ($src !== null) {
                break;
            }
        }

        if ($src === null) {
            $detailId = (int)($element['DETAIL_PICTURE'] ?? 0);
            $src = $resolveFileSrc($detailId);
        }

        if ($src === null) {
            $morePhotos = $getPropertyValues($iblockId, $productId, 'MORE_PHOTO');
            foreach ($morePhotos as $fileId) {
                $fileId = (int)$fileId;
                $src = $resolveFileSrc($fileId);
                if ($src !== null) {
                    break;
                }
            }
        }

        if ($src === null) {
            $previewId = (int)($element['PREVIEW_PICTURE'] ?? 0);
            $src = $resolveFileSrc($previewId);
        }

        if ($src === null) {
            $linkPhoto = $getPropertyValues($iblockId, $productId, 'LINK_PHOTO');
            foreach ($linkPhoto as $value) {
                $parts = array_filter(array_map('trim', explode(';', (string)$value)));
                if (!empty($parts[0])) {
                    $src = $parts[0];
                    break;
                }
            }
        }

        if ($src !== null) {
            $row['data']['PREVIEW_PICTURE'] = $row['data']['PREVIEW_PICTURE'] ?: 1;
            $row['data']['DETAIL_PICTURE'] = $row['data']['DETAIL_PICTURE'] ?: 1;
            $row['data']['PREVIEW_PICTURE_SRC'] = $src;
            $row['data']['PREVIEW_PICTURE_SRC_2X'] = $src;
            $row['data']['PREVIEW_PICTURE_SRC_ORIGINAL'] = $src;
            $row['data']['DETAIL_PICTURE_SRC'] = $src;
            $row['data']['DETAIL_PICTURE_SRC_2X'] = $src;
            $row['data']['DETAIL_PICTURE_SRC_ORIGINAL'] = $src;
        }
    }
    unset($row);
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
