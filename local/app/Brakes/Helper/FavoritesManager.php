<?php
namespace App\Brakes\Helper;

use App\Brakes\Pricing\Configurator;
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Web\Cookie;
use CCatalogGroup;
use CPrice;

class FavoritesManager
{
    private const COOKIE_NAME = 'BR_FAVORITES';
    private const COOKIE_TTL = 31536000; // 1 year
    private const SESSION_FLAG = 'BR_FAVORITES_SYNC_DONE';
    private const LOG_PATH = '/upload/favorites_log.txt';

    /**
     * @var array<int, array{options: array}>
     */
    private static ?array $currentItems = null;
    private static ?bool $isAuthorized = null;

    public static function handleProlog(): void
    {
        self::resetCache();

        global $USER;
        $session = Application::getInstance()->getSession();

        if ($USER instanceof \CUser && $USER->IsAuthorized()) {
            if (!$session->has(self::SESSION_FLAG)) {
                $cookieItems = self::getCookieFavorites();

                if (!empty($cookieItems)) {
                    try {
                        Favorites::mergeFavorites((int)$USER->GetID(), $cookieItems);
                    } catch (SystemException $exception) {
                        // ignore merge errors to avoid blocking authorization flow
                    }

                    self::resetCache();
                    self::setCookieFavorites([]);
                }

                $session[self::SESSION_FLAG] = true;
            }
        } else {
            if ($session->has(self::SESSION_FLAG)) {
                unset($session[self::SESSION_FLAG]);
            }
        }

        self::ensureLoaded();
    }

    public static function getCurrentFavorites(): array
    {
        self::ensureLoaded();

        return array_keys(self::$currentItems);
    }

    public static function getClientState(): array
    {
        $items = self::getCurrentFavorites();

        return [
            'items' => $items,
            'count' => count($items),
            'isAuthorized' => self::$isAuthorized === true,
            'meta' => self::$currentItems,
        ];
    }

    public static function isFavorite(int $productId): bool
    {
        self::ensureLoaded();

        return isset(self::$currentItems[$productId]);
    }

    public static function toggleProduct(int $productId, array $options = []): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            self::log('toggleProduct invalid productId', ['productId' => $productId, 'options' => $options]);
            return self::getCurrentFavorites();
        }

        self::ensureLoaded();
        $normalizedOptions = self::normalizeOptions($options);
        self::log('toggleProduct called', [
            'productId' => $productId,
            'options' => $options,
            'normalizedOptions' => $normalizedOptions,
            'isAuthorized' => self::$isAuthorized,
        ]);

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                self::log('toggleProduct userId=0', ['productId' => $productId]);
                return self::getCurrentFavorites();
            }

            if (isset(self::$currentItems[$productId])) {
                Favorites::removeProduct($userId, $productId);
                unset(self::$currentItems[$productId]);
                self::log('toggleProduct removed', ['userId' => $userId, 'productId' => $productId]);
            } else {
                Favorites::addProduct($userId, $productId, $normalizedOptions);
                self::$currentItems[$productId] = [
                    'options' => $normalizedOptions,
                ];
                self::log('toggleProduct added', ['userId' => $userId, 'productId' => $productId, 'normalizedOptions' => $normalizedOptions]);
            }
        } else {
            if (isset(self::$currentItems[$productId])) {
                unset(self::$currentItems[$productId]);
                self::log('toggleProduct removed guest', ['productId' => $productId]);
            } else {
                self::$currentItems[$productId] = [
                    'options' => $normalizedOptions,
                ];
                self::log('toggleProduct added guest', ['productId' => $productId, 'normalizedOptions' => $normalizedOptions]);
            }

            self::setCookieFavorites(self::$currentItems);
        }

        ksort(self::$currentItems);

        return array_keys(self::$currentItems);
    }

    public static function updateProductOptions(int $productId, array $options): bool
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            self::log('updateProductOptions invalid productId', ['productId' => $productId, 'options' => $options]);
            return false;
        }

        self::ensureLoaded();

        if (!isset(self::$currentItems[$productId])) {
            self::log('updateProductOptions product not in favorites', ['productId' => $productId]);
            return false;
        }

        $normalizedOptions = self::normalizeOptions($options);
        self::$currentItems[$productId]['options'] = $normalizedOptions;
        self::log('updateProductOptions set options', [
            'productId' => $productId,
            'normalizedOptions' => $normalizedOptions,
            'isAuthorized' => self::$isAuthorized,
        ]);

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                self::log('updateProductOptions userId=0', ['productId' => $productId]);
                return false;
            }

            Favorites::setProductOptions($userId, $productId, $normalizedOptions);
            self::log('updateProductOptions saved for user', ['productId' => $productId, 'userId' => $userId]);
        } else {
            self::setCookieFavorites(self::$currentItems);
            self::log('updateProductOptions saved for guest', ['productId' => $productId]);
        }

        return true;
    }

    public static function getFavoritesProductsData(array $productIds): array
    {
        self::ensureLoaded();

        $ids = Favorites::normalizeProductIds($productIds);

        if ($ids === []) {
            self::log('getFavoritesProductsData empty ids', ['productIds' => $productIds]);
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            self::log('getFavoritesProductsData iblock not loaded', ['ids' => $ids]);
            return [];
        }

        if (!Loader::includeModule('catalog')) {
            self::log('getFavoritesProductsData catalog not loaded', ['ids' => $ids]);
            return [];
        }

        $select = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'DETAIL_PAGE_URL',
            'PROPERTY_LINK_PHOTO',
            'PROPERTY_CML2_ARTICLE',
            'PROPERTY_MANUFACTURER',
            'PROPERTY_NUMBER_PISTONS',
            'PROPERTY_INSTALLATION_AXIS',
        ];

        $elements = [];
        $result = \CIBlockElement::GetList([], ['ID' => $ids], false, false, $select);

        while ($element = $result->GetNextElement()) {
            $fields = $element->GetFields();
            $properties = $element->GetProperties();

            $id = (int)($fields['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = $fields['~NAME'] ?? $fields['NAME'] ?? '';
            $detailUrl = $fields['DETAIL_PAGE_URL'] ?? '#';

            $details = [
                [
                    'label' => 'Артикул',
                    'value' => self::normalizePropertyValue($properties['CML2_ARTICLE']['VALUE'] ?? $fields['PROPERTY_CML2_ARTICLE_VALUE'] ?? ''),
                ],
                [
                    'label' => 'Производитель:',
                    'value' => self::normalizePropertyValue($properties['MANUFACTURER']['VALUE'] ?? $fields['PROPERTY_MANUFACTURER_VALUE'] ?? ''),
                ],
                [
                    'label' => 'Кол-во поршней:',
                    'value' => self::normalizePropertyValue($properties['NUMBER_PISTONS']['VALUE'] ?? $fields['PROPERTY_NUMBER_PISTONS_VALUE'] ?? ''),
                ],
                [
                    'label' => 'Ось:',
                    'value' => self::normalizePropertyValue($properties['INSTALLATION_AXIS']['VALUE'] ?? $fields['PROPERTY_INSTALLATION_AXIS_VALUE'] ?? ''),
                ],
            ];

            $colors = [];
            if (isset($properties['COLOR']) && is_array($properties['COLOR'])) {
                $colorValues = $properties['COLOR']['VALUE'] ?? [];
                $colorXmlIds = $properties['COLOR']['VALUE_XML_ID'] ?? [];

                if (!is_array($colorValues)) {
                    $colorValues = $colorValues !== null ? [$colorValues] : [];
                }
                if (!is_array($colorXmlIds)) {
                    $colorXmlIds = $colorXmlIds !== null ? [$colorXmlIds] : [];
                }

                foreach ($colorValues as $index => $colorValue) {
                    $xmlId = $colorXmlIds[$index] ?? null;
                    if (!is_string($xmlId) || $xmlId === '') {
                        continue;
                    }
                    $colors[] = [
                        'xmlId' => $xmlId,
                    ];
                }
            }

            $picture = getPreviewImgCatalog($fields['PROPERTY_LINK_PHOTO_VALUE'] ?? null);
            $optionsRaw = self::$currentItems[$id]['options'] ?? [];
            $optionsRaw = is_array($optionsRaw) ? $optionsRaw : [];
            $optionsUnwrapped = self::unwrapOptionsPayload($optionsRaw);
            $selectedValues = self::flattenOptionValues($optionsUnwrapped);

            $elements[$id] = [
                'ID' => $id,
                'NAME' => $name,
                'URL' => $detailUrl,
                'PICTURE' => $picture,
                'DETAILS' => $details,
                'COLORS' => $colors,
                'PRICE' => null,
                'PRICE_HTML' => null,
                'PRICE_DATA' => null,
                'OPTIONS' => $optionsRaw,
                'OPTIONS_UNWRAPPED' => $optionsUnwrapped,
                'SELECTED_OPTIONS' => $selectedValues,
                'CARD' => [
                    'ID' => $id,
                    'NAME' => $name,
                    'DETAIL_PAGE_URL' => $detailUrl,
                    'IMG' => $picture,
                    'DETAILS' => $details,
                    'COLORS' => $colors,
                    'SELECTED' => $selectedValues,
                    'EXPAND_FEATURES' => true,
                ],
            ];
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (!isset($elements[$id])) {
                continue;
            }

            $options = $elements[$id]['OPTIONS'];
            $priceData = self::calculatePrice($id, $options);

            if ($priceData !== null) {
                $elements[$id]['PRICE_DATA'] = $priceData;
                $formattedPrice = $priceData['PRICE_FORMATTED'] ?? null;
                if (is_string($formattedPrice) && $formattedPrice !== '') {
                    $elements[$id]['PRICE_HTML'] = $formattedPrice;
                    $elements[$id]['PRICE'] = htmlspecialcharsback($formattedPrice);
                } else {
                    $elements[$id]['PRICE_HTML'] = null;
                    $elements[$id]['PRICE'] = null;
                }
            }

            $optionsAttr = '{}';
            $optionsUnwrapped = $elements[$id]['OPTIONS_UNWRAPPED'] ?? [];
            if (!empty($optionsUnwrapped)) {
                $encoded = json_encode(
                    ['options' => $optionsUnwrapped],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                if (is_string($encoded) && $encoded !== '') {
                    $optionsAttr = htmlspecialcharsbx($encoded);
                }
            }

            $card = $elements[$id]['CARD'];
            $card['PRICE_HTML'] = isset($elements[$id]['PRICE_HTML']) && is_string($elements[$id]['PRICE_HTML'])
                ? htmlspecialcharsback($elements[$id]['PRICE_HTML'])
                : '';
            $card['OPTIONS_ATTR'] = $optionsAttr;
            $card['BUY'] = [
                'NAME' => $elements[$id]['NAME'],
                'URL' => $elements[$id]['URL'],
            ];
            $card['SELECTED'] = $elements[$id]['SELECTED_OPTIONS'] ?? $card['SELECTED'] ?? [];

            $elements[$id]['CARD'] = $card;
            $elements[$id]['OPTIONS_ATTR'] = $optionsAttr;

            $ordered[] = $elements[$id];
        }

        return $ordered;
    }

    public static function getProductPrice(int $productId, array $options): ?array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return null;
        }

        $normalized = self::normalizeOptions($options);
        self::log('getProductPrice request', [
            'productId' => $productId,
            'options' => $normalized,
        ]);

        $price = self::calculatePrice($productId, $normalized);

        if ($price !== null) {
            self::log('getProductPrice result', [
                'productId' => $productId,
                'priceFormatted' => $price['PRICE_FORMATTED'] ?? null,
                'markup' => $price['MARKUP'] ?? null,
            ]);
        } else {
            self::log('getProductPrice result null', [
                'productId' => $productId,
                'options' => $normalized,
            ]);
        }

        return $price;
    }

    public static function buildFavoritesPopupHtml(array $items): string
    {
        if ($items === []) {
            return '<div class="favorit-box__empty">Favorites list is empty.</div>';
        }

        $templatePath = defined('SITE_TEMPLATE_PATH') ? SITE_TEMPLATE_PATH : '/local/templates/main';
        $partialPath = self::getProductCardPartialPath();

        ob_start();

        foreach ($items as $item) {
            $card = isset($item['CARD']) && is_array($item['CARD']) ? $item['CARD'] : null;

            if ($card !== null) {
                if (!isset($card['PRICE_HTML'])) {
                    $priceHtml = $item['PRICE_HTML'] ?? null;
                    $card['PRICE_HTML'] = is_string($priceHtml) ? htmlspecialcharsback($priceHtml) : '';
                }

                if (!isset($card['OPTIONS_ATTR'])) {
                    $card['OPTIONS_ATTR'] = $item['OPTIONS_ATTR'] ?? '{}';
                }

                if (!isset($card['SELECTED'])) {
                    $card['SELECTED'] = $item['SELECTED_OPTIONS'] ?? [];
                }

                $card['FAVORITES_VIEW'] = true;

                if (!isset($card['BUY'])) {
                    $card['BUY'] = [
                        'NAME' => $item['NAME'] ?? '',
                        'URL' => $item['URL'] ?? '#',
                    ];
                }

                if (!isset($card['IMG']) || $card['IMG'] === '') {
                    $card['IMG'] = $item['PICTURE'] ?? '';
                }

                if ($partialPath !== null) {
                    $cardData = $card;
                    $card = $cardData;
                    include $partialPath;
                    unset($card);
                    continue;
                }
            }

            echo self::renderLegacyFavoritesItem($item, $templatePath);
        }

        return trim((string)ob_get_clean());
    }

    public static function refreshCurrentFavorites(): void
    {
        self::resetCache();
        self::ensureLoaded();
    }

    private static function ensureLoaded(): void
    {
        if (self::$currentItems !== null) {
            return;
        }

        global $USER;
        self::$isAuthorized = $USER instanceof \CUser && $USER->IsAuthorized();

        if (self::$isAuthorized) {
            $userId = (int)$USER->GetID();
            $rows = Favorites::getUserProducts($userId);

            $items = [];
            foreach ($rows as $productId => $row) {
                $items[$productId] = [
                    'options' => self::normalizeOptions($row['OPTIONS'] ?? []),
                ];
            }

            self::$currentItems = $items;
        } else {
            self::$currentItems = self::getCookieFavorites();
        }

        self::pruneMissingProducts();

        static $lastSignature = null;
        $ids = array_keys(self::$currentItems);
        $previewIds = array_slice($ids, 0, 5);
        $signature = (self::$isAuthorized ? 'auth' : 'guest') . ':' . count(self::$currentItems) . ':' . implode(',', $previewIds);

        if ($signature !== $lastSignature) {
            $lastSignature = $signature;

            if (self::$isAuthorized) {
                self::log('ensureLoaded authorized', [
                    'userId' => $userId ?? 0,
                    'itemsCount' => count(self::$currentItems),
                    'productIds' => $ids,
                ]);
            } else {
                self::log('ensureLoaded guest', [
                    'itemsCount' => count(self::$currentItems),
                    'productIds' => $ids,
                ]);
            }
        }
    }

    private static function resetCache(): void
    {
        self::$currentItems = null;
        self::$isAuthorized = null;
    }

    private static function getProductCardPartialPath(): ?string
    {
        $templatePath = defined('SITE_TEMPLATE_PATH') ? SITE_TEMPLATE_PATH : '/local/templates/main';
        $root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        if ($root === '') {
            return null;
        }

        $partialRelative = $templatePath . '/components/bitrix/catalog/.default/bitrix/catalog.section/.default/partials/product-card.php';
        $fullPath = $root . $partialRelative;

        return file_exists($fullPath) ? $fullPath : null;
    }

    private static function pruneMissingProducts(): void
    {
        if (self::$currentItems === null || self::$currentItems === []) {
            return;
        }

        $ids = array_map(static fn($id) => (int)$id, array_keys(self::$currentItems));
        $ids = array_values(array_filter($ids, static fn($id) => $id > 0));
        if ($ids === []) {
            return;
        }

        if (!Loader::includeModule('iblock')) {
            self::log('pruneMissingProducts skipped: iblock module not available', ['ids' => $ids]);
            return;
        }

        $existing = [];
        $result = \CIBlockElement::GetList([], ['ID' => $ids], false, false, ['ID']);
        while ($row = $result->Fetch()) {
            $existing[(int)$row['ID']] = true;
        }

        $missing = array_values(array_diff($ids, array_keys($existing)));
        if ($missing === []) {
            return;
        }

        foreach ($missing as $productId) {
            unset(self::$currentItems[$productId]);
        }

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId > 0) {
                foreach ($missing as $productId) {
                    Favorites::removeProduct($userId, $productId);
                }
            }
        } else {
            self::setCookieFavorites(self::$currentItems);
        }

        self::log('pruneMissingProducts removed', [
            'missingIds' => $missing,
            'isAuthorized' => self::$isAuthorized,
        ]);
    }

    private static function renderLegacyFavoritesItem(array $item, string $templatePath): string
    {
        $id = (int)($item['ID'] ?? 0);
        if ($id <= 0) {
            return '';
        }

        $name = htmlspecialcharsbx($item['NAME'] ?? '');
        $url = htmlspecialcharsbx($item['URL'] ?? '#');
        $pictureUrl = !empty($item['PICTURE']) ? htmlspecialcharsbx($item['PICTURE']) : '';
        $priceHtml = $item['PRICE_HTML'] ?? null;
        $priceText = $item['PRICE'] ?? null;

        ob_start();
        ?>
        <a class="favorit-box__item" data-fls-like-product="<?= $id ?>" href="<?= $url ?>">
            <div class="favorit-box__item-foto">
                <img class="favorit-box__img" alt="<?= $name ?>" src="<?= $pictureUrl ?>">
            </div>
            <div class="favorit-box__inner">
                <h3 class="favorit-box__item-title"><?= $name ?></h3>
                <div class="favorit-box__item-bottom">
                    <div class="favorit-box__item-price">
                        <?php
                        if (is_string($priceHtml) && $priceHtml !== '') {
                            echo htmlspecialcharsback($priceHtml);
                        } elseif (is_string($priceText) && $priceText !== '') {
                            echo htmlspecialcharsbx($priceText);
                        }
                        ?>
                    </div>
                </div>
            </div>
            <button class="favorit-box__delete" data-fls-like-button data-product-id="<?= $id ?>" aria-label="Remove from favorites">
                <img src="<?= $templatePath ?>/assets/img/favorite/trash.svg" alt="Remove">
            </button>
        </a>
        <?php

        return trim((string)ob_get_clean());
    }

    private static function unwrapOptionsPayload(array $options): array
    {
        if (isset($options['options']) && is_array($options['options'])) {
            return $options['options'];
        }

        return $options;
    }

    private static function flattenOptionValues(array $options): array
    {
        $result = [];
        foreach ($options as $key => $value) {
            if (!is_string($key) || $key === '') {
                continue;
            }

            $normalizedKey = self::lowercase($key);

            if (is_array($value) && isset($value['value'])) {
                $result[$normalizedKey] = self::lowercase((string)$value['value']);
            } elseif (is_scalar($value)) {
                $result[$normalizedKey] = self::lowercase((string)$value);
            }
        }

        return $result;
    }

    private static function normalizePropertyValue($value): string
    {
        if (is_array($value)) {
            $first = reset($value);
            if (is_scalar($first)) {
                return (string)$first;
            }

            return '';
        }

        if (is_scalar($value)) {
            return (string)$value;
        }

        return '';
    }

    /**
     * @return array<int, array{options: array}>
     */
    private static function getCookieFavorites(): array
    {
        $request = Context::getCurrent()->getRequest();
        $raw = $request->getCookie(self::COOKIE_NAME);

        if ($raw === null || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $item) {
            if (is_array($item)) {
                $productId = isset($item['id']) ? (int)$item['id'] : (int)($item['PRODUCT_ID'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }

                $options = [];
                if (isset($item['options']) && is_array($item['options'])) {
                    $options = $item['options'];
                } elseif (isset($item['OPTIONS']) && is_array($item['OPTIONS'])) {
                    $options = $item['OPTIONS'];
                }

                $normalized[$productId] = [
                    'options' => self::normalizeOptions($options),
                ];
            } else {
                $productId = (int)$item;
                if ($productId <= 0) {
                    continue;
                }

                $normalized[$productId] = [
                    'options' => [],
                ];
            }
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<int, array{options: array}> $items
     */
    private static function setCookieFavorites(array $items): void
    {
        $payload = [];
        foreach ($items as $productId => $data) {
            $productId = (int)$productId;
            if ($productId <= 0) {
                continue;
            }

            $payload[] = [
                'id' => $productId,
                'options' => $data['options']['options'] ?? ($data['options'] ?? []),
            ];
        }

        $context = Context::getCurrent();
        $response = $context->getResponse();

        $cookie = new Cookie(self::COOKIE_NAME, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $cookie->setPath('/');
        $cookie->setHttpOnly(false);
        $cookie->setSecure($context->getRequest()->isHttps());
        $cookie->setExpires(time() + self::COOKIE_TTL);

        $response->addCookie($cookie);
    }

    private static function getCurrentUserId(): int
    {
        global $USER;

        if ($USER instanceof \CUser && $USER->IsAuthorized()) {
            return (int)$USER->GetID();
        }

        return 0;
    }

    private static function normalizeOptions(array $options): array
    {
        self::log('normalizeOptions input', ['options' => $options]);

        if (isset($options['options']) && is_array($options['options'])) {
            $options = $options['options'];
        }

        $normalized = [];

        foreach ($options as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_array($value) && array_key_exists('value', $value)) {
                $val = $value['value'];
            } else {
                $val = $value;
            }

            if (!is_scalar($val)) {
                continue;
            }

            $normalizedKey = self::lowercase((string)$key);
            $normalized[$normalizedKey] = [
                'value' => self::lowercase((string)$val),
            ];
        }

        $result = $normalized === [] ? [] : ['options' => $normalized];
        self::log('normalizeOptions output', ['result' => $result]);

        return $result;
    }

    private static function lowercase(string $value): string
    {
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }

        return strtolower($value);
    }

    private static function calculatePrice(int $productId, array $options): ?array
    {
        self::log('calculatePrice request', [
            'productId' => $productId,
            'options' => $options,
        ]);

        try {
            $price = Configurator::calculate($productId, $options);
            if ($price !== null) {
                self::log('calculatePrice success', [
                    'productId' => $productId,
                    'options' => $options,
                    'priceFormatted' => $price['PRICE_FORMATTED'] ?? null,
                    'markup' => $price['MARKUP'] ?? null,
                ]);
                return $price;
            }
        } catch (\Throwable $exception) {
            self::log('calculatePrice exception', [
                'productId' => $productId,
                'options' => $options,
                'error' => $exception->getMessage(),
            ]);
            // ignore and fallback to base price
        }

        self::log('calculatePrice fallback base', [
            'productId' => $productId,
            'options' => $options,
        ]);

        return self::getBasePriceData($productId);
    }

    private static function getBasePriceData(int $productId): ?array
    {
        $baseGroup = CCatalogGroup::GetBaseGroup();
        if (!is_array($baseGroup) || !isset($baseGroup['ID'])) {
            return null;
        }

        $priceRow = CPrice::GetList([], ['PRODUCT_ID' => $productId, 'CATALOG_GROUP_ID' => (int)$baseGroup['ID']])->Fetch();
        if (!$priceRow || !isset($priceRow['PRICE'])) {
            self::log('getBasePriceData missing', [
                'productId' => $productId,
            ]);
            return null;
        }

        $value = (float)$priceRow['PRICE'];
        $currency = $priceRow['CURRENCY'] ?? 'RUB';

        self::log('getBasePriceData', [
            'productId' => $productId,
            'value' => $value,
            'currency' => $currency,
        ]);

        return [
            'PRICE_FORMATTED' => number_format($value, 0, '.', ' ') . ' ' . $currency,
            'BASE_PRICE' => $value,
            'CURRENCY' => $currency,
        ];
    }

    private static function log(string $message, array $context = []): void
    {
        static $skipMessages = [
            'normalizeOptions input',
            'normalizeOptions output',
        ];

        foreach ($skipMessages as $skip) {
            if (strpos($message, $skip) === 0) {
                return;
            }
        }

        $path = $_SERVER['DOCUMENT_ROOT'] . self::LOG_PATH;
        $log = date('Y-m-d H:i:s') . ' ' . $message;

        if ($context !== []) {
            $encoded = json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
            if ($encoded !== false) {
                $log .= ' | ' . $encoded;
            }
        }

        $log .= PHP_EOL;
        error_log($log, 3, $path);
    }
}
