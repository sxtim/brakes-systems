<?php

namespace App\Brakes\Helper;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Sale\Basket;
use Bitrix\Sale\BasketItemBase;
use Bitrix\Sale\Fuser;

class BasketManager
{
    private const IBLOCK_ID = 1;
    private const OPTION_PROP_CODE = 'OPTIONS_JSON';

    public static function addProduct(int $productId, float $quantity, array $context = [], array $options = []): array
    {
        if ($productId <= 0 || $quantity <= 0) {
            throw new SystemException('Invalid product or quantity.');
        }

        self::ensureModules();

        $contextData = self::normalizeContext($context, $options);
        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        $existing = self::findMatchingItem($basket, $productId, $contextData);

        if ($existing) {
            $existing->setField('QUANTITY', $existing->getQuantity() + $quantity);
            self::applyContextProperties($existing, $contextData);
            $basket->save();

            return self::getSummaryFromBasket($basket);
        }

        $item = $basket->createItem('catalog', $productId);
        $fields = [
            'QUANTITY' => $quantity,
            'LID' => SITE_ID,
            'PRODUCT_PROVIDER_CLASS' => \Bitrix\Catalog\Product\CatalogProvider::class,
        ];

        if (Loader::includeModule('currency')) {
            $fields['CURRENCY'] = \Bitrix\Currency\CurrencyManager::getBaseCurrency();
        }

        $item->setFields($fields);
        self::applyContextProperties($item, $contextData);
        $basket->save();

        return self::getSummaryFromBasket($basket);
    }

    public static function updateQuantity(int $basketItemId, float $quantity): array
    {
        if ($basketItemId <= 0 || $quantity <= 0) {
            throw new SystemException('Invalid basket item or quantity.');
        }

        self::ensureModules();

        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        $item = $basket->getItemById($basketItemId);
        if (!$item) {
            throw new SystemException('Basket item not found.');
        }

        $item->setField('QUANTITY', $quantity);
        $basket->save();

        return self::getSummaryFromBasket($basket);
    }

    public static function removeItem(int $basketItemId): array
    {
        if ($basketItemId <= 0) {
            throw new SystemException('Invalid basket item id.');
        }

        self::ensureModules();

        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        $item = $basket->getItemById($basketItemId);
        if (!$item) {
            throw new SystemException('Basket item not found.');
        }

        $item->delete();
        $basket->save();

        return self::getSummaryFromBasket($basket);
    }

    public static function getSummary(): array
    {
        try {
            self::ensureModules();
        } catch (SystemException) {
            return [
                'count' => 0,
                'positions' => 0,
            ];
        }

        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        return self::getSummaryFromBasket($basket);
    }

    private static function getSummaryFromBasket(Basket $basket): array
    {
        $count = 0.0;
        $positions = 0;

        foreach ($basket as $item) {
            $count += (float)$item->getQuantity();
            $positions++;
        }

        return [
            'count' => $count,
            'positions' => $positions,
        ];
    }

    private static function ensureModules(): void
    {
        if (!Loader::includeModule('sale') || !Loader::includeModule('catalog')) {
            throw new SystemException('Sale or catalog module not available.');
        }
    }

    private static function normalizeContext(array $context, array $options): array
    {
        $sectionId = isset($context['section_id']) ? (int)$context['section_id'] : 0;
        $sectionPath = isset($context['section_path']) ? (string)$context['section_path'] : '';
        $sectionPath = self::normalizePath($sectionPath);

        if ($sectionId <= 0 && $sectionPath !== '' && Loader::includeModule('iblock')) {
            $sectionId = (int)\CIBlockFindTools::GetSectionIDByCodePath(self::IBLOCK_ID, $sectionPath);
        }

        $contextLabel = isset($context['label']) ? trim((string)$context['label']) : '';
        if ($contextLabel === '' && $sectionId > 0 && Loader::includeModule('iblock')) {
            $contextLabel = self::resolveContextLabel($sectionId);
        }

        $optionsJson = '';
        if (!empty($options)) {
            $encoded = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (is_string($encoded)) {
                $optionsJson = $encoded;
            }
        }

        return [
            'section_id' => $sectionId,
            'section_path' => $sectionPath,
            'context_label' => $contextLabel,
            'options_json' => $optionsJson,
        ];
    }

    private static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        return trim($path, "/ \t\n\r\0\x0B");
    }

    private static function resolveContextLabel(int $sectionId): string
    {
        $names = [];
        $navChain = \CIBlockSection::GetNavChain(self::IBLOCK_ID, $sectionId, ['NAME']);
        while ($row = $navChain->Fetch()) {
            $name = isset($row['NAME']) ? trim((string)$row['NAME']) : '';
            if ($name !== '') {
                $names[] = $name;
            }
        }

        if ($names === []) {
            return '';
        }

        $names = array_slice($names, -3);
        return trim(implode(' ', $names));
    }

    private static function findMatchingItem(Basket $basket, int $productId, array $contextData): ?BasketItemBase
    {
        foreach ($basket as $item) {
            if ((int)$item->getProductId() !== $productId) {
                continue;
            }

            $signature = self::getItemSignature($item);
            if ($signature['section_id'] !== $contextData['section_id']) {
                continue;
            }
            if ($signature['section_path'] !== $contextData['section_path']) {
                continue;
            }
            if ($signature['options_json'] !== $contextData['options_json']) {
                continue;
            }

            return $item;
        }

        return null;
    }

    private static function getItemSignature(BasketItemBase $item): array
    {
        $collection = $item->getPropertyCollection();
        $getValue = static function (string $code) use ($collection): string {
            $prop = $collection?->getItemByCode($code);
            return $prop ? (string)$prop->getValue() : '';
        };

        return [
            'section_id' => (int)$getValue('CONTEXT_SECTION_ID'),
            'section_path' => self::normalizePath($getValue('CONTEXT_PATH')),
            'options_json' => $getValue(self::OPTION_PROP_CODE),
        ];
    }

    private static function applyContextProperties(BasketItemBase $item, array $contextData): void
    {
        $collection = $item->getPropertyCollection();
        if (!$collection) {
            return;
        }

        $properties = [];

        if ($contextData['section_id'] > 0) {
            $properties[] = [
                'NAME' => 'Context Section ID',
                'CODE' => 'CONTEXT_SECTION_ID',
                'VALUE' => (string)$contextData['section_id'],
                'SORT' => 100,
            ];
        }

        if ($contextData['section_path'] !== '') {
            $properties[] = [
                'NAME' => 'Context Section Path',
                'CODE' => 'CONTEXT_PATH',
                'VALUE' => $contextData['section_path'],
                'SORT' => 110,
            ];
        }

        if ($contextData['context_label'] !== '') {
            $properties[] = [
                'NAME' => 'Context Label',
                'CODE' => 'CONTEXT_LABEL',
                'VALUE' => $contextData['context_label'],
                'SORT' => 120,
            ];
        }

        if ($contextData['options_json'] !== '') {
            $properties[] = [
                'NAME' => 'Options',
                'CODE' => self::OPTION_PROP_CODE,
                'VALUE' => $contextData['options_json'],
                'SORT' => 130,
            ];
        }

        foreach ($properties as $property) {
            $collection->setProperty($property);
        }
    }
}
