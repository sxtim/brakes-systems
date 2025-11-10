<?php

namespace App\Brakes\Pricing;

use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\SystemException;
use CCatalogProduct;
use CCurrencyLang;

class Configurator
{
    private const OPTION_KEY_MAP = [
        'two_piece_disc_construction' => 'two_piece_disc_construction',
        'rotor_pattern' => 'rotor_pattern',
        'caliper_logo' => 'caliper_logo',
        'electric_handbrake' => 'electric_handbrake',
    ];

    private const MARKUP_RULES = [
        'two_piece_disc_construction' => [
            'yes' => 10000.0,
        ],
        'caliper_logo' => [
            'special' => 5000.0,
        ],
        'electric_handbrake' => [
            'yes' => 50000.0,
        ],
    ];

    private const HL_TABLE_NAME = 'brakes_option_markups';

    /**
     * @var array<string, array<string, float>>|null
     */
    private static ?array $cachedMarkupRules = null;

    /**
     * @throws SystemException
     */
    public static function calculate(int $productId, array $options = [], array $context = []): ?array
    {
        if ($productId <= 0) {
            return null;
        }

        if (!Loader::includeModule('catalog')) {
            throw new SystemException('Catalog module is not available.');
        }

        $basePrice = \CPrice::GetBasePrice($productId);
        if (!is_array($basePrice) || !isset($basePrice['PRICE'])) {
            return null;
        }

        $currency = $basePrice['CURRENCY'] ?? 'RUB';
        $baseValue = (float)$basePrice['PRICE'];
        $markup = self::calculateMarkup($options);
        $finalValue = $baseValue + $markup;

        $resultPrice = [
            'BASE_PRICE' => $baseValue,
            'UNROUND_BASE_PRICE' => $baseValue,
            'DISCOUNT_PRICE' => $finalValue,
            'UNROUND_DISCOUNT_PRICE' => $finalValue,
            'CURRENCY' => $currency,
            'VAT_INCLUDED' => $basePrice['VAT_INCLUDED'] ?? 'N',
            'VAT_RATE' => isset($basePrice['VAT_RATE']) ? (float)$basePrice['VAT_RATE'] : 0,
            'VAT_VALUE' => 0,
        ];

        $priceData = [
            'BASE_PRICE' => $baseValue,
            'CURRENCY' => $currency,
            'DISCOUNT_PRICE' => $finalValue,
            'UNROUND_DISCOUNT_PRICE' => $finalValue,
            'RESULT_PRICE' => $resultPrice,
            'MARKUP' => $markup,
        ];

        $priceData['PRICE_FORMATTED'] = self::formatPrice($finalValue, $currency);

        return $priceData;
    }

    private static function resolveUserGroups(array $context): array
    {
        if (isset($context['USER_GROUPS']) && is_array($context['USER_GROUPS'])) {
            return $context['USER_GROUPS'];
        }

        global $USER;

        if ($USER instanceof \CUser) {
            $groups = $USER->GetUserGroupArray();
            if (is_array($groups) && $groups !== []) {
                return $groups;
            }
        }

        return [2]; // группа по умолчанию (все пользователи)
    }

    private static function calculateMarkup(array $options): float
    {
        $normalized = self::normalizeOptions($options);
        $rules = self::getMarkupRules();
        $sum = 0.0;

        foreach ($rules as $code => $map) {
            $value = $normalized[$code] ?? null;
            if ($value === null) {
                continue;
            }

            if (isset($map[$value])) {
                $sum += (float)$map[$value];
            }
        }

        return $sum;
    }

    private static function normalizeOptions(array $options): array
    {
        if (isset($options['options']) && is_array($options['options'])) {
            $options = $options['options'];
        }

        $normalized = [];

        foreach ($options as $key => $value) {
            $key = self::OPTION_KEY_MAP[$key] ?? $key;

            if (is_array($value)) {
                $value = $value['value'] ?? $value['VALUE'] ?? reset($value);
            }

            if (!is_scalar($value)) {
                continue;
            }

            $normalized[$key] = self::toLower((string)$value);
        }

        return $normalized;
    }

    private static function applyMarkup(array $resultPrice, float $markup): array
    {
        $resultPrice['BASE_PRICE'] = ($resultPrice['BASE_PRICE'] ?? 0) + $markup;
        $resultPrice['UNROUND_BASE_PRICE'] = ($resultPrice['UNROUND_BASE_PRICE'] ?? 0) + $markup;
        $resultPrice['DISCOUNT_PRICE'] = ($resultPrice['DISCOUNT_PRICE'] ?? 0) + $markup;
        $resultPrice['UNROUND_DISCOUNT_PRICE'] = ($resultPrice['UNROUND_DISCOUNT_PRICE'] ?? 0) + $markup;

        if (!empty($resultPrice['VAT_RATE']) && $resultPrice['VAT_RATE'] > 0) {
            $vatIncluded = $resultPrice['VAT_INCLUDED'] === 'Y';
            $vatRate = (float)$resultPrice['VAT_RATE'];

            if ($vatIncluded) {
                $resultPrice['VAT_VALUE'] = ($resultPrice['DISCOUNT_PRICE'] * $vatRate) / (1 + $vatRate);
            } else {
                $resultPrice['VAT_VALUE'] = ($resultPrice['DISCOUNT_PRICE']) * $vatRate;
            }
        }

        return $resultPrice;
    }

    private static function getMarkupRules(): array
    {
        if (self::$cachedMarkupRules !== null) {
            return self::$cachedMarkupRules;
        }

        $rules = self::MARKUP_RULES;

        if (!Loader::includeModule('highloadblock')) {
            self::$cachedMarkupRules = $rules;
            return self::$cachedMarkupRules;
        }

        $hlBlock = HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::HL_TABLE_NAME],
            'limit' => 1,
        ])->fetch();

        if (!$hlBlock) {
            self::$cachedMarkupRules = $rules;
            return self::$cachedMarkupRules;
        }

        $entity = HighloadBlockTable::compileEntity($hlBlock);
        $dataClass = $entity->getDataClass();

        $result = $dataClass::getList([
            'select' => ['UF_OPTION', 'UF_VALUE', 'UF_PRICE'],
        ]);

        while ($row = $result->fetch()) {
            $option = isset($row['UF_OPTION']) ? self::toLower((string)$row['UF_OPTION']) : '';
            $value = isset($row['UF_VALUE']) ? self::toLower((string)$row['UF_VALUE']) : '';
            $price = isset($row['UF_PRICE']) && is_numeric($row['UF_PRICE']) ? (float)$row['UF_PRICE'] : null;

            if ($option === '' || $value === '' || $price === null) {
                continue;
            }

            if (!isset($rules[$option])) {
                $rules[$option] = [];
            }

            $rules[$option][$value] = $price;
        }

        self::$cachedMarkupRules = $rules;

        return self::$cachedMarkupRules;
    }

    private static function toLower(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }

        return strtolower($value);
    }

    private static function formatPrice(float $value, string $currency): string
    {
        if (!Loader::includeModule('currency')) {
            return number_format($value, 2, '.', ' ') . ' ' . $currency;
        }

        return CCurrencyLang::CurrencyFormat($value, $currency);
    }
}
