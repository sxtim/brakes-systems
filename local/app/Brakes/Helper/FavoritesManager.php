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
        self::ensureLoaded();

        $items = array_keys(self::$currentItems);
        $meta = self::buildClientMeta();

        return [
            'items' => $items,
            'count' => count($items),
            'isAuthorized' => self::$isAuthorized === true,
            'meta' => $meta,
        ];
    }

    /**
     * @return array<int, array{options: array, optionsHash: string, price: ?array}>
     */
    private static function buildClientMeta(): array
    {
        $meta = [];

        foreach (self::$currentItems as $productId => $item) {
            $productId = (int)$productId;
            if ($productId <= 0) {
                continue;
            }

            $meta[$productId] = self::buildClientMetaEntry($productId, $item);
        }

        return $meta;
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
            return self::getCurrentFavorites();
        }

        self::ensureLoaded();
        $normalizedOptions = self::normalizeOptions($options);
        $itemState = self::makeItemState($normalizedOptions);

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                return self::getCurrentFavorites();
            }

            if (isset(self::$currentItems[$productId])) {
                Favorites::removeProduct($userId, $productId);
                unset(self::$currentItems[$productId]);
            } else {
                Favorites::addProduct($userId, $productId, $normalizedOptions);
                self::$currentItems[$productId] = $itemState;
            }
        } else {
            if (isset(self::$currentItems[$productId])) {
                unset(self::$currentItems[$productId]);
            } else {
                self::$currentItems[$productId] = $itemState;
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
            return false;
        }

        self::ensureLoaded();

        if (!isset(self::$currentItems[$productId])) {
            return false;
        }

        $normalizedOptions = self::normalizeOptions($options);
        $itemState = self::makeItemState($normalizedOptions);
        self::$currentItems[$productId]['options'] = $itemState['options'];
        self::$currentItems[$productId]['optionsHash'] = $itemState['optionsHash'];
        self::$currentItems[$productId]['priceHash'] = null;
        self::$currentItems[$productId]['priceData'] = null;
        self::$currentItems[$productId]['pricePublic'] = null;

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                return false;
            }

            Favorites::setProductOptions($userId, $productId, $normalizedOptions);
        } else {
            self::setCookieFavorites(self::$currentItems);
        }

        return true;
    }

    public static function getFavoritesProductsData(array $productIds): array
    {
        self::ensureLoaded();

        $ids = Favorites::normalizeProductIds($productIds);

        if ($ids === []) {
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            return [];
        }

        if (!Loader::includeModule('catalog')) {
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

            $currentItem = self::$currentItems[$id] ?? [];
            $metaEntry = self::buildClientMetaEntry($id, $currentItem);

            $optionsPayload = $metaEntry['options'] ?? $elements[$id]['OPTIONS'];
            $elements[$id]['OPTIONS'] = $optionsPayload;

            $optionsUnwrapped = $elements[$id]['OPTIONS_UNWRAPPED'] ?? self::unwrapOptionsPayload($optionsPayload);
            $elements[$id]['OPTIONS_UNWRAPPED'] = $optionsUnwrapped;
            $elements[$id]['SELECTED_OPTIONS'] = $elements[$id]['SELECTED_OPTIONS'] ?? self::flattenOptionValues($optionsUnwrapped);

            $priceData = self::$currentItems[$id]['priceData'] ?? null;
            $pricePublic = self::$currentItems[$id]['pricePublic'] ?? $metaEntry['price'];

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
            } elseif (is_array($pricePublic)) {
                $formattedPrice = $pricePublic['formatted'] ?? null;
                if (is_string($formattedPrice) && $formattedPrice !== '') {
                    $elements[$id]['PRICE_HTML'] = $formattedPrice;
                    $elements[$id]['PRICE'] = htmlspecialcharsback($formattedPrice);
                } else {
                    $elements[$id]['PRICE_HTML'] = null;
                    $elements[$id]['PRICE'] = null;
                }
            }

            $optionsAttr = '{}';
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

        $price = self::calculatePrice($productId, $normalized);

        if ($price !== null) {
            if (isset(self::$currentItems[$productId])) {
                $payload = self::prepareOptionsPayload($normalized);
                $hash = self::hashOptionsPayload($payload);
                self::$currentItems[$productId]['options'] = $payload;
                self::$currentItems[$productId]['optionsHash'] = $hash;
                self::$currentItems[$productId]['priceData'] = $price;
                self::$currentItems[$productId]['priceHash'] = $hash;
                self::$currentItems[$productId]['pricePublic'] = self::buildPublicPrice($price);
            }
        } else {
            if (isset(self::$currentItems[$productId])) {
                $payload = self::prepareOptionsPayload($normalized);
                $hash = self::hashOptionsPayload($payload);
                self::$currentItems[$productId]['options'] = $payload;
                self::$currentItems[$productId]['optionsHash'] = $hash;
                self::$currentItems[$productId]['priceData'] = null;
                self::$currentItems[$productId]['priceHash'] = null;
                self::$currentItems[$productId]['pricePublic'] = null;
            }
        }

        return $price;
    }

    public static function buildFavoritesPopupHtml(array $items): string
    {
        if ($items === []) {
            return '<div class="favorit-box__empty">Нет добавленных товаров.</div>';
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

    /**
     * @param array{
     *     options?: array,
     *     optionsHash?: string,
     *     priceData?: ?array,
     *     priceHash?: ?string,
     *     pricePublic?: ?array
     * } $item
     *
     * @return array{options: array, optionsHash: string, price: ?array}
     */
    private static function buildClientMetaEntry(int $productId, array $item): array
    {
        $optionsPayload = self::prepareOptionsPayload($item['options'] ?? []);
        $optionsHash = $item['optionsHash'] ?? self::hashOptionsPayload($optionsPayload);

        $priceData = self::getCachedPriceData($productId, $optionsPayload, $optionsHash);
        $pricePublic = self::buildPublicPrice($priceData);

        self::$currentItems[$productId]['options'] = $optionsPayload;
        self::$currentItems[$productId]['optionsHash'] = $optionsHash;
        self::$currentItems[$productId]['priceData'] = $priceData;
        self::$currentItems[$productId]['priceHash'] = $priceData !== null ? $optionsHash : null;
        self::$currentItems[$productId]['pricePublic'] = $pricePublic;

        return [
            'options' => $optionsPayload,
            'optionsHash' => $optionsHash,
            'price' => $pricePublic,
        ];
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
                $normalizedOptions = self::normalizeOptions($row['OPTIONS'] ?? []);
                $items[$productId] = self::makeItemState($normalizedOptions);
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
            } else {
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

                $normalizedOptions = self::normalizeOptions($options);
                $optionsHash = isset($item['optionsHash']) && is_string($item['optionsHash']) && $item['optionsHash'] !== ''
                    ? (string)$item['optionsHash']
                    : self::hashOptionsPayload($normalizedOptions);

                $pricePublic = self::normalizePublicPrice($item['price'] ?? null);

                $normalized[$productId] = [
                    'options' => $normalizedOptions,
                    'optionsHash' => $optionsHash,
                    'pricePublic' => $pricePublic,
                    'priceHash' => $pricePublic !== null ? $optionsHash : null,
                ];
                continue;
            }

            $productId = (int)$item;
            if ($productId <= 0) {
                continue;
            }

            $normalized[$productId] = [
                'options' => [],
                'optionsHash' => 'empty',
            ];
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param array<int, array{options: array}> $items
     */
    private static function setCookieFavorites(array $items): void
    {
        self::ensureLoaded();
        $meta = self::buildClientMeta();

        $payload = [];
        foreach ($items as $productId => $data) {
            $productId = (int)$productId;
            if ($productId <= 0) {
                continue;
            }

            $metaEntry = $meta[$productId] ?? self::buildClientMetaEntry($productId, $data);

            $payload[] = [
                'id' => $productId,
                'options' => $metaEntry['options'],
                'optionsHash' => $metaEntry['optionsHash'],
                'price' => $metaEntry['price'],
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

    public static function prepareOptionsPayload(array $options, bool $wrap = true): array
    {
        $map = self::normalizeOptionsMap($options);

        if (!$wrap) {
            return $map;
        }

        return $map === [] ? [] : ['options' => $map];
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
                if (array_key_exists('value', $value)) {
                    $value = $value['value'];
                } elseif (array_key_exists('VALUE', $value)) {
                    $value = $value['VALUE'];
                } else {
                    $value = reset($value);
                }
            }

            if ($value === null) {
                continue;
            }

            if (!is_scalar($value)) {
                continue;
            }

            $normalizedKey = self::lowercase((string)$key);
            $normalized[$normalizedKey] = self::lowercase((string)$value);
        }

        ksort($normalized);

        return $normalized;
    }

    private static function hashOptionsPayload(array $options): string
    {
        $map = self::prepareOptionsPayload($options, false);

        if ($map === []) {
            return 'empty';
        }

        ksort($map);

        $encoded = json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded !== false ? md5($encoded) : md5((string)microtime(true));
    }

    private static function makeItemState(array $optionsPayload): array
    {
        $hash = self::hashOptionsPayload($optionsPayload);

        return [
            'options' => $optionsPayload,
            'optionsHash' => $hash,
            'priceHash' => null,
            'priceData' => null,
            'pricePublic' => null,
        ];
    }

    /**
     * @param array{options?: array} $optionsPayload
     */
    private static function getCachedPriceData(int $productId, array $optionsPayload, string $optionsHash): ?array
    {
        $cachedHash = self::$currentItems[$productId]['priceHash'] ?? null;
        $cachedData = self::$currentItems[$productId]['priceData'] ?? null;

        if ($cachedData !== null && $cachedHash === $optionsHash) {
            return $cachedData;
        }

        $priceData = self::calculatePrice($productId, $optionsPayload);
        self::$currentItems[$productId]['priceHash'] = $priceData !== null ? $optionsHash : null;
        self::$currentItems[$productId]['priceData'] = $priceData;

        return $priceData;
    }

    private static function buildPublicPrice(?array $priceData): ?array
    {
        if ($priceData === null) {
            return null;
        }

        return [
            'formatted' => isset($priceData['PRICE_FORMATTED']) ? (string)$priceData['PRICE_FORMATTED'] : '',
            'basePrice' => isset($priceData['BASE_PRICE']) ? (float)$priceData['BASE_PRICE'] : null,
            'currency' => $priceData['CURRENCY'] ?? null,
            'discountPrice' => isset($priceData['DISCOUNT_PRICE']) ? (float)$priceData['DISCOUNT_PRICE'] : null,
            'markup' => isset($priceData['MARKUP']) ? (float)$priceData['MARKUP'] : null,
        ];
    }

    private static function normalizePublicPrice($value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $formatted = isset($value['formatted']) && is_string($value['formatted']) ? $value['formatted'] : '';
        $base = isset($value['basePrice']) ? (float)$value['basePrice'] : null;
        $currency = isset($value['currency']) && is_string($value['currency']) ? $value['currency'] : null;
        $discount = isset($value['discountPrice']) ? (float)$value['discountPrice'] : null;
        $markup = isset($value['markup']) ? (float)$value['markup'] : null;

        return [
            'formatted' => $formatted,
            'basePrice' => $base,
            'currency' => $currency,
            'discountPrice' => $discount,
            'markup' => $markup,
        ];
    }

    /**
     * @param array{
     *     PRICE_FORMATTED?: mixed,
     *     BASE_PRICE?: mixed,
     *     DISCOUNT_PRICE?: mixed,
     *     CURRENCY?: mixed
     * } $price
     */
    private static function normalizePriceOutput(array $price): array
    {
        $currency = is_string($price['CURRENCY'] ?? null) ? (string)($price['CURRENCY']) : 'RUB';

        if (!isset($price['BASE_PRICE']) && isset($price['DISCOUNT_PRICE'])) {
            $price['BASE_PRICE'] = (float)$price['DISCOUNT_PRICE'];
        }

        if (!isset($price['DISCOUNT_PRICE']) && isset($price['BASE_PRICE'])) {
            $price['DISCOUNT_PRICE'] = (float)$price['BASE_PRICE'];
        }

        $formatted = $price['PRICE_FORMATTED'] ?? null;

        if (!is_string($formatted) || trim($formatted) === '') {
            $value = null;

            if (isset($price['DISCOUNT_PRICE']) && is_numeric($price['DISCOUNT_PRICE'])) {
                $value = (float)$price['DISCOUNT_PRICE'];
            } elseif (isset($price['BASE_PRICE']) && is_numeric($price['BASE_PRICE'])) {
                $value = (float)$price['BASE_PRICE'];
            }

            if ($value !== null) {
                $price['PRICE_FORMATTED'] = number_format($value, 0, '.', ' ') . ' ' . $currency;
            } else {
                $price['PRICE_FORMATTED'] = '';
            }
        }

        return $price;
    }

    private static function normalizeOptions(array $options): array
    {
        $map = self::normalizeOptionsMap($options);
        $result = $map === [] ? [] : ['options' => $map];

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

        try {
            $price = Configurator::calculate($productId, $options);
            if ($price !== null) {
                return self::normalizePriceOutput($price);
            }
        } catch (\Throwable $exception) {
            // ignore and fallback to base price
        }


        $fallback = self::getBasePriceData($productId);

        return $fallback !== null ? self::normalizePriceOutput($fallback) : null;
    }

    private static function getBasePriceData(int $productId): ?array
    {
        $baseGroup = CCatalogGroup::GetBaseGroup();
        if (!is_array($baseGroup) || !isset($baseGroup['ID'])) {
            return self::buildZeroPrice();
        }

        $priceRow = CPrice::GetList([], ['PRODUCT_ID' => $productId, 'CATALOG_GROUP_ID' => (int)$baseGroup['ID']])->Fetch();
        if (!$priceRow || !isset($priceRow['PRICE'])) {
            return self::buildZeroPrice();
        }

        $value = (float)$priceRow['PRICE'];
        $currency = $priceRow['CURRENCY'] ?? 'RUB';


        return [
            'PRICE_FORMATTED' => number_format($value, 0, '.', ' ') . ' ' . $currency,
            'BASE_PRICE' => $value,
            'DISCOUNT_PRICE' => $value,
            'CURRENCY' => $currency,
        ];
    }

    private static function buildZeroPrice(): array
    {
        return [
            'PRICE_FORMATTED' => '0',
            'BASE_PRICE' => 0.0,
            'DISCOUNT_PRICE' => 0.0,
            'CURRENCY' => 'RUB',
        ];
    }

}
