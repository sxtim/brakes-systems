<?php

namespace App\Brakes\Helper;

use Bitrix\Main\Loader;

/**
 * Single source of truth for product stock/availability used across UI blocks.
 *
 * For now we rely on CCatalogProduct (single stock/global quantity).
 * If we later switch to multi-warehouse/reserves, this class is the only place to change.
 */
class StockProvider
{
    /**
     * @param array<int|string> $productIds
     * @return array<int, array{CATALOG_QUANTITY: ?float, CATALOG_AVAILABLE: mixed}>
     */
    public static function getMap(array $productIds): array
    {
        $ids = [];
        foreach ($productIds as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = true;
            }
        }
        $ids = array_keys($ids);
        if ($ids === []) {
            return [];
        }

        if (!Loader::includeModule('catalog')) {
            return [];
        }

        if (!class_exists('CCatalogProduct')) {
            return [];
        }

        $map = [];
        $res = \CCatalogProduct::GetList(
            [],
            ['@ID' => $ids],
            false,
            false,
            ['ID', 'QUANTITY', 'AVAILABLE']
        );

        while ($row = $res->Fetch()) {
            $id = (int)($row['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $map[$id] = [
                'CATALOG_QUANTITY' => isset($row['QUANTITY']) ? (float)$row['QUANTITY'] : null,
                'CATALOG_AVAILABLE' => $row['AVAILABLE'] ?? null,
            ];
        }

        return $map;
    }
}

