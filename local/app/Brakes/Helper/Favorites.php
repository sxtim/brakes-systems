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
        $select = [self::FIELD_PRODUCT_ID];
        if (self::hasOptionsField()) {
            $select[] = self::FIELD_OPTIONS;
        }

        $result = $className::getList([
            'select' => $select,
            'filter' => [self::FIELD_USER_ID => $userId],
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $productId = (int)($row[self::FIELD_PRODUCT_ID] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $items[$productId] = self::buildRow($row);
        }

        ksort($items);

        return $items;
    }

    public static function getUserProductIds(int $userId): array
    {
        $items = self::getUserProducts($userId);
        return array_keys($items);
    }

    public static function addProduct(int $userId, int $productId, array $options = []): bool
    {
        if ($userId <= 0 || $productId <= 0) {
            return false;
        }

        $className = self::getEntityClass();

        $exists = $className::getList([
            'select' => ['ID'],
            'filter' => [
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
            ],
            'limit' => 1,
        ])->fetch();

        if ($exists) {
            if ($options !== [] && self::hasOptionsField()) {
                $className::update((int)$exists['ID'], [
                    self::FIELD_OPTIONS => self::encodeOptions($options),
                ]);
            }
            return true;
        }

        $result = $className::add([
            self::FIELD_USER_ID => $userId,
            self::FIELD_PRODUCT_ID => $productId,
            self::FIELD_OPTIONS => self::hasOptionsField() ? self::encodeOptions($options) : null,
        ]);

        return $result->isSuccess();
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

        $result = $className::update((int)$row['ID'], [
            self::FIELD_OPTIONS => self::encodeOptions($options),
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
            return self::getUserProductIds($userId);
        }

        $className = self::getEntityClass();

        $existing = self::getUserProductIds($userId);
        $existingMap = array_flip($existing);

        foreach ($normalized as $productId => $data) {
            if (isset($existingMap[$productId])) {
                if ($data['options'] !== [] && self::hasOptionsField()) {
                    self::setProductOptions($userId, $productId, $data['options']);
                }
                continue;
            }

            $result = $className::add([
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
                self::FIELD_OPTIONS => self::hasOptionsField() ? self::encodeOptions($data['options']) : null,
            ]);

            if ($result->isSuccess()) {
                $existingMap[$productId] = true;
                $existing[] = $productId;
            }
        }

        sort($existing);

        return array_values($existing);
    }

    public static function normalizeFavoriteItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $key => $item) {
            $productId = null;
            $options = [];

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
            } else {
                $productId = (int)$item;
            }

            if ($productId <= 0) {
                continue;
            }

            $normalized[$productId] = [
                'options' => $options,
            ];
        }

        ksort($normalized);

        return $normalized;
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
