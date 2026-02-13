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

if (!function_exists('brakes_1c_parse_log_event')) {
    function brakes_1c_parse_log_event(string $severity, string $message, array $context = []): void
    {
        if (!class_exists('CEventLog')) {
            return;
        }

        $description = $message;
        if ($context) {
            $description .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        CEventLog::Add([
            'SEVERITY' => $severity,
            'AUDIT_TYPE_ID' => 'BRKS_1C_PARSE',
            'MODULE_ID' => 'brakes',
            'ITEM_ID' => '1c_catalog_parse',
            'DESCRIPTION' => $description,
        ]);
    }
}

if (!function_exists('brakes_1c_image_log_event')) {
    function brakes_1c_image_log_event(string $severity, string $message, array $context = []): void
    {
        if (!class_exists('CEventLog')) {
            return;
        }

        $description = $message;
        if ($context) {
            $description .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        CEventLog::Add([
            'SEVERITY' => $severity,
            'AUDIT_TYPE_ID' => 'BRKS_1C_IMAGE',
            'MODULE_ID' => 'brakes',
            'ITEM_ID' => '1c_image_migrator',
            'DESCRIPTION' => $description,
        ]);
    }
}

if (!function_exists('brakes_is_1c_exchange_request')) {
    function brakes_is_1c_exchange_request(): bool
    {
        $script = (string)($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script === '') {
            $script = (string)($_SERVER['PHP_SELF'] ?? '');
        }
        return str_ends_with($script, '/bitrix/admin/1c_exchange.php');
    }
}

if (!function_exists('brakes_dispatch_background_job')) {
    function brakes_dispatch_background_job(callable $runner): void
    {
        // catalog.import.1c finishes with die(), so addBackgroundJob is skipped there.
        if (PHP_SAPI !== 'cli' && brakes_is_1c_exchange_request()) {
            register_shutdown_function(static function () use ($runner): void {
                if (function_exists('fastcgi_finish_request')) {
                    @fastcgi_finish_request();
                }
                $runner();
            });
            return;
        }

        if (class_exists(\Bitrix\Main\Application::class)
            && method_exists(\Bitrix\Main\Application::getInstance(), 'addBackgroundJob')
        ) {
            \Bitrix\Main\Application::getInstance()->addBackgroundJob($runner);
            return;
        }

        $runner();
    }
}

if (!function_exists('brakes_1c_images_extract_links')) {
    function brakes_1c_images_extract_links(string $absFileName): array
    {
        $result = [];
        if ($absFileName === '' || !is_file($absFileName)) {
            return $result;
        }

        $reader = new \XMLReader();
        if (!$reader->open($absFileName)) {
            return $result;
        }

        $normalizeLinks = static function (string $raw): string {
            $parts = array_filter(array_map('trim', explode(';', $raw)));
            if (!$parts) {
                return '';
            }
            return implode(';', array_values(array_unique($parts)));
        };

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT) {
                continue;
            }
            if ($reader->localName !== 'Товар') {
                continue;
            }

            $xml = $reader->readOuterXML();
            if ($xml === '') {
                continue;
            }

            try {
                $node = new \SimpleXMLElement($xml);
            } catch (\Throwable $exception) {
                continue;
            }

            $xmlId = trim((string)($node->Ид ?? ''));
            if ($xmlId === '') {
                continue;
            }

            $linksValue = '';
            if (isset($node->ЗначенияРеквизитов)) {
                foreach ($node->ЗначенияРеквизитов->ЗначениеРеквизита as $requisite) {
                    $name = trim((string)($requisite->Наименование ?? ''));
                    if ($name !== 'Ссылки на фото') {
                        continue;
                    }
                    $linksValue = trim((string)($requisite->Значение ?? ''));
                    break;
                }
            }

            $linksValue = $normalizeLinks($linksValue);
            if ($linksValue !== '') {
                $result[$xmlId] = $linksValue;
            }
        }

        $reader->close();
        return $result;
    }
}

if (!function_exists('brakes_1c_images_sync_from_import')) {
    function brakes_1c_images_sync_from_import(string $absFileName, array $options = []): array
    {
        $iblockId = (int)($options['iblockId'] ?? 1);
        $force = !empty($options['force']);

        $linksByXml = brakes_1c_images_extract_links($absFileName);
        if (!$linksByXml) {
            return [
                'found' => 0,
                'updated' => 0,
                'migrated' => 0,
                'skipped' => 0,
            ];
        }

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            throw new \RuntimeException('iblock module is not available');
        }

        $xmlIds = array_keys($linksByXml);
        $updated = 0;
        $migrated = 0;
        $skipped = 0;

        $chunks = array_chunk($xmlIds, 500);
        foreach ($chunks as $chunk) {
            $elementMap = [];
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

            foreach ($chunk as $xmlId) {
                $elementId = $elementMap[$xmlId] ?? 0;
                if ($elementId <= 0) {
                    $skipped++;
                    continue;
                }

                $newValue = $linksByXml[$xmlId] ?? '';
                if ($newValue === '') {
                    $skipped++;
                    continue;
                }

                $currentValues = [];
                $propRes = \CIBlockElement::GetProperty(
                    $iblockId,
                    $elementId,
                    ['sort' => 'asc', 'id' => 'asc'],
                    ['CODE' => 'LINK_PHOTO']
                );
                while ($prop = $propRes->Fetch()) {
                    $val = trim((string)($prop['VALUE'] ?? ''));
                    if ($val !== '') {
                        $currentValues[] = $val;
                    }
                }
                $currentValue = $currentValues ? trim((string)$currentValues[0]) : '';

                if (!$force && $currentValue === $newValue) {
                    $skipped++;
                    continue;
                }

                \CIBlockElement::SetPropertyValueCode($elementId, 'LINK_PHOTO', $newValue);
                $updated++;

                if (class_exists(\App\Brakes\Helper\ImageMigrator::class)) {
                    \App\Brakes\Helper\ImageMigrator::migrateElement($elementId, [
                        'force' => true,
                    ]);
                    $migrated++;
                }
            }
        }

        return [
            'found' => count($linksByXml),
            'updated' => $updated,
            'migrated' => $migrated,
            'skipped' => $skipped,
        ];
    }
}

if (!function_exists('brakes_1c_images_schedule')) {
    function brakes_1c_images_schedule(string $absFileName, array $meta = []): void
    {
        $runner = static function () use ($absFileName): void {
            try {
                $result = brakes_1c_images_sync_from_import($absFileName, [
                    'iblockId' => 1,
                ]);
                brakes_1c_image_log_event('INFO', '1c image migrator finished', [
                    'file' => $absFileName,
                    'result' => $result,
                ]);
            } catch (\Throwable $exception) {
                brakes_1c_image_log_event('ERROR', '1c image migrator failed', [
                    'file' => $absFileName,
                    'error' => $exception->getMessage(),
                ]);
            }
        };

        brakes_1c_image_log_event('INFO', '1c image migrator scheduled', [
            'source' => $meta['source'] ?? null,
            'file' => $absFileName,
        ]);

        brakes_dispatch_background_job($runner);
    }
}

if (!function_exists('brakes_1c_parse_run_safe')) {
    function brakes_1c_parse_run_safe(array $options = []): void
    {
        try {
            require_once $_SERVER['DOCUMENT_ROOT'] . '/local/cron/1c_catalog_parse.php';
            if (function_exists('brakes_1c_catalog_parse_run')) {
                $result = brakes_1c_catalog_parse_run($options);
                brakes_1c_parse_log_event('INFO', '1c catalog parse finished', [
                    'result' => $result,
                ]);
            } else {
                brakes_1c_parse_log_event('ERROR', '1c catalog parse function not found');
            }
        } catch (\Throwable $exception) {
            brakes_1c_parse_log_event('ERROR', '1c catalog parse failed', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

if (!function_exists('brakes_1c_parse_schedule')) {
    function brakes_1c_parse_schedule(array $options = [], array $meta = [], bool $runNow = true): void
    {
        if (!class_exists(\Bitrix\Main\Config\Option::class)) {
            if ($runNow) {
                brakes_1c_parse_run_safe($options);
            }
            return;
        }

        $moduleId = 'brakes';

        // Prevent duplicate runs when both "rests" and "complete" triggers fire for the same exchange file.
        // Important: do this BEFORE we touch pending/running flags.
        $runFile = (string)($meta['file'] ?? '');
        if ($runNow && $runFile !== '') {
            $lastRunFile = (string)\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_last_run_file', '');
            $lastRunTs = (int)\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_last_run_finish_ts', '0');
            if ($lastRunFile !== '' && $lastRunFile === $runFile && $lastRunTs > 0 && (time() - $lastRunTs) < 900) {
                brakes_1c_parse_log_event('INFO', '1c catalog parse skipped (duplicate)', [
                    'source' => $meta['source'] ?? null,
                    'file' => $runFile,
                ]);
                return;
            }
        }

        \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_pending', 'Y');
        \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_ts', (string)time());
        if (!empty($meta['file'])) {
            \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_file', (string)$meta['file']);
        }
        if (!empty($meta['source'])) {
            \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_source', (string)$meta['source']);
        }

        brakes_1c_parse_log_event('INFO', '1c catalog parse scheduled', [
            'source' => $meta['source'] ?? null,
            'file' => $meta['file'] ?? null,
            'runNow' => $runNow ? 'Y' : 'N',
            'options' => $options,
        ]);

        if (!$runNow) {
            return;
        }

        $jobToken = (string)(microtime(true) . ':' . mt_rand(1000, 9999));
        \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_job_token', $jobToken);

        $runner = static function () use ($options, $moduleId, $jobToken, $runFile): void {
            $currentToken = \Bitrix\Main\Config\Option::get($moduleId, '1c_parse_job_token', '');
            if ($currentToken !== $jobToken) {
                return;
            }

            if (\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_running', 'N') === 'Y') {
                return;
            }

            \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_running', 'Y');
            \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_pending', 'N');
            try {
                brakes_1c_parse_run_safe($options);
            } finally {
                \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_running', 'N');
                $finishTs = time();
                \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_finish', (string)$finishTs);
                if ($runFile !== '') {
                    \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_run_file', $runFile);
                    \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_last_run_finish_ts', (string)$finishTs);
                }
            }
        };

        brakes_dispatch_background_job($runner);
    }
}

if (!function_exists('brakes_1c_parse_try_fallback')) {
    function brakes_1c_parse_try_fallback(): void
    {
        if (PHP_SAPI === 'cli' || brakes_is_1c_exchange_request()) {
            return;
        }

        if (!class_exists(\Bitrix\Main\Config\Option::class)) {
            return;
        }

        $moduleId = 'brakes';
        $pending = \Bitrix\Main\Config\Option::get($moduleId, '1c_parse_pending', 'N');
        if ($pending !== 'Y') {
            return;
        }

        if (\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_running', 'N') === 'Y') {
            return;
        }

        $lastTs = (int)\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_last_ts', '0');
        if ($lastTs <= 0 || (time() - $lastTs) < 120) {
            return;
        }

        $lastFallback = (int)\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_fallback_last_ts', '0');
        if ($lastFallback > 0 && (time() - $lastFallback) < 30) {
            return;
        }
        \Bitrix\Main\Config\Option::set($moduleId, '1c_parse_fallback_last_ts', (string)time());

        $lastFile = (string)\Bitrix\Main\Config\Option::get($moduleId, '1c_parse_last_file', '');
        brakes_1c_parse_schedule([
            'iblockId' => 1,
            'reactivate' => true,
            'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
            'logPrefix' => 'OnSuccessTimeoutFallback',
        ], [
            'source' => 'OnSuccessTimeoutFallback',
            'file' => $lastFile,
        ], true);
    }
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
    $hiddenProps = [
        'CONTEXT_SECTION_ID' => true,
        'CONTEXT_PATH' => true,
        'CONTEXT_LABEL' => true,
        'OPTIONS_JSON' => true,
        'OPTIONS' => true,
    ];

    foreach ($rows as &$row) {
        if (empty($row['data']['PROPS']) || !is_array($row['data']['PROPS'])) {
            continue;
        }

        $filtered = [];
        foreach ($row['data']['PROPS'] as $prop) {
            if (!is_array($prop)) {
                continue;
            }
            $code = (string)($prop['CODE'] ?? '');
            if ($code !== '' && isset($hiddenProps[$code])) {
                continue;
            }
            $filtered[] = $prop;
        }
        $row['data']['PROPS'] = $filtered;
    }
    unset($row);

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
AddEventHandler('catalog', 'OnCompleteCatalogImport1C', static function ($params = null, $absFileName = ''): void {
    $absFileName = (string)$absFileName;
    if ($absFileName === '' && class_exists(\Bitrix\Main\Config\Option::class)) {
        $absFileName = (string)\Bitrix\Main\Config\Option::get('brakes', '1c_parse_last_file', '');
    }

    brakes_1c_parse_schedule([
        'iblockId' => 1,
        'reactivate' => true,
        'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
        'logPrefix' => 'OnCompleteCatalogImport1C',
    ], [
        'source' => 'OnCompleteCatalogImport1C',
        'file' => $absFileName,
    ]);
});

AddEventHandler('main', 'OnAfterEpilog', static function (): void {
    brakes_1c_parse_try_fallback();
});
