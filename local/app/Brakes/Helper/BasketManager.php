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
            self::applyDisplayProperties($existing, $options);
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
        self::applyDisplayProperties($item, $options);
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
            if (!$collection) {
                return '';
            }

            if (method_exists($collection, 'getItemByCode')) {
                $prop = $collection->getItemByCode($code);
                return $prop ? (string)$prop->getValue() : '';
            }

            if (method_exists($collection, 'getPropertyValues')) {
                $values = $collection->getPropertyValues();
                if (is_array($values) && array_key_exists($code, $values)) {
                    return self::normalizePropertyValue($values[$code]);
                }
            }

            if (method_exists($collection, 'getArray')) {
                $data = $collection->getArray();
                if (is_array($data) && !empty($data['PROPS']) && is_array($data['PROPS'])) {
                    foreach ($data['PROPS'] as $prop) {
                        if (!is_array($prop)) {
                            continue;
                        }
                        if ((string)($prop['CODE'] ?? '') === $code) {
                            return self::normalizePropertyValue($prop['VALUE'] ?? '');
                        }
                    }
                }
            }

            return '';
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
                'VALUE' => self::normalizePropertyValue($contextData['section_id']),
                'SORT' => 100,
            ];
        }

        if ($contextData['section_path'] !== '') {
            $properties[] = [
                'NAME' => 'Context Section Path',
                'CODE' => 'CONTEXT_PATH',
                'VALUE' => self::normalizePropertyValue($contextData['section_path']),
                'SORT' => 110,
            ];
        }

        if ($contextData['context_label'] !== '') {
            $properties[] = [
                'NAME' => 'Context Label',
                'CODE' => 'CONTEXT_LABEL',
                'VALUE' => self::normalizePropertyValue($contextData['context_label']),
                'SORT' => 120,
            ];
        }

        if ($contextData['options_json'] !== '') {
            $properties[] = [
                'NAME' => 'Options',
                'CODE' => self::OPTION_PROP_CODE,
                'VALUE' => self::normalizePropertyValue($contextData['options_json']),
                'SORT' => 130,
            ];
        }

        if ($properties !== []) {
            $collection->setProperty($properties);
        }
    }

    private static function applyDisplayProperties(BasketItemBase $item, array $options = []): void
    {
        $collection = $item->getPropertyCollection();
        if (!$collection) {
            return;
        }

        $productId = (int)$item->getProductId();
        if ($productId <= 0) {
            return;
        }

        if ($options === []) {
            $options = self::extractOptionsFromItem($item);
        }

        $properties = self::buildDisplayProperties($productId, $options);
        if ($properties === []) {
            return;
        }

        $preserveCodes = [
            'CONTEXT_SECTION_ID' => true,
            'CONTEXT_PATH' => true,
            'CONTEXT_LABEL' => true,
            self::OPTION_PROP_CODE => true,
        ];
        $merged = [];

        if (method_exists($collection, 'getArray')) {
            $data = $collection->getArray();
            if (is_array($data) && !empty($data['PROPS']) && is_array($data['PROPS'])) {
                foreach ($data['PROPS'] as $prop) {
                    if (!is_array($prop)) {
                        continue;
                    }
                    $code = (string)($prop['CODE'] ?? '');
                    if ($code === '' || !isset($preserveCodes[$code])) {
                        continue;
                    }
                    $value = self::normalizePropertyValue($prop['VALUE'] ?? '');
                    $merged[$code] = [
                        'NAME' => (string)($prop['NAME'] ?? $code),
                        'CODE' => $code,
                        'VALUE' => $value,
                        'SORT' => (int)($prop['SORT'] ?? 0),
                    ];
                }
            }
        } elseif (method_exists($collection, 'getPropertyValues')) {
            $values = $collection->getPropertyValues();
            if (is_array($values)) {
                foreach ($values as $code => $value) {
                    if (!is_string($code) || $code === '' || !isset($preserveCodes[$code])) {
                        continue;
                    }
                    $finalValue = self::normalizePropertyValue($value);
                    $merged[$code] = [
                        'NAME' => $code,
                        'CODE' => $code,
                        'VALUE' => $finalValue,
                        'SORT' => 0,
                    ];
                }
            }
        }

        foreach ($properties as $property) {
            if (!is_array($property)) {
                continue;
            }
            $code = (string)($property['CODE'] ?? '');
            if ($code === '') {
                continue;
            }
            if (array_key_exists('VALUE', $property)) {
                $property['VALUE'] = self::normalizePropertyValue($property['VALUE']);
            }
            $merged[$code] = $property;
        }

        $collection->setProperty(array_values($merged));
    }

    private static function buildDisplayProperties(int $productId, array $options): array
    {
        $properties = [];

        $article = self::getProductPropertyValue($productId, 'CML2_ARTICLE');
        if ($article !== '') {
            $properties[] = [
                'NAME' => 'Артикул',
                'CODE' => 'DISPLAY_ARTICLE',
                'VALUE' => $article,
                'SORT' => 200,
            ];
        }

        $manufacturer = self::getProductPropertyValue($productId, 'MANUFACTURER');
        if ($manufacturer === '') {
            $manufacturer = self::getProductPropertyValue($productId, 'CML2_MANUFACTURER');
        }
        if ($manufacturer !== '') {
            $properties[] = [
                'NAME' => 'Производитель',
                'CODE' => 'DISPLAY_MANUFACTURER',
                'VALUE' => $manufacturer,
                'SORT' => 210,
            ];
        }

        $pistons = self::getProductPropertyValue($productId, 'NUMBER_PISTONS');
        if ($pistons !== '') {
            $properties[] = [
                'NAME' => 'Кол-во поршней',
                'CODE' => 'DISPLAY_PISTONS',
                'VALUE' => $pistons,
                'SORT' => 220,
            ];
        }

        $axis = self::getProductPropertyValue($productId, 'INSTALLATION_AXIS');
        if ($axis !== '') {
            $properties[] = [
                'NAME' => 'Ось',
                'CODE' => 'DISPLAY_AXIS',
                'VALUE' => $axis,
                'SORT' => 230,
            ];
        }

        $optionsMap = self::normalizeOptionsMap($options);
        if ($optionsMap !== []) {
            $twoPiece = isset($optionsMap['two_piece_disc_construction'])
                ? (string)$optionsMap['two_piece_disc_construction']
                : '';
            if ($twoPiece !== '') {
                $properties[] = [
                    'NAME' => 'Плавающая конструкция диска',
                    'CODE' => 'DISPLAY_TWO_PIECE',
                    'VALUE' => $twoPiece === 'yes' ? 'Да' : 'Нет',
                    'SORT' => 240,
                ];
            }

            $rotor = isset($optionsMap['rotor_pattern']) ? (string)$optionsMap['rotor_pattern'] : '';
            $rotorValue = self::formatRotorPattern($rotor);
            if ($rotorValue !== '') {
                $properties[] = [
                    'NAME' => 'Тип ротора',
                    'CODE' => 'DISPLAY_ROTOR',
                    'VALUE' => $rotorValue,
                    'SORT' => 250,
                ];
            }

            $caliper = isset($optionsMap['caliper_logo']) ? (string)$optionsMap['caliper_logo'] : '';
            $caliperValue = self::formatCaliperLogo($caliper);
            if ($caliperValue !== '') {
                $properties[] = [
                    'NAME' => 'Лого на суппорт',
                    'CODE' => 'DISPLAY_CALIPER_LOGO',
                    'VALUE' => $caliperValue,
                    'SORT' => 260,
                ];
            }
        }

        return $properties;
    }

    private static function getProductPropertyValue(int $productId, string $code): string
    {
        if (!Loader::includeModule('iblock')) {
            return '';
        }

        $value = '';
        $propertyIterator = \CIBlockElement::GetProperty(
            self::IBLOCK_ID,
            $productId,
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            ['CODE' => $code]
        );

        while ($property = $propertyIterator->Fetch()) {
            $propValue = $property['VALUE'] ?? '';
            if (is_array($propValue)) {
                $propValue = implode(', ', array_filter($propValue, 'strlen'));
            }
            if (!is_string($propValue)) {
                $propValue = (string)$propValue;
            }
            $propValue = trim($propValue);
            if ($propValue !== '') {
                $value = $propValue;
                break;
            }
        }

        return $value;
    }

    private static function formatRotorPattern(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'none' => 'Нет',
            'perforation' => 'Перфорация',
            'slots', 'notches' => 'Насечки',
            'perforation_slots', 'perforation_and_notches' => 'Перфорация + насечки',
            default => $value,
        };
    }

    private static function formatCaliperLogo(string $value): string
    {
        $value = strtolower(trim($value));
        if ($value === '') {
            return '';
        }

        return match ($value) {
            'standard' => 'Стандарт',
            'special', 'custom_logo', 'custom' => 'Особый логотип',
            default => $value,
        };
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

        $quantity = (float)$item->getQuantity();
        if ($quantity <= 0.0) {
            $quantity = 1.0;
        }

        $priceData = self::calculatePriceData($productId, $normalized, $quantity);
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

        $raw = '';
        if (method_exists($collection, 'getItemByCode')) {
            $prop = $collection->getItemByCode(self::OPTION_PROP_CODE);
            if ($prop) {
                $raw = (string)$prop->getValue();
            }
        } elseif (method_exists($collection, 'getPropertyValues')) {
            $values = $collection->getPropertyValues();
            if (is_array($values) && array_key_exists(self::OPTION_PROP_CODE, $values)) {
                $raw = self::normalizePropertyValue($values[self::OPTION_PROP_CODE]);
            }
        } elseif (method_exists($collection, 'getArray')) {
            $data = $collection->getArray();
            if (is_array($data) && !empty($data['PROPS']) && is_array($data['PROPS'])) {
                foreach ($data['PROPS'] as $prop) {
                    if (!is_array($prop)) {
                        continue;
                    }
                    if ((string)($prop['CODE'] ?? '') === self::OPTION_PROP_CODE) {
                        $raw = self::normalizePropertyValue($prop['VALUE'] ?? '');
                        break;
                    }
                }
            }
        }
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

    private static function calculatePriceData(int $productId, array $options, float $quantity = 1.0): ?array
    {
        try {
            $price = Configurator::calculate($productId, ['options' => $options], [
                'QUANTITY' => $quantity,
            ]);
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

        $userGroups = [2];
        global $USER;
        if ($USER instanceof \CUser) {
            $groups = $USER->GetUserGroupArray();
            if (is_array($groups) && $groups !== []) {
                $userGroups = $groups;
            }
        }

        $optimal = \CCatalogProduct::GetOptimalPrice($productId, $quantity, $userGroups, 'N');
        if (is_array($optimal)) {
            $resultPrice = $optimal['RESULT_PRICE'] ?? null;
            if (is_array($resultPrice) && isset($resultPrice['DISCOUNT_PRICE'])) {
                return [
                    'PRICE' => (float)$resultPrice['DISCOUNT_PRICE'],
                    'CURRENCY' => isset($resultPrice['CURRENCY']) ? (string)$resultPrice['CURRENCY'] : 'RUB',
                ];
            }
            if (isset($optimal['PRICE']) && is_array($optimal['PRICE']) && isset($optimal['PRICE']['PRICE'])) {
                return [
                    'PRICE' => (float)$optimal['PRICE']['PRICE'],
                    'CURRENCY' => isset($optimal['PRICE']['CURRENCY']) ? (string)$optimal['PRICE']['CURRENCY'] : 'RUB',
                ];
            }
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

    private static function normalizePropertyValue(mixed $value): string
    {
        if (is_array($value) || is_object($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($encoded) ? $encoded : '';
        }

        if (is_bool($value)) {
            return $value ? 'Y' : 'N';
        }

        if ($value === null) {
            return '';
        }

        return (string)$value;
    }
}
