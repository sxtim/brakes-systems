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

    private static ?string $entityClass = null;

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

    public static function getUserProductIds(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        $className = self::getEntityClass();

        $result = $className::getList([
            'select' => [self::FIELD_PRODUCT_ID],
            'filter' => [self::FIELD_USER_ID => $userId],
        ]);

        $ids = [];
        while ($row = $result->fetch()) {
            $productId = (int)($row[self::FIELD_PRODUCT_ID] ?? 0);
            if ($productId > 0) {
                $ids[] = $productId;
            }
        }

        sort($ids);

        return array_values(array_unique($ids));
    }

    public static function addProduct(int $userId, int $productId): bool
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
            return true;
        }

        $result = $className::add([
            self::FIELD_USER_ID => $userId,
            self::FIELD_PRODUCT_ID => $productId,
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

    public static function mergeFavorites(int $userId, array $productIds): array
    {
        if ($userId <= 0) {
            return [];
        }

        $cleanIds = self::normalizeProductIds($productIds);
        if (empty($cleanIds)) {
            return self::getUserProductIds($userId);
        }

        $className = self::getEntityClass();

        $existing = self::getUserProductIds($userId);
        $existingMap = array_flip($existing);

        foreach ($cleanIds as $productId) {
            if (isset($existingMap[$productId])) {
                continue;
            }

            $result = $className::add([
                self::FIELD_USER_ID => $userId,
                self::FIELD_PRODUCT_ID => $productId,
            ]);

            if ($result->isSuccess()) {
                $existingMap[$productId] = true;
                $existing[] = $productId;
            }
        }

        sort($existing);

        return array_values($existing);
    }

    public static function normalizeProductIds(array $productIds): array
    {
        $normalized = [];

        foreach ($productIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $normalized[$id] = true;
            }
        }

        return array_keys($normalized);
    }
}
