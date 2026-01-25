<?php

namespace App\Brakes\Helper;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use App\Brakes\Pricing\Configurator;
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
            self::syncCustomPrice($existing, $options);
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
        self::syncCustomPrice($item, $options);
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

        if ($properties !== []) {
            $collection->setProperty($properties);
        }
    }

    public static function syncCustomPrice(BasketItemBase $item, ?array $options = null): void
    {
        $productId = (int)$item->getProductId();
        if ($productId <= 0) {
            return;
        }

        $optionsMap = $options ?? self::extractOptionsFromItem($item);
        $normalized = self::normalizeOptionsMap($optionsMap);
        if ($normalized === []) {
            return;
        }

        $priceData = self::calculatePriceData($productId, $normalized);
        if ($priceData === null) {
            return;
        }

        $price = (float)$priceData['PRICE'];
        $currency = (string)$priceData['CURRENCY'];
        if ($price <= 0.0 || $currency === '') {
            return;
        }

        $currentPrice = (float)$item->getField('PRICE');
        $currentCurrency = (string)$item->getField('CURRENCY');
        if (
            $item->getField('CUSTOM_PRICE') === 'Y'
            && abs($currentPrice - $price) < 0.0001
            && $currentCurrency === $currency
        ) {
            return;
        }

        $item->setField('CUSTOM_PRICE', 'Y');
        $item->setField('PRICE', $price);
        $item->setField('CURRENCY', $currency);
    }

    private static function extractOptionsFromItem(BasketItemBase $item): array
    {
        $collection = $item->getPropertyCollection();
        if (!$collection) {
            return [];
        }

        $prop = $collection->getItemByCode(self::OPTION_PROP_CODE);
        if (!$prop) {
            return [];
        }

        $raw = (string)$prop->getValue();
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function normalizeOptionsMap(array $options): array
    {
        if (isset($options['options']) && is_array($options['options'])) {
            $options = $options['options'];
        }

        $normalized = [];
        foreach ($options as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }
            if (is_array($value)) {
                $value = $value['value'] ?? $value['VALUE'] ?? reset($value);
            }
            if (!is_scalar($value)) {
                continue;
            }
            $normalized[$key] = $value;
        }

        return $normalized;
    }

    private static function calculatePriceData(int $productId, array $options): ?array
    {
        try {
            $price = Configurator::calculate($productId, ['options' => $options]);
            if (is_array($price)) {
                $value = null;
                if (isset($price['DISCOUNT_PRICE']) && is_numeric($price['DISCOUNT_PRICE'])) {
                    $value = (float)$price['DISCOUNT_PRICE'];
                } elseif (isset($price['BASE_PRICE']) && is_numeric($price['BASE_PRICE'])) {
                    $value = (float)$price['BASE_PRICE'];
                }
                $currency = isset($price['CURRENCY']) ? (string)$price['CURRENCY'] : 'RUB';
                if ($value !== null) {
                    return [
                        'PRICE' => $value,
                        'CURRENCY' => $currency,
                    ];
                }
            }
        } catch (\Throwable $exception) {
            // fallback to base price
        }

        if (!Loader::includeModule('catalog')) {
            return null;
        }

        $base = \CPrice::GetBasePrice($productId);
        if (is_array($base) && isset($base['PRICE'])) {
            return [
                'PRICE' => (float)$base['PRICE'],
                'CURRENCY' => isset($base['CURRENCY']) ? (string)$base['CURRENCY'] : 'RUB',
            ];
        }

        return null;
    }
}
