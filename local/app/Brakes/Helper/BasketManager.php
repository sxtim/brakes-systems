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
    private const LEGACY_OPTION_PROP_CODE = 'OPTIONS';
    private const OPTION_HASH_PROP_CODE = 'OPTIONS_HASH';

    private static function buildContextKey(int $sectionId, string $sectionPath): string
    {
        if ($sectionId > 0) {
            return 's:' . $sectionId;
        }

        $path = self::normalizePath($sectionPath);
        if ($path !== '') {
            return 'p:' . $path;
        }

        return 's:0';
    }

    public static function addProduct(int $productId, float $quantity, array $context = [], array $options = []): array
    {
        if ($productId <= 0 || $quantity <= 0) {
            throw new SystemException('Invalid product or quantity.');
        }

        self::ensureModules();

        $normalizedOptions = self::normalizeOptionsMap($options);
        $optionsHash = self::hashOptionsMap($normalizedOptions);
        $contextData = self::normalizeContext($context, $options, $optionsHash);
        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);
        $existing = self::findMatchingItemByContextAndOptions($basket, $productId, $optionsHash, $contextData);

        if ($existing) {
            // Idempotent add: if the same product+options are already in basket,
            // don't create duplicates or silently bump quantity. Quantity is managed in basket UI.
            return [
                'summary' => self::getSummaryFromBasket($basket),
                'alreadyInBasket' => true,
                'basketItemId' => (int)$existing->getId(),
            ];
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

        return [
            'summary' => self::getSummaryFromBasket($basket),
            'alreadyInBasket' => false,
            'basketItemId' => (int)$item->getId(),
        ];
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

    /**
     * Checks whether the given product+options+context configurations already exist in the current basket.
     * Used by UI sync (e.g. after back/forward navigation) to mark "В корзине" without creating duplicates.
     *
     * Input items format:
     *  [
     *    ['productId' => 123, 'options' => '{"options":{...}}', 'context' => ['section_id' => 10, 'section_path' => '...']],
     *    ...
     *  ]
     */
    public static function checkItems(array $items): array
    {
        self::ensureModules();

        $basket = Basket::loadItemsForFUser(Fuser::getId(), SITE_ID);

        // Build a quick lookup: productId -> optionsHash -> contextKey -> basketItemId
        $index = [];
        foreach ($basket as $basketItem) {
            $pid = (int)$basketItem->getProductId();
            if ($pid <= 0) {
                continue;
            }
            $hash = self::getItemOptionsHash($basketItem);
            if ($hash === '') {
                $hash = 'empty';
            }

            $signature = self::getItemSignature($basketItem);
            $contextKey = self::buildContextKey((int)($signature['section_id'] ?? 0), (string)($signature['section_path'] ?? ''));

            $index[$pid][$hash][$contextKey] = (int)$basketItem->getId();
        }

        $result = [];
        foreach ($items as $entry) {
            if (!is_array($entry)) {
                $result[] = ['inBasket' => false, 'basketItemId' => 0];
                continue;
            }

            $productId = (int)($entry['productId'] ?? 0);
            $rawOptions = $entry['options'] ?? null;
            $rawContext = $entry['context'] ?? null;

            $options = [];
            if (is_array($rawOptions)) {
                $options = $rawOptions;
            } elseif (is_string($rawOptions) && trim($rawOptions) !== '') {
                $decoded = json_decode($rawOptions, true);
                if (is_array($decoded)) {
                    $options = $decoded;
                }
            }

            $normalized = self::normalizeOptionsMap($options);
            $hash = self::hashOptionsMap($normalized);

            $context = [];
            if (is_array($rawContext)) {
                $context = $rawContext;
            } elseif (is_string($rawContext) && trim($rawContext) !== '') {
                $decoded = json_decode($rawContext, true);
                if (is_array($decoded)) {
                    $context = $decoded;
                }
            }

            $contextKey = self::buildContextKey(
                (int)($context['section_id'] ?? 0),
                (string)($context['section_path'] ?? '')
            );

            $basketItemId = 0;
            if ($productId > 0 && isset($index[$productId][$hash][$contextKey])) {
                $basketItemId = (int)$index[$productId][$hash][$contextKey];
            }

            $result[] = [
                'inBasket' => $basketItemId > 0,
                'basketItemId' => $basketItemId,
            ];
        }

        return [
            'items' => $result,
            'summary' => self::getSummaryFromBasket($basket),
        ];
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

    private static function normalizeContext(array $context, array $options, string $optionsHash = ''): array
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
            'options_hash' => trim($optionsHash),
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

    private static function findMatchingItemByContextAndOptions(Basket $basket, int $productId, string $optionsHash, array $contextData): ?BasketItemBase
    {
        $targetKey = self::buildContextKey((int)($contextData['section_id'] ?? 0), (string)($contextData['section_path'] ?? ''));

        foreach ($basket as $item) {
            if ((int)$item->getProductId() !== $productId) {
                continue;
            }

            $signature = self::getItemSignature($item);
            $existingKey = self::buildContextKey((int)($signature['section_id'] ?? 0), (string)($signature['section_path'] ?? ''));
            if ($existingKey !== $targetKey) {
                continue;
            }

            $existingHash = self::getItemOptionsHash($item);
            if ($existingHash === $optionsHash) {
                return $item;
            }
        }

        return null;
    }

    private static function getItemOptionsHash(BasketItemBase $item): string
    {
        $collection = $item->getPropertyCollection();
        if ($collection) {
            $raw = trim(self::extractPropertyRawValue($collection, self::OPTION_HASH_PROP_CODE));
            if ($raw !== '') {
                return $raw;
            }
        }

        $options = self::extractOptionsFromItem($item);
        $normalized = self::normalizeOptionsMap($options);
        return self::hashOptionsMap($normalized);
    }

    private static function getItemSignature(BasketItemBase $item): array
    {
        $collection = $item->getPropertyCollection();
        $getValue = static function (string $code) use ($collection): string {
            if (!$collection) {
                return '';
            }

            if (method_exists($collection, 'getPropertyValues')) {
                $values = $collection->getPropertyValues();
                if (is_array($values) && array_key_exists($code, $values)) {
                    return self::normalizeBasketPropValue($values[$code]);
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
                            return self::normalizeBasketPropValue($prop['VALUE'] ?? '');
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

        if (!empty($contextData['options_hash'])) {
            $properties[] = [
                'NAME' => 'Options Hash',
                'CODE' => self::OPTION_HASH_PROP_CODE,
                'VALUE' => self::normalizePropertyValue($contextData['options_hash']),
                'SORT' => 131,
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
            self::LEGACY_OPTION_PROP_CODE => true,
            self::OPTION_HASH_PROP_CODE => true,
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
                    // Preserve original basket property values as scalars (Bitrix can return arrays like ['VALUE' => '...']).
                    $value = self::normalizeBasketPropValue($prop['VALUE'] ?? '');
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
                    $finalValue = self::normalizeBasketPropValue($value);
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

        $raw = self::extractPropertyRawValue($collection, self::OPTION_PROP_CODE);
        if ($raw === '') {
            $raw = self::extractPropertyRawValue($collection, self::LEGACY_OPTION_PROP_CODE);
        }
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private static function extractPropertyRawValue(object $collection, string $code): string
    {
        if (method_exists($collection, 'getPropertyValues')) {
            $values = $collection->getPropertyValues();
            if (is_array($values) && array_key_exists($code, $values)) {
                return trim(self::normalizeBasketPropValue($values[$code]));
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
                        return trim(self::normalizeBasketPropValue($prop['VALUE'] ?? ''));
                    }
                }
            }
        }

        return '';
    }

    /**
     * Basket property values can come back as arrays (e.g. ['VALUE' => '...']).
     * We need to unwrap them into a stable scalar string for comparisons/indexing.
     */
    private static function normalizeBasketPropValue(mixed $value): string
    {
        if (is_object($value)) {
            $value = get_object_vars($value);
        }

        if (is_array($value)) {
            if (array_key_exists('VALUE', $value)) {
                return self::normalizeBasketPropValue($value['VALUE']);
            }
            if (array_key_exists('value', $value)) {
                return self::normalizeBasketPropValue($value['value']);
            }
            if (array_key_exists('TEXT', $value)) {
                return self::normalizeBasketPropValue($value['TEXT']);
            }
            if (count($value) === 1) {
                return self::normalizeBasketPropValue(reset($value));
            }

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

        ksort($normalized);

        return $normalized;
    }

    private static function hashOptionsMap(array $options): string
    {
        if ($options === []) {
            return 'empty';
        }

        ksort($options);
        $encoded = json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === '') {
            return 'empty';
        }

        return md5($encoded);
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
