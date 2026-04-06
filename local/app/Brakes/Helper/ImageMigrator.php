<?php

namespace App\Brakes\Helper;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class ImageMigrator
{
    public const IBLOCK_ID = 1;
    public const SOURCE_PROPERTY_CODE = 'LINK_PHOTO';
    public const TARGET_PROPERTY_CODE = 'LINK_PHOTO_FILE';
    public const SOURCE_DEFAULT_ROOT = '/fotoz';

    private static bool $internalUpdate = false;

    private static function ensureModule(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        if (!Loader::includeModule('iblock')) {
            throw new SystemException('iblock module is not available');
        }
        $loaded = true;
    }

    /**
     * @param int $elementId
     * @param array{force?:bool,dryRun?:bool,deleteSource?:bool} $options
     *
     * @return array{
     *     status: string,
     *     elementId: int,
     *     processed: int,
     *     errors: array<int,string>,
     *     skippedReason?: string
     * }
     */
    public static function migrateElement(int $elementId, array $options = []): array
    {
        self::ensureModule();

        $force = !empty($options['force']);
        $dryRun = !empty($options['dryRun']);
        $deleteSource = !empty($options['deleteSource']);

        $elementId = max(0, $elementId);
        if ($elementId <= 0) {
            return [
                'status' => 'error',
                'elementId' => $elementId,
                'processed' => 0,
                'errors' => ['Invalid element id'],
            ];
        }

        if (self::$internalUpdate) {
            return [
                'status' => 'skipped',
                'skippedReason' => 'internal_update',
                'elementId' => $elementId,
                'processed' => 0,
                'errors' => [],
            ];
        }

        $sourcePaths = self::getSourcePaths($elementId);
        if (empty($sourcePaths)) {
            return [
                'status' => 'skipped',
                'skippedReason' => 'empty_source',
                'elementId' => $elementId,
                'processed' => 0,
                'errors' => [],
            ];
        }

        $targetRows = self::getTargetRows($elementId);
        if (!$force) {
            $targetIds = array_filter(array_map(
                static fn(array $row): int => (int)($row['fileId'] ?? 0),
                $targetRows
            ));
            if (!empty($targetIds)) {
                return [
                    'status' => 'skipped',
                    'skippedReason' => 'already_filled',
                    'elementId' => $elementId,
                    'processed' => 0,
                    'errors' => [],
                ];
            }
        }

        $fileValues = [];
        $sourceFiles = [];
        $errors = [];

        foreach ($sourcePaths as $path) {
            $normalized = self::normalizePath($path);
            if ($normalized === null) {
                $errors[] = sprintf('Skip invalid path: %s', $path);
                continue;
            }

            $absolute = self::absolutePath($normalized);
            if (!is_file($absolute)) {
                $errors[] = sprintf('File not found: %s', $normalized);
                continue;
            }

            $fileArray = \CFile::MakeFileArray($absolute);
            if (!is_array($fileArray)) {
                $errors[] = sprintf('Failed to prepare file array: %s', $absolute);
                continue;
            }

            $fileArray['MODULE_ID'] = 'iblock';
            $index = 'n' . count($fileValues);
            $fileValues[$index] = [
                'VALUE' => $fileArray,
                'DESCRIPTION' => '',
            ];

            $sourceFiles[] = $absolute;
        }

        if (empty($fileValues)) {
            return [
                'status' => $dryRun ? 'dry-run' : 'skipped',
                'skippedReason' => 'no_valid_files',
                'elementId' => $elementId,
                'processed' => 0,
                'errors' => $errors,
            ];
        }

        if ($dryRun) {
            return [
                'status' => 'dry-run',
                'elementId' => $elementId,
                'processed' => count($fileValues),
                'errors' => $errors,
            ];
        }

        self::$internalUpdate = true;
        try {
            $propertyValue = self::buildReplacementPropertyValue($targetRows, $fileValues);
            \CIBlockElement::SetPropertyValueCode(
                $elementId,
                self::TARGET_PROPERTY_CODE,
                $propertyValue
            );
        } catch (\Throwable $exception) {
            self::$internalUpdate = false;
            return [
                'status' => 'error',
                'elementId' => $elementId,
                'processed' => 0,
                'errors' => array_merge($errors, [$exception->getMessage()]),
            ];
        }
        self::$internalUpdate = false;

        if ($deleteSource) {
            foreach ($sourceFiles as $absolute) {
                @unlink($absolute);
            }
        }

        return [
            'status' => 'migrated',
            'elementId' => $elementId,
            'processed' => count($fileValues),
            'errors' => $errors,
        ];
    }

    /**
     * @param array{
     *     startId?:int,
     *     chunk?:int,
     *     limit?:int,
     *     force?:bool,
     *     dryRun?:bool,
     *     deleteSource?:bool,
     * } $options
     *
     * @return array{
     *     processed:int,
     *     migrated:int,
     *     skipped:int,
     *     errors: array<int, string>,
     *     dryRun: bool
     * }
     */
    public static function migrateAll(array $options = []): array
    {
        self::ensureModule();

        $startId = isset($options['startId']) ? max(0, (int)$options['startId']) : 0;
        $chunk = isset($options['chunk']) ? max(1, (int)$options['chunk']) : 200;
        $limit = isset($options['limit']) ? max(0, (int)$options['limit']) : 0;
        $force = !empty($options['force']);
        $dryRun = !empty($options['dryRun']);
        $deleteSource = !empty($options['deleteSource']);

        $processed = 0;
        $migrated = 0;
        $skipped = 0;
        $errors = [];
        $lastId = $startId;

        while (true) {
            $batchCount = 0;
            $res = \CIBlockElement::GetList(
                ['ID' => 'ASC'],
                [
                    'IBLOCK_ID' => self::IBLOCK_ID,
                    '>ID' => $lastId,
                ],
                false,
                ['nTopCount' => $chunk],
                ['ID']
            );

            while ($row = $res->Fetch()) {
                $batchCount++;
                $lastId = (int)$row['ID'];
                $processed++;

                $result = self::migrateElement(
                    $lastId,
                    [
                        'force' => $force,
                        'dryRun' => $dryRun,
                        'deleteSource' => $deleteSource,
                    ]
                );

                if ($result['status'] === 'migrated') {
                    $migrated++;
                } elseif ($result['status'] === 'error') {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $skipped++;
                }

                if ($limit > 0 && $processed >= $limit) {
                    break 2;
                }
            }

            if ($batchCount === 0) {
                break;
            }
        }

        return [
            'processed' => $processed,
            'migrated' => $migrated,
            'skipped' => $skipped,
            'errors' => array_values(array_filter($errors)),
            'dryRun' => $dryRun,
        ];
    }

    private static function getSourcePaths(int $elementId): array
    {
        $values = [];

        $res = \CIBlockElement::GetProperty(
            self::IBLOCK_ID,
            $elementId,
            ['sort' => 'asc'],
            ['CODE' => self::SOURCE_PROPERTY_CODE]
        );

        while ($row = $res->Fetch()) {
            if (!isset($row['VALUE'])) {
                continue;
            }

            $value = trim((string)$row['VALUE']);
            if ($value === '') {
                continue;
            }

            if (str_contains($value, ';')) {
                $chunks = array_map('trim', explode(';', $value));
                foreach ($chunks as $chunk) {
                    if ($chunk !== '') {
                        $values[] = $chunk;
                    }
                }
            } else {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    private static function getTargetRows(int $elementId): array
    {
        $values = [];

        $res = \CIBlockElement::GetProperty(
            self::IBLOCK_ID,
            $elementId,
            ['sort' => 'asc'],
            ['CODE' => self::TARGET_PROPERTY_CODE]
        );

        while ($row = $res->Fetch()) {
            $values[] = [
                'fileId' => (int)($row['VALUE'] ?? 0),
                'valueId' => (int)($row['PROPERTY_VALUE_ID'] ?? 0),
            ];
        }

        return $values;
    }

    /**
     * Replace the whole multiple file property in a single Bitrix call:
     * mark all current values for deletion and append the new file arrays.
     *
     * @param array<int, array{fileId:int, valueId:int}> $targetRows
     * @param array<string, array{VALUE: array<string, mixed>, DESCRIPTION: string}> $fileValues
     *
     * @return array<int|string, array<string, mixed>>
     */
    private static function buildReplacementPropertyValue(array $targetRows, array $fileValues): array
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

    private static function normalizePath(string $path): ?string
    {
        $parsed = parse_url($path, PHP_URL_PATH);
        $normalized = is_string($parsed) && $parsed !== '' ? $parsed : trim($path);

        if ($normalized === '' || str_contains($normalized, '..')) {
            return null;
        }

        if ($normalized[0] !== '/') {
            $normalized = rtrim(self::SOURCE_DEFAULT_ROOT, '/') . '/' . ltrim($normalized, '/');
        }

        return $normalized;
    }

    private static function absolutePath(string $path): string
    {
        $root = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        return $root . $path;
    }
}
