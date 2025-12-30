<?php

namespace App\Brakes\Helper;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;

class Favorites
{
    private const TABLE_NAME = 'app_favorites';
    private const FIELD_USER_ID = 'UF_USER_ID';
    private const FIELD_PRODUCT_ID = 'UF_PRODUCT_ID';
    private const FIELD_OPTIONS = 'UF_OPTIONS';

    private static ?string $entityClass = null;
    private static ?bool $hasOptionsField = null;

    protected static function getEntityClass(): string
    {
        if (self::$entityClass !== null) {
            return self::$entityClass;
        }

        if (!Loader::includeModule('highloadblock')) {
            throw new SystemException('Highload module is not available.');
        }

        $hlBlock = HighloadBlockTable::getRow([
            'filter' => ['=TABLE_NAME' => self::TABLE_NAME],
        ]);

        if ($hlBlock === null) {
            throw new SystemException(sprintf('Highload block %s not found.', self::TABLE_NAME));
        }

        self::$entityClass = HighloadBlockTable::compileEntity($hlBlock)->getDataClass();

        return self::$entityClass;
    }

    private static function hasOptionsField(): bool
    {
        if (self::$hasOptionsField !== null) {
            return self::$hasOptionsField;
        }

        $className = self::getEntityClass();
        $entity = $className::getEntity();
        self::$hasOptionsField = $entity->hasField(self::FIELD_OPTIONS);

        return self::$hasOptionsField;
    }

    private static function encodeOptions(array $options): ?string
    {
        if ($options === []) {
            return null;
        }

        return json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function decodeOptions($value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    private static function buildRow(array $row): array
    {
        $productId = (int)($row[self::FIELD_PRODUCT_ID] ?? 0);

        return [
            'ID' => (int)($row['ID'] ?? 0),
            'PRODUCT_ID' => $productId,
            'OPTIONS' => self::decodeOptions($row[self::FIELD_OPTIONS] ?? null),
            'RAW' => $row,
        ];
    }

    public static function getUserProducts(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $className = self::getEntityClass();
        $select = ['ID', self::FIELD_PRODUCT_ID];
        if (self::hasOptionsField()) {
            $select[] = self::FIELD_OPTIONS;
        }

        $result = $className::getList([
            'select' => $select,
            'filter' => [self::FIELD_USER_ID => $userId],
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $rowId = (int)($row['ID'] ?? 0);
            $productId = (int)($row[self::FIELD_PRODUCT_ID] ?? 0);
            if ($rowId <= 0 || $productId <= 0) {
                continue;
            }

            $items[$rowId] = self::buildRow($row);
        }

        ksort($items);

        return $items;
    }

    public static function getUserProductIds(int $userId): array
    {
        $items = self::getUserProducts($userId);
        $ids = [];
        foreach ($items as $row) {
            $productId = (int)($row['PRODUCT_ID'] ?? 0);
            if ($productId > 0) {
                $ids[$productId] = true;
            }
        }

        return array_keys($ids);
    }

    public static function addProduct(int $userId, int $productId, array $options = []): int
    {
        if ($userId <= 0 || $productId <= 0) {
            return 0;
        }

        $className = self::getEntityClass();
        $normalizedPayload = self::normalizePayload($options);
        $context = $normalizedPayload['context'];
        $favoriteKey = self::buildFavoriteKey($productId, $context);

        $existingRows = $className::getList([
            'select' => ['ID', self::FIELD_OPTIONS],
            'filter' => [
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
            ],
        ]);

        while ($row = $existingRows->fetch()) {
            $rowId = (int)($row['ID'] ?? 0);
            if ($rowId <= 0) {
                continue;
            }
            $existingContext = self::extractContext(self::decodeOptions($row[self::FIELD_OPTIONS] ?? null));
            $existingKey = self::buildFavoriteKey($productId, $existingContext);
            if ($existingKey !== '' && $existingKey === $favoriteKey) {
                if ($normalizedPayload !== [] && self::hasOptionsField()) {
                    $className::update($rowId, [
                        self::FIELD_OPTIONS => self::encodeOptions($normalizedPayload),
                    ]);
                }
                return $rowId;
            }
        }

        $result = $className::add([
            self::FIELD_USER_ID => $userId,
            self::FIELD_PRODUCT_ID => $productId,
            self::FIELD_OPTIONS => self::hasOptionsField() ? self::encodeOptions($normalizedPayload) : null,
        ]);

        return $result->isSuccess() ? (int)$result->getId() : 0;
    }

    public static function removeProduct(int $userId, int $productId): bool
    {
        if ($userId <= 0 || $productId <= 0) {
            return false;
        }

        $className = self::getEntityClass();

        $rows = $className::getList([
            'select' => ['ID'],
            'filter' => [
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
            ],
        ]);

        $removed = false;

        while ($row = $rows->fetch()) {
            $className::delete((int)$row['ID']);
            $removed = true;
        }

        return $removed;
    }

    public static function removeRow(int $userId, int $rowId): bool
    {
        if ($userId <= 0 || $rowId <= 0) {
            return false;
        }

        $className = self::getEntityClass();

        $row = $className::getList([
            'select' => ['ID'],
            'filter' => [
                'ID' => $rowId,
                self::FIELD_USER_ID => $userId,
            ],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return false;
        }

        $className::delete((int)$row['ID']);

        return true;
    }

    public static function setRowOptions(int $userId, int $rowId, array $options): bool
    {
        if ($userId <= 0 || $rowId <= 0 || !self::hasOptionsField()) {
            return false;
        }

        $className = self::getEntityClass();

        $row = $className::getList([
            'select' => ['ID'],
            'filter' => [
                'ID' => $rowId,
                self::FIELD_USER_ID => $userId,
            ],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return false;
        }

        $normalizedPayload = self::normalizePayload($options);
        $result = $className::update((int)$row['ID'], [
            self::FIELD_OPTIONS => self::encodeOptions($normalizedPayload),
        ]);

        return $result->isSuccess();
    }

    public static function setProductOptions(int $userId, int $productId, array $options): bool
    {
        if ($userId <= 0 || $productId <= 0 || !self::hasOptionsField()) {
            return false;
        }

        $className = self::getEntityClass();

        $row = $className::getList([
            'select' => ['ID'],
            'filter' => [
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
            ],
            'limit' => 1,
        ])->fetch();

        if (!$row) {
            return false;
        }

        $normalizedPayload = self::normalizePayload($options);
        $result = $className::update((int)$row['ID'], [
            self::FIELD_OPTIONS => self::encodeOptions($normalizedPayload),
        ]);

        return $result->isSuccess();
    }

    public static function mergeFavorites(int $userId, array $items): array
    {
        if ($userId <= 0) {
            return [];
        }

        $normalized = self::normalizeFavoriteItems($items);
        if ($normalized === []) {
            $existingRows = self::getUserProducts($userId);
            $existingKeys = [];
            foreach ($existingRows as $row) {
                $productId = (int)($row['PRODUCT_ID'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                $context = self::extractContext($row['OPTIONS'] ?? []);
                $key = self::buildFavoriteKey($productId, $context);
                if ($key !== '') {
                    $existingKeys[$key] = true;
                }
            }

            return array_keys($existingKeys);
        }

        $className = self::getEntityClass();

        $existingRows = self::getUserProducts($userId);
        $existingMap = [];
        foreach ($existingRows as $row) {
            $productId = (int)($row['PRODUCT_ID'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $context = self::extractContext($row['OPTIONS'] ?? []);
            $key = self::buildFavoriteKey($productId, $context);
            if ($key !== '') {
                $existingMap[$key] = (int)($row['ID'] ?? 0);
            }
        }

        foreach ($normalized as $key => $data) {
            $productId = (int)($data['productId'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            if (isset($existingMap[$key])) {
                if (!empty($data['options']) && self::hasOptionsField()) {
                    self::setRowOptions($userId, (int)$existingMap[$key], $data['options']);
                }
                continue;
            }

            $result = $className::add([
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
                self::FIELD_OPTIONS => self::hasOptionsField() ? self::encodeOptions($data['options']) : null,
            ]);

            if ($result->isSuccess()) {
                $existingMap[$key] = (int)$result->getId();
            }
        }

        ksort($existingMap);

        return array_keys($existingMap);
    }

    public static function normalizeFavoriteItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $key => $item) {
            $productId = null;
            $options = [];
            $context = [];

            if (is_array($item)) {
                if (isset($item['PRODUCT_ID'])) {
                    $productId = (int)$item['PRODUCT_ID'];
                } elseif (isset($item['id'])) {
                    $productId = (int)$item['id'];
                } elseif (isset($item['ID'])) {
                    $productId = (int)$item['ID'];
                } elseif (is_int($key)) {
                    $productId = (int)$key;
                }

                if (isset($item['options']) && is_array($item['options'])) {
                    $options = $item['options'];
                } elseif (isset($item['OPTIONS']) && is_array($item['OPTIONS'])) {
                    $options = $item['OPTIONS'];
                }

                if (isset($item['context']) && is_array($item['context'])) {
                    $context = $item['context'];
                } elseif (isset($item['CONTEXT']) && is_array($item['CONTEXT'])) {
                    $context = $item['CONTEXT'];
                }
            } else {
                $productId = (int)$item;
            }

            if ($productId <= 0) {
                continue;
            }

            $payload = self::normalizePayload(['options' => $options, 'context' => $context]);
            $context = $payload['context'];
            $favoriteKey = self::buildFavoriteKey($productId, $context);

            if ($favoriteKey === '') {
                $favoriteKey = (string)$productId;
            }

            $normalized[$favoriteKey] = [
                'productId' => $productId,
                'options' => $payload,
                'context' => $context,
            ];
        }

        ksort($normalized);

        return $normalized;
    }

    private static function normalizePayload(array $payload): array
    {
        $options = [];
        if (isset($payload['options']) && is_array($payload['options'])) {
            $options = $payload['options'];
        } elseif (!empty($payload) && !array_key_exists('options', $payload)) {
            $options = $payload;
        }

        $context = self::normalizeContext($payload['context'] ?? []);

        return [
            'options' => $options,
            'context' => $context,
        ];
    }

    private static function normalizeContext($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $sectionId = isset($value['section_id']) ? (int)$value['section_id'] : (isset($value['sectionId']) ? (int)$value['sectionId'] : 0);
        $sectionPath = isset($value['section_path']) ? (string)$value['section_path'] : (isset($value['sectionPath']) ? (string)$value['sectionPath'] : '');

        $context = [];
        if ($sectionId > 0) {
            $context['section_id'] = $sectionId;
        }
        if ($sectionPath !== '') {
            $sectionPath = trim((string)$sectionPath, " \t\n\r\0\x0B/");
            if ($sectionPath !== '') {
                $context['section_path'] = $sectionPath;
            }
        }

        return $context;
    }

    private static function extractContext($options): array
    {
        if (!is_array($options)) {
            return [];
        }

        if (isset($options['context']) && is_array($options['context'])) {
            return self::normalizeContext($options['context']);
        }

        return self::normalizeContext($options);
    }

    private static function buildFavoriteKey(int $productId, array $context): string
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return '';
        }

        $sectionId = isset($context['section_id']) ? (int)$context['section_id'] : 0;
        $sectionPath = isset($context['section_path']) ? (string)$context['section_path'] : '';

        if ($sectionId > 0) {
            return $productId . ':s' . $sectionId;
        }

        $sectionPath = trim($sectionPath, " \t\n\r\0\x0B/");
        if ($sectionPath !== '') {
            return $productId . ':p' . $sectionPath;
        }

        return $productId . ':n';
    }

    public static function normalizeProductIds(array $productIds): array
    {
        $normalized = [];

        foreach ($productIds as $key => $id) {
            if (is_array($id)) {
                if (isset($id['PRODUCT_ID'])) {
                    $id = $id['PRODUCT_ID'];
                } elseif (isset($id['id'])) {
                    $id = $id['id'];
                } elseif (isset($id['ID'])) {
                    $id = $id['ID'];
                } elseif (is_int($key)) {
                    $id = $key;
                }
            }

            $id = (int)$id;
            if ($id > 0) {
                $normalized[$id] = true;
            }
        }

        return array_keys($normalized);
    }
}
