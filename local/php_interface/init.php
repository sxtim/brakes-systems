<?php

// Безопасная обработка mode=deactivate при 1С-обмене (catalog):
// - стандартный механизм деактивирует и элементы, и разделы, которых не было в полной выгрузке;
// - у нас есть собственное дерево разделов "категория → марка → модель → кузов", которого нет в 1С,
//   поэтому разделы деактивировать нельзя;
// - при этом деактивация ТОВАРОВ (элементов) может быть нужна ("пропал из полной выгрузки → снять с витрины").
// Здесь мы деактивируем только элементы IBLOCK_ID=1 и возвращаем success, не трогая разделы.
if (
    !empty($_SERVER['SCRIPT_NAME'])
    && substr((string)$_SERVER['SCRIPT_NAME'], -strlen('/bitrix/admin/1c_exchange.php')) === '/bitrix/admin/1c_exchange.php'
    && (($_REQUEST['type'] ?? '') === 'catalog')
    && (($_REQUEST['mode'] ?? '') === 'deactivate')
) {
    $timestamp = (int)($_REQUEST['timestamp'] ?? 0);
    $iblockId = 1;
    $deactivated = 0;
    $error = null;
    $skippedReason = null;
    $coverage = null;

    try {
        if ($timestamp > 0 && class_exists(\Bitrix\Main\Loader::class)) {
            // Safety: perform element-only deactivate only in a full-exchange context
            // (after rests or complete). This prevents mass deactivation on "changed-only" exchanges.
            $now = time();
            $restsTs = class_exists(\Bitrix\Main\Config\Option::class)
                ? (int)\Bitrix\Main\Config\Option::get('brakes', '1c_last_rests_ts', '0')
                : 0;
            $completeTs = class_exists(\Bitrix\Main\Config\Option::class)
                ? (int)\Bitrix\Main\Config\Option::get('brakes', '1c_last_complete_ts', '0')
                : 0;

            if (
                ($restsTs <= 0 || ($now - $restsTs) > 6 * 3600)
                && ($completeTs <= 0 || ($now - $completeTs) > 6 * 3600)
            ) {
                $skippedReason = 'no_full_exchange_context';
            } else {
            \Bitrix\Main\Loader::includeModule('iblock');
            \CTimeZone::Disable();
            try {
                // NOTE: mode=deactivate passes UNIX timestamp, MySQL expects DATETIME in Y-m-d H:i:s.
                // ConvertTimeStamp() can return locale-specific format like "15.02.2026 17:40:15",
                // which breaks SQL comparisons, so we always use explicit MySQL datetime format here.
                $timeStamp = date('Y-m-d H:i:s', $timestamp);
                $connection = \Bitrix\Main\Application::getConnection();
                $helper = $connection->getSqlHelper();
                $safeTs = $helper->forSql($timeStamp);

                // Extra safety: skip deactivate if this looks like a partial ("changed-only") exchange.
                // In a full exchange, most elements get touched and have TIMESTAMP_X >= exchange start timestamp.
                $totalActive = (int)$connection->queryScalar(
                    "SELECT COUNT(1) FROM b_iblock_element WHERE IBLOCK_ID=" . (int)$iblockId . " AND ACTIVE='Y'"
                );
                $touched = (int)$connection->queryScalar(
                    "SELECT COUNT(1) FROM b_iblock_element WHERE IBLOCK_ID=" . (int)$iblockId . " AND ACTIVE='Y' AND TIMESTAMP_X >= '" . $safeTs . "'"
                );
                $ratio = $totalActive > 0 ? ($touched / $totalActive) : 0.0;
                $coverage = ['totalActive' => $totalActive, 'touched' => $touched, 'ratio' => $ratio];

                if ($totalActive > 0 && $ratio < 0.7) {
                    $skippedReason = 'low_coverage';
                } else {
                // Deactivate only elements which were not updated since the exchange start timestamp.
                $sql = "UPDATE b_iblock_element SET ACTIVE='N' "
                    . "WHERE IBLOCK_ID=" . (int)$iblockId . " AND ACTIVE='Y' AND TIMESTAMP_X < '" . $safeTs . "'";
                $connection->queryExecute($sql);

                // Best-effort count of affected rows (driver may return 0 for some engines).
                $deactivated = method_exists($connection, 'getAffectedRowsCount')
                    ? (int)$connection->getAffectedRowsCount()
                    : 0;
                }
            } finally {
                \CTimeZone::Enable();
            }
            }
        }
    } catch (\Throwable $exception) {
        $error = $exception->getMessage();
    }

    $logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log';
    $logLine = date('c')
        . ' src=1c_exchange_guard'
        . ' mode=deactivate'
        . ' iblock=' . (int)$iblockId
        . ' deactivated=' . (int)$deactivated
        . ' ts=' . $timestamp
        . ($skippedReason ? ' skipped=' . $skippedReason : '')
        . ($error ? ' error=' . $error : '')
        . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '-')
        . ' qs=' . ($_SERVER['QUERY_STRING'] ?? '-')
        . ' ua=' . ($_SERVER['HTTP_USER_AGENT'] ?? '-')
        . PHP_EOL;
    @file_put_contents($logPath, $logLine, FILE_APPEND | LOCK_EX);

    if (class_exists('CEventLog')) {
        CEventLog::Add([
            'SEVERITY' => $error ? 'ERROR' : 'INFO',
            'AUDIT_TYPE_ID' => 'BRKS_1C_DEACT',
            'MODULE_ID' => 'brakes',
            'ITEM_ID' => '1c_exchange_guard',
            'DESCRIPTION' => '1c deactivate handled | ' . json_encode([
                'iblockId' => $iblockId,
                'timestamp' => $timestamp,
                'deactivated' => $deactivated,
                'skipped' => $skippedReason,
                'coverage' => $coverage,
                'error' => $error,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

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
        $linksByXml = isset($options['linksByXml']) && is_array($options['linksByXml'])
            ? $options['linksByXml']
            : brakes_1c_images_extract_links($absFileName);
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

if (!function_exists('brakes_1c_images_extract_file_pictures')) {
    function brakes_1c_images_extract_file_pictures(string $absFileName): array
    {
        $result = [];
        if ($absFileName === '' || !is_file($absFileName)) {
            return $result;
        }

        $reader = new \XMLReader();
        if (!$reader->open($absFileName)) {
            return $result;
        }

        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'Товар') {
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

            $pictures = [];
            foreach ($node->Картинка as $pictureNode) {
                $value = trim((string)$pictureNode);
                if ($value !== '') {
                    $pictures[] = $value;
                }
            }

            if ($pictures !== []) {
                $result[$xmlId] = array_values(array_unique($pictures));
            }
        }

        $reader->close();
        return $result;
    }
}

if (!function_exists('brakes_1c_images_normalize_source_path')) {
    function brakes_1c_images_normalize_source_path(string $baseDir, string $path): ?string
    {
        $path = trim(str_replace('\\', '/', $path));
        if ($path === '') {
            return null;
        }

        $relative = ltrim($path, '/');
        $candidate = rtrim($baseDir, '/') . '/' . $relative;
        if (is_file($candidate)) {
            return $candidate;
        }

        $candidate = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $relative;
        if (is_file($candidate)) {
            return $candidate;
        }

        return null;
    }
}

if (!function_exists('brakes_1c_images_load_target_rows')) {
    function brakes_1c_images_load_target_rows(int $iblockId, int $elementId): array
    {
        $rows = [];
        $propRes = \CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            ['sort' => 'asc', 'id' => 'asc'],
            ['CODE' => 'LINK_PHOTO_FILE']
        );
        while ($prop = $propRes->Fetch()) {
            $fileId = (int)($prop['VALUE'] ?? 0);
            $originalName = '';

            if ($fileId > 0) {
                $file = \CFile::GetFileArray($fileId);
                if (is_array($file)) {
                    $originalName = basename((string)($file['ORIGINAL_NAME'] ?? $file['FILE_NAME'] ?? ''));
                }
            }

            $rows[] = [
                'fileId' => $fileId,
                'valueId' => (int)($prop['PROPERTY_VALUE_ID'] ?? 0),
                'originalName' => $originalName,
            ];
        }

        return $rows;
    }
}

if (!function_exists('brakes_1c_images_same_target_set')) {
    function brakes_1c_images_same_target_set(array $targetRows, array $picturePaths): bool
    {
        $currentNames = [];
        foreach ($targetRows as $row) {
            $name = trim((string)($row['originalName'] ?? ''));
            if ($name !== '') {
                $currentNames[] = $name;
            }
        }

        $sourceNames = [];
        foreach ($picturePaths as $path) {
            $path = trim(str_replace('\\', '/', (string)$path));
            if ($path !== '') {
                $sourceNames[] = basename($path);
            }
        }

        return $currentNames !== [] && $currentNames === $sourceNames;
    }
}

if (!function_exists('brakes_1c_images_build_replacement_property_value')) {
    function brakes_1c_images_build_replacement_property_value(array $targetRows, array $fileValues): array
    {
        $propertyValue = [];

        foreach ($targetRows as $row) {
            $valueId = (int)($row['valueId'] ?? 0);
            if ($valueId <= 0) {
                continue;
            }

            $propertyValue[$valueId] = [
                'VALUE' => [
                    'del' => 'Y',
                ],
            ];
        }

        foreach ($fileValues as $key => $value) {
            $propertyValue[$key] = $value;
        }

        return $propertyValue;
    }
}

if (!function_exists('brakes_1c_images_apply_file_pictures')) {
    function brakes_1c_images_apply_file_pictures(int $elementId, string $xmlId, array $picturePaths, array $options = []): array
    {
        $iblockId = (int)($options['iblockId'] ?? 1);
        $baseDir = (string)($options['baseDir'] ?? '');
        $force = !empty($options['force']);
        $dryRun = !empty($options['dryRun']);

        if ($picturePaths === []) {
            return [
                'status' => 'no_pictures',
                'elementId' => $elementId,
                'xmlId' => $xmlId,
            ];
        }

        $targetRows = brakes_1c_images_load_target_rows($iblockId, $elementId);
        if (!$force && brakes_1c_images_same_target_set($targetRows, $picturePaths)) {
            return [
                'status' => 'skip_same',
                'elementId' => $elementId,
                'xmlId' => $xmlId,
            ];
        }

        $fileValues = [];
        $resolvedFiles = [];
        $errors = [];

        foreach ($picturePaths as $index => $picturePath) {
            $absolutePath = brakes_1c_images_normalize_source_path($baseDir, $picturePath);
            if ($absolutePath === null) {
                $errors[] = "file not found: {$picturePath}";
                continue;
            }

            $fileArray = \CFile::MakeFileArray($absolutePath);
            if (!is_array($fileArray)) {
                $errors[] = "failed to prepare file: {$absolutePath}";
                continue;
            }

            $fileArray['MODULE_ID'] = 'iblock';
            $fileValues['n' . $index] = [
                'VALUE' => $fileArray,
                'DESCRIPTION' => '',
            ];
            $resolvedFiles[] = $absolutePath;
        }

        if ($fileValues === []) {
            return [
                'status' => 'error',
                'elementId' => $elementId,
                'xmlId' => $xmlId,
                'errors' => $errors,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry_run',
                'elementId' => $elementId,
                'xmlId' => $xmlId,
                'files' => $resolvedFiles,
                'errors' => $errors,
                'count' => count($fileValues),
            ];
        }

        $propertyValue = brakes_1c_images_build_replacement_property_value($targetRows, $fileValues);
        \CIBlockElement::SetPropertyValueCode($elementId, 'LINK_PHOTO_FILE', $propertyValue);

        return [
            'status' => 'updated',
            'elementId' => $elementId,
            'xmlId' => $xmlId,
            'files' => $resolvedFiles,
            'errors' => $errors,
            'count' => count($fileValues),
        ];
    }
}

if (!function_exists('brakes_1c_images_sync_file_pictures_from_import')) {
    function brakes_1c_images_sync_file_pictures_from_import(string $absFileName, array $options = []): array
    {
        $iblockId = (int)($options['iblockId'] ?? 1);
        $force = !empty($options['force']);
        $skipXmlIds = array_fill_keys((array)($options['skipXmlIds'] ?? []), true);

        $picturesByXml = brakes_1c_images_extract_file_pictures($absFileName);
        if (!$picturesByXml) {
            return [
                'found' => 0,
                'updated' => 0,
                'skip_same' => 0,
                'skipped_by_links' => 0,
                'not_found' => 0,
                'error' => 0,
            ];
        }

        if (!\Bitrix\Main\Loader::includeModule('iblock')) {
            throw new \RuntimeException('iblock module is not available');
        }

        $xmlIds = array_keys($picturesByXml);
        $elementMap = [];
        foreach (array_chunk($xmlIds, 500) as $chunk) {
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
        }

        $stats = [
            'found' => count($picturesByXml),
            'updated' => 0,
            'skip_same' => 0,
            'skipped_by_links' => 0,
            'not_found' => 0,
            'error' => 0,
        ];

        $baseDir = dirname($absFileName);
        foreach ($picturesByXml as $xmlId => $picturePaths) {
            if (isset($skipXmlIds[$xmlId])) {
                $stats['skipped_by_links']++;
                continue;
            }

            $elementId = (int)($elementMap[$xmlId] ?? 0);
            if ($elementId <= 0) {
                $stats['not_found']++;
                continue;
            }

            $result = brakes_1c_images_apply_file_pictures($elementId, $xmlId, $picturePaths, [
                'iblockId' => $iblockId,
                'baseDir' => $baseDir,
                'force' => $force,
            ]);

            $status = (string)($result['status'] ?? '');
            if (isset($stats[$status])) {
                $stats[$status]++;
            } elseif ($status === 'dry_run') {
                continue;
            } else {
                $stats['error']++;
            }
        }

        return $stats;
    }
}

if (!function_exists('brakes_1c_images_schedule')) {
    function brakes_1c_images_schedule(string $absFileName, array $meta = []): void
    {
        $runner = static function () use ($absFileName): void {
            try {
                $linksByXml = brakes_1c_images_extract_links($absFileName);
                $linksResult = brakes_1c_images_sync_from_import($absFileName, [
                    'iblockId' => 1,
                    'linksByXml' => $linksByXml,
                ]);
                $filesResult = brakes_1c_images_sync_file_pictures_from_import($absFileName, [
                    'iblockId' => 1,
                    'skipXmlIds' => array_keys($linksByXml),
                ]);
                brakes_1c_image_log_event('INFO', '1c image migrator finished', [
                    'file' => $absFileName,
                    'linksResult' => $linksResult,
                    'filesResult' => $filesResult,
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
            'reactivateSections' => true,
            'reactivateElements' => false,
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
    'App\Brakes\Helper\StockProvider' => '/local/app/Brakes/Helper/StockProvider.php',
    'App\Brakes\Helper\FavoritesManager' => '/local/app/Brakes/Helper/FavoritesManager.php',
    'App\Brakes\Helper\BasketManager' => '/local/app/Brakes/Helper/BasketManager.php',
    'App\Brakes\Auth\Sms' => '/local/app/Brakes/Auth/Sms.php',
    'App\Brakes\Pricing\Configurator' => '/local/app/Brakes/Pricing/Configurator.php',
    'App\Brakes\Order\PaymentFlow' => '/local/app/Brakes/Order/PaymentFlow.php',
]);

\App\Brakes\Order\PaymentFlow::bootstrap();

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

AddEventHandler('sale', 'OnSaleOrderBeforeSaved', static function ($event): void {
    $order = $event instanceof \Bitrix\Main\Event ? $event->getParameter('ENTITY') : $event;
    if (!$order instanceof \Bitrix\Sale\Order) {
        return;
    }

    $technicalBasketPropCodes = [
        'CONTEXT_SECTION_ID' => true,
        'CONTEXT_PATH' => true,
        'CONTEXT_LABEL' => true,
        'OPTIONS_JSON' => true,
        'OPTIONS_HASH' => true,
        'OPTIONS' => true,
    ];

    $basket = $order->getBasket();
    if ($basket instanceof \Bitrix\Sale\BasketBase) {
        /** @var \Bitrix\Sale\BasketItem $basketItem */
        foreach ($basket as $basketItem) {
            if (!$basketItem instanceof \Bitrix\Sale\BasketItem) {
                continue;
            }

            $propertyCollection = $basketItem->getPropertyCollection();
            if (!$propertyCollection) {
                continue;
            }

            $propertyValues = [];
            if (method_exists($propertyCollection, 'getPropertyValues')) {
                $propertyValues = $propertyCollection->getPropertyValues();
            } elseif (method_exists($propertyCollection, 'getArray')) {
                $propertyData = $propertyCollection->getArray();
                $propertyValues = is_array($propertyData['PROPS'] ?? null) ? $propertyData['PROPS'] : [];
            }

            if (!is_array($propertyValues) || $propertyValues === []) {
                continue;
            }

            $filteredProps = [];
            foreach ($propertyValues as $code => $property) {
                if (is_array($property)) {
                    $propCode = (string)($property['CODE'] ?? $code);
                    if ($propCode !== '' && isset($technicalBasketPropCodes[$propCode])) {
                        continue;
                    }
                    $filteredProps[] = $property;
                    continue;
                }

                if (is_string($code) && $code !== '' && isset($technicalBasketPropCodes[$code])) {
                    continue;
                }
            }

            if (count($filteredProps) !== count($propertyValues)) {
                $propertyCollection->setProperty($filteredProps);
            }
        }
    }

    try {
        \App\Brakes\Order\PaymentFlow::prepareOrderBeforeSave($order);
    } catch (\Throwable $exception) {
        // Do not block saving an order because of optional payment-flow synchronization.
    }
});

AddEventHandler('sale', 'OnSaleComponentOrderJsData', static function (array &$arResult, array &$arParams): void {
    if (!empty($arResult['JS_DATA']['ORDER_PROP']['properties']) && is_array($arResult['JS_DATA']['ORDER_PROP']['properties'])) {
        foreach ($arResult['JS_DATA']['ORDER_PROP']['properties'] as $key => &$property) {
            if (!is_array($property)) {
                continue;
            }

            $code = (string)($property['CODE'] ?? '');
            if ($code === 'PAYMENT_LINK' || $code === 'CITY') {
                unset($arResult['JS_DATA']['ORDER_PROP']['properties'][$key]);
                continue;
            }

            if ($code === 'FIO') {
                $normalizeValue = static function ($value): string {
                    if (is_array($value)) {
                        return '';
                    }

                    return trim(html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                };

                foreach (['VALUE', 'DEFAULT_VALUE', 'VALUE_FORMATED'] as $field) {
                    if (!array_key_exists($field, $property)) {
                        continue;
                    }

                    $normalized = $normalizeValue($property[$field]);
                    if ($normalized === '<Без имени>' || $normalized === 'Без имени') {
                        $property[$field] = '';
                    }
                }
            }

        }
        unset($property);
    }

    if (empty($arResult['JS_DATA']['GRID']['ROWS']) || !\Bitrix\Main\Loader::includeModule('iblock')) {
        return;
    }

    $rows = &$arResult['JS_DATA']['GRID']['ROWS'];
    $hiddenProps = [
        'CONTEXT_SECTION_ID' => true,
        'CONTEXT_PATH' => true,
        'CONTEXT_LABEL' => true,
        'OPTIONS_JSON' => true,
        'OPTIONS_HASH' => true,
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

    if (class_exists(\Bitrix\Main\Config\Option::class)) {
        \Bitrix\Main\Config\Option::set('brakes', '1c_last_complete_ts', (string)time());
        \Bitrix\Main\Config\Option::set('brakes', '1c_last_complete_file', (string)$absFileName);
    }

    brakes_1c_parse_schedule([
        'iblockId' => 1,
        'reactivateSections' => true,
        'reactivateElements' => false,
        'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
        'logPrefix' => 'OnCompleteCatalogImport1C',
    ], [
        'source' => 'OnCompleteCatalogImport1C',
        'file' => $absFileName,
    ]);
});

AddEventHandler('main', 'OnProlog', static function (): void {
    if (!defined('ADMIN_SECTION') || ADMIN_SECTION !== true) {
        return;
    }

    $scriptName = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    if (!in_array($scriptName, ['sale_order_view.php', 'sale_order_edit.php'], true)) {
        return;
    }

    if (class_exists(\Bitrix\Main\Page\Asset::class)) {
        \Bitrix\Main\Page\Asset::getInstance()->addJs('/local/js/brakes/admin-payment-link-reload.js');
    }
});

AddEventHandler('main', 'OnAfterEpilog', static function (): void {
    brakes_1c_parse_try_fallback();
});
