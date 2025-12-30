<?php
namespace App\Brakes\Helper;

use App\Brakes\Helper\Image;
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
     * @var array<string, array{productId: int, context: array, options: array, optionsHash: string, priceData: ?array, priceHash: ?string, pricePublic: ?array, rowId: ?int}>
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
        $metaByProduct = [];
        foreach ($meta as $entry) {
            $productId = isset($entry['productId']) ? (int)$entry['productId'] : 0;
            if ($productId <= 0) {
                continue;
            }
            if (!isset($metaByProduct[$productId])) {
                $metaByProduct[$productId] = $entry;
            }
        }

        return [
            'items' => $items,
            'count' => count($items),
            'isAuthorized' => self::$isAuthorized === true,
            'meta' => $meta,
            'metaByProduct' => $metaByProduct,
        ];
    }

    /**
     * @return array<string, array{productId: int, options: array, optionsHash: string, price: ?array, context: array}>
     */
    private static function buildClientMeta(): array
    {
        $meta = [];

        foreach (self::$currentItems as $key => $item) {
            $productId = (int)($item['productId'] ?? 0);
            if ($productId <= 0 || !is_string($key) || $key === '') {
                continue;
            }

            $meta[$key] = self::buildClientMetaEntry($key, $item);
        }

        return $meta;
    }

    public static function isFavorite(int $productId, array $context = []): bool
    {
        self::ensureLoaded();

        $key = self::buildFavoriteKey($productId, self::normalizeContext($context));
        if ($key !== '' && isset(self::$currentItems[$key])) {
            return true;
        }

        foreach (self::$currentItems as $item) {
            if ((int)($item['productId'] ?? 0) === $productId) {
                return true;
            }
        }

        return false;
    }

    public static function toggleProduct(int $productId, array $options = []): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return self::getCurrentFavorites();
        }

        self::ensureLoaded();
        $normalizedPayload = self::normalizePayload($options);
        $favoriteKey = self::buildFavoriteKey($productId, $normalizedPayload['context']);
        if ($favoriteKey === '') {
            return self::getCurrentFavorites();
        }
        $itemState = self::makeItemState($normalizedPayload, $productId);

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                return self::getCurrentFavorites();
            }

            if (isset(self::$currentItems[$favoriteKey])) {
                $rowId = (int)(self::$currentItems[$favoriteKey]['rowId'] ?? 0);
                if ($rowId > 0) {
                    Favorites::removeRow($userId, $rowId);
                } else {
                    Favorites::removeProduct($userId, $productId);
                }
                unset(self::$currentItems[$favoriteKey]);
            } else {
                $rowId = Favorites::addProduct($userId, $productId, $normalizedPayload);
                $itemState['rowId'] = $rowId > 0 ? $rowId : null;
                self::$currentItems[$favoriteKey] = $itemState;
            }
        } else {
            if (isset(self::$currentItems[$favoriteKey])) {
                unset(self::$currentItems[$favoriteKey]);
            } else {
                self::$currentItems[$favoriteKey] = $itemState;
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

        $normalizedPayload = self::normalizePayload($options);
        $favoriteKey = self::buildFavoriteKey($productId, $normalizedPayload['context']);
        if ($favoriteKey === '' || !isset(self::$currentItems[$favoriteKey])) {
            return false;
        }

        $itemState = self::makeItemState($normalizedPayload, $productId);
        self::$currentItems[$favoriteKey]['options'] = $itemState['options'];
        self::$currentItems[$favoriteKey]['context'] = $itemState['context'];
        self::$currentItems[$favoriteKey]['optionsHash'] = $itemState['optionsHash'];
        self::$currentItems[$favoriteKey]['priceHash'] = null;
        self::$currentItems[$favoriteKey]['priceData'] = null;
        self::$currentItems[$favoriteKey]['pricePublic'] = null;

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                return false;
            }

            $rowId = (int)(self::$currentItems[$favoriteKey]['rowId'] ?? 0);
            if ($rowId > 0) {
                Favorites::setRowOptions($userId, $rowId, $normalizedPayload);
            } else {
                Favorites::setProductOptions($userId, $productId, $normalizedPayload);
            }
        } else {
            self::setCookieFavorites(self::$currentItems);
        }

        return true;
    }

    public static function getFavoritesProductsData(array $favoriteKeys): array
    {
        self::ensureLoaded();

        $keys = self::normalizeFavoriteKeys($favoriteKeys);

        if ($keys === []) {
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            return [];
        }

        if (!Loader::includeModule('catalog')) {
            return [];
        }

        $lookup = [];
        $productIds = [];
        foreach ($keys as $key) {
            $item = self::$currentItems[$key] ?? null;
            if ($item) {
                $productId = (int)($item['productId'] ?? 0);
                $context = is_array($item['context'] ?? null) ? $item['context'] : [];
            } else {
                $parsed = self::parseFavoriteKey($key);
                $productId = (int)($parsed['productId'] ?? 0);
                $context = is_array($parsed['context'] ?? null) ? $parsed['context'] : [];
            }

            if ($productId <= 0) {
                continue;
            }

            $lookup[$key] = [
                'productId' => $productId,
                'context' => $context,
            ];
            $productIds[$productId] = true;
        }

        $ids = array_keys($productIds);
        if ($ids === []) {
            return [];
        }

        $select = [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
            'DETAIL_PAGE_URL',
            'PROPERTY_LINK_PHOTO',
            'PROPERTY_LINK_PHOTO_FILE',
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

            $pictureData = null;

            $fileValue = $properties['LINK_PHOTO_FILE']['VALUE'] ?? $fields['PROPERTY_LINK_PHOTO_FILE_VALUE'] ?? null;
            if (is_array($fileValue)) {
                $fileIds = array_values(array_filter(array_map(static fn($value) => (int)$value, $fileValue)));
            } elseif ($fileValue !== null && $fileValue !== '') {
                $fileIds = [(int)$fileValue];
            } else {
                $fileIds = [];
            }

            $fileId = $fileIds[0] ?? 0;
            if ($fileId > 0) {
                $pictureData = Image::resizeByPreset($fileId, Image::PRESET_CATALOG_TILE);
            }

            if ($pictureData === null || empty($pictureData['src'])) {
                $fallback = getPreviewImgCatalog($fields['PROPERTY_LINK_PHOTO_VALUE'] ?? '');
                if (is_string($fallback) && $fallback !== '') {
                    $pictureData = [
                        'src' => $fallback,
                        'width' => 0,
                        'height' => 0,
                        'cached' => false,
                    ];
                }
            }

            $pictureSrc = is_array($pictureData) ? (string)($pictureData['src'] ?? '') : '';

            $elements[$id] = [
                'FIELDS' => $fields,
                'PROPERTIES' => $properties,
                'ID' => $id,
                'NAME' => $name,
                'DETAIL_PAGE_URL' => $detailUrl,
                'PICTURE' => $pictureSrc,
                'IMAGE' => $pictureData,
                'DETAILS' => $details,
                'COLORS' => $colors,
            ];
        }

        $ordered = [];
        foreach ($keys as $key) {
            if (!isset($lookup[$key])) {
                continue;
            }

            $productId = (int)($lookup[$key]['productId'] ?? 0);
            if ($productId <= 0 || !isset($elements[$productId])) {
                continue;
            }

            $base = $elements[$productId];
            $fields = $base['FIELDS'] ?? [];
            $detailUrl = $base['DETAIL_PAGE_URL'] ?? '#';
            $itemState = self::$currentItems[$key] ?? [];
            $context = $itemState['context'] ?? $lookup[$key]['context'] ?? [];
            $context = self::normalizeContext(is_array($context) ? $context : []);
            if ($context === []) {
                $lookupContext = $lookup[$key]['context'] ?? [];
                $context = self::normalizeContext(is_array($lookupContext) ? $lookupContext : []);
            }
            if ($context === []) {
                $parsed = self::parseFavoriteKey($key);
                $context = self::normalizeContext($parsed['context'] ?? []);
            }
            if ($itemState === []) {
                $itemState = [
                    'productId' => $productId,
                    'context' => $context,
                    'options' => [],
                ];
            }
            $detailUrlContext = self::buildDetailUrlWithContext($fields, $context);
            $contextLabel = self::buildContextLabel($context, (int)($fields['IBLOCK_ID'] ?? 0));
            $contextPath = self::buildContextSectionPath($context, (int)($fields['IBLOCK_ID'] ?? 0));

            $optionsRaw = $itemState['options'] ?? [];
            $optionsRaw = is_array($optionsRaw) ? $optionsRaw : [];
            $optionsUnwrapped = self::unwrapOptionsPayload($optionsRaw);
            $selectedValues = self::flattenOptionValues($optionsUnwrapped);

            $item = [
                'ID' => $productId,
                'FAVORITES_KEY' => $key,
                'NAME' => $base['NAME'] ?? '',
                'URL' => $detailUrlContext ?: $detailUrl,
                'CANONICAL_URL' => $detailUrl,
                'PICTURE' => $base['PICTURE'] ?? '',
                'IMAGE' => $base['IMAGE'] ?? null,
                'DETAILS' => $base['DETAILS'] ?? [],
                'COLORS' => $base['COLORS'] ?? [],
                'PRICE' => null,
                'PRICE_HTML' => null,
                'PRICE_DATA' => null,
                'OPTIONS' => $optionsRaw,
                'OPTIONS_UNWRAPPED' => $optionsUnwrapped,
                'SELECTED_OPTIONS' => $selectedValues,
                'CONTEXT' => $context,
                'CONTEXT_LABEL' => $contextLabel,
                'CONTEXT_URL' => $detailUrlContext ?: $detailUrl,
                'CARD' => [
                    'ID' => $productId,
                    'NAME' => $base['NAME'] ?? '',
                    'DETAIL_PAGE_URL' => $detailUrlContext ?: $detailUrl,
                    'CONTEXT_LABEL' => $contextLabel,
                    'CONTEXT_SECTION_ID' => isset($context['section_id']) ? (int)$context['section_id'] : 0,
                    'CONTEXT_SECTION_PATH' => $contextPath,
                    'IMAGE' => $base['IMAGE'] ?? null,
                    'IMG' => $base['PICTURE'] ?? '',
                    'DETAILS' => $base['DETAILS'] ?? [],
                    'COLORS' => $base['COLORS'] ?? [],
                    'SELECTED' => $selectedValues,
                    'EXPAND_FEATURES' => true,
                    'FAVORITE_KEY' => $key,
                ],
            ];

            $metaEntry = self::buildClientMetaEntry($key, $itemState);
            $optionsPayload = $metaEntry['options'] ?? $item['OPTIONS'];
            $item['OPTIONS'] = $optionsPayload;

            $optionsUnwrapped = $item['OPTIONS_UNWRAPPED'] ?? self::unwrapOptionsPayload($optionsPayload);
            $item['OPTIONS_UNWRAPPED'] = $optionsUnwrapped;
            $item['SELECTED_OPTIONS'] = $item['SELECTED_OPTIONS'] ?? self::flattenOptionValues($optionsUnwrapped);

            $priceData = $itemState['priceData'] ?? null;
            $pricePublic = $itemState['pricePublic'] ?? $metaEntry['price'];

            if ($priceData !== null) {
                $item['PRICE_DATA'] = $priceData;
                $formattedPrice = $priceData['PRICE_FORMATTED'] ?? null;
                if (is_string($formattedPrice) && $formattedPrice !== '') {
                    $item['PRICE_HTML'] = $formattedPrice;
                    $item['PRICE'] = htmlspecialcharsback($formattedPrice);
                } else {
                    $item['PRICE_HTML'] = null;
                    $item['PRICE'] = null;
                }
            } elseif (is_array($pricePublic)) {
                $formattedPrice = $pricePublic['formatted'] ?? null;
                if (is_string($formattedPrice) && $formattedPrice !== '') {
                    $item['PRICE_HTML'] = $formattedPrice;
                    $item['PRICE'] = htmlspecialcharsback($formattedPrice);
                } else {
                    $item['PRICE_HTML'] = null;
                    $item['PRICE'] = null;
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

            $card = $item['CARD'];
            $card['PRICE_HTML'] = isset($item['PRICE_HTML']) && is_string($item['PRICE_HTML'])
                ? htmlspecialcharsback($item['PRICE_HTML'])
                : '';
            $card['OPTIONS_ATTR'] = $optionsAttr;
            $card['BUY'] = [
                'NAME' => $item['NAME'],
                'URL' => $item['URL'],
            ];
            $card['SELECTED'] = $item['SELECTED_OPTIONS'] ?? $card['SELECTED'] ?? [];

            $item['CARD'] = $card;
            $item['OPTIONS_ATTR'] = $optionsAttr;

            $ordered[] = $item;
        }

        return $ordered;
    }

    public static function getProductPrice(int $productId, array $options): ?array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return null;
        }

        $normalizedPayload = self::normalizePayload($options);
        $normalized = ['options' => $normalizedPayload['options']];
        $favoriteKey = self::buildFavoriteKey($productId, $normalizedPayload['context']);

        $price = self::calculatePrice($productId, $normalized);

        if ($price !== null) {
            if ($favoriteKey !== '' && isset(self::$currentItems[$favoriteKey])) {
                $payload = self::prepareOptionsPayload($normalized);
                $hash = self::hashOptionsPayload($payload);
                self::$currentItems[$favoriteKey]['options'] = $payload;
                self::$currentItems[$favoriteKey]['context'] = $normalizedPayload['context'];
                self::$currentItems[$favoriteKey]['optionsHash'] = $hash;
                self::$currentItems[$favoriteKey]['priceData'] = $price;
                self::$currentItems[$favoriteKey]['priceHash'] = $hash;
                self::$currentItems[$favoriteKey]['pricePublic'] = self::buildPublicPrice($price);
            }
        } else {
            if ($favoriteKey !== '' && isset(self::$currentItems[$favoriteKey])) {
                $payload = self::prepareOptionsPayload($normalized);
                $hash = self::hashOptionsPayload($payload);
                self::$currentItems[$favoriteKey]['options'] = $payload;
                self::$currentItems[$favoriteKey]['context'] = $normalizedPayload['context'];
                self::$currentItems[$favoriteKey]['optionsHash'] = $hash;
                self::$currentItems[$favoriteKey]['priceData'] = null;
                self::$currentItems[$favoriteKey]['priceHash'] = null;
                self::$currentItems[$favoriteKey]['pricePublic'] = null;
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

                $imageData = [];
                if (isset($card['IMAGE']) && is_array($card['IMAGE'])) {
                    $imageData = $card['IMAGE'];
                } elseif (isset($item['IMAGE']) && is_array($item['IMAGE'])) {
                    $imageData = $item['IMAGE'];
                }

                if ($imageData !== []) {
                    $card['IMAGE'] = $imageData;
                }

                if (!isset($card['IMG']) || $card['IMG'] === '') {
                    if (!empty($imageData['src'])) {
                        $card['IMG'] = $imageData['src'];
                    } else {
                        $card['IMG'] = $item['PICTURE'] ?? '';
                    }
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
    private static function buildClientMetaEntry(string $key, array $item): array
    {
        $productId = (int)($item['productId'] ?? 0);
        $optionsPayload = self::prepareOptionsPayload($item['options'] ?? []);
        $optionsHash = $item['optionsHash'] ?? self::hashOptionsPayload($optionsPayload);

        $priceData = self::getCachedPriceData($key, $productId, $optionsPayload, $optionsHash);
        $pricePublic = self::buildPublicPrice($priceData);
        $context = self::normalizeContext($item['context'] ?? []);
        if ($context === []) {
            $parsed = self::parseFavoriteKey($key);
            $context = self::normalizeContext($parsed['context'] ?? []);
        }

        if (isset(self::$currentItems[$key])) {
            self::$currentItems[$key]['options'] = $optionsPayload;
            self::$currentItems[$key]['optionsHash'] = $optionsHash;
            self::$currentItems[$key]['priceData'] = $priceData;
            self::$currentItems[$key]['priceHash'] = $priceData !== null ? $optionsHash : null;
            self::$currentItems[$key]['pricePublic'] = $pricePublic;
            if ($context !== []) {
                self::$currentItems[$key]['context'] = $context;
            }
        }

        return [
            'productId' => $productId,
            'options' => $optionsPayload,
            'optionsHash' => $optionsHash,
            'price' => $pricePublic,
            'context' => $context,
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
            foreach ($rows as $row) {
                $productId = (int)($row['PRODUCT_ID'] ?? 0);
                if ($productId <= 0) {
                    continue;
                }
                $normalizedPayload = self::normalizePayload($row['OPTIONS'] ?? []);
                $favoriteKey = self::buildFavoriteKey($productId, $normalizedPayload['context']);
                if ($favoriteKey === '') {
                    continue;
                }
                $itemState = self::makeItemState($normalizedPayload, $productId, (int)($row['ID'] ?? 0));
                $items[$favoriteKey] = $itemState;
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

    public static function buildFavoriteKey(int $productId, array $context = []): string
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return '';
        }

        $context = self::normalizeContext($context);
        $sectionId = isset($context['section_id']) ? (int)$context['section_id'] : 0;
        $sectionPath = isset($context['section_path']) ? (string)$context['section_path'] : '';

        if ($sectionId > 0) {
            return $productId . ':s' . $sectionId;
        }

        $sectionPath = trim($sectionPath, " \t\n\r\0\x0B/");
        if ($sectionPath !== '') {
            return $productId . ':p' . $sectionPath;
        }

        return $productId . ':n';
    }

    public static function normalizeFavoriteKeys(array $items): array
    {
        $normalized = [];
        $seen = [];

        foreach ($items as $item) {
            $key = '';
            $productId = 0;
            $context = [];

            if (is_string($item)) {
                if (strpos($item, ':') !== false) {
                    $key = $item;
                } else {
                    $productId = (int)$item;
                }
            } elseif (is_int($item)) {
                $productId = $item;
            } elseif (is_array($item)) {
                if (isset($item['key']) && is_string($item['key']) && $item['key'] !== '') {
                    $key = $item['key'];
                } elseif (isset($item['favoriteKey']) && is_string($item['favoriteKey']) && $item['favoriteKey'] !== '') {
                    $key = $item['favoriteKey'];
                } elseif (isset($item['FAVORITE_KEY']) && is_string($item['FAVORITE_KEY']) && $item['FAVORITE_KEY'] !== '') {
                    $key = $item['FAVORITE_KEY'];
                }

                if (isset($item['PRODUCT_ID'])) {
                    $productId = (int)$item['PRODUCT_ID'];
                } elseif (isset($item['productId'])) {
                    $productId = (int)$item['productId'];
                } elseif (isset($item['id'])) {
                    $productId = (int)$item['id'];
                } elseif (isset($item['ID'])) {
                    $productId = (int)$item['ID'];
                }

                if (isset($item['context']) && is_array($item['context'])) {
                    $context = $item['context'];
                } elseif (isset($item['CONTEXT']) && is_array($item['CONTEXT'])) {
                    $context = $item['CONTEXT'];
                }
            }

            if ($key === '' && $productId > 0) {
                $key = self::buildFavoriteKey($productId, $context);
            }

            if ($key === '') {
                continue;
            }

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $normalized[] = $key;
            }
        }

        return $normalized;
    }

    private static function parseFavoriteKey(string $key): array
    {
        $key = trim($key);
        if ($key === '') {
            return [];
        }

        if (preg_match('/^(\\d+):(s|p|n)(.*)$/', $key, $matches)) {
            $productId = (int)$matches[1];
            $type = $matches[2];
            $value = $matches[3] ?? '';
            $context = [];

            if ($type === 's') {
                $sectionId = (int)$value;
                if ($sectionId > 0) {
                    $context['section_id'] = $sectionId;
                }
            } elseif ($type === 'p') {
                $sectionPath = trim((string)$value, " \t\n\r\0\x0B/");
                if ($sectionPath !== '') {
                    $context['section_path'] = $sectionPath;
                }
            }

            return [
                'productId' => $productId,
                'context' => $context,
            ];
        }

        $productId = (int)$key;
        if ($productId > 0) {
            return [
                'productId' => $productId,
                'context' => [],
            ];
        }

        return [];
    }

    private static function buildContextSectionPath(array $context, int $iblockId): string
    {
        $path = '';

        if (!empty($context['section_path']) && is_string($context['section_path'])) {
            $path = trim($context['section_path'], " \t\n\r\0\x0B/");
        } elseif (!empty($context['section_id']) && $context['section_id'] > 0 && Loader::includeModule('iblock')) {
            $nav = \CIBlockSection::GetNavChain($iblockId, (int)$context['section_id'], ['CODE']);
            $codes = [];
            while ($row = $nav->Fetch()) {
                if (!empty($row['CODE'])) {
                    $codes[] = $row['CODE'];
                }
            }
            if (!empty($codes)) {
                $path = implode('/', $codes);
            }
        }

        return $path;
    }

    private static function buildContextLabel(array $context, int $iblockId): string
    {
        if (empty($context)) {
            return '';
        }

        if (!empty($context['section_id']) && Loader::includeModule('iblock')) {
            $nav = \CIBlockSection::GetNavChain($iblockId, (int)$context['section_id'], ['NAME']);
            $names = [];
            while ($row = $nav->Fetch()) {
                if (!empty($row['NAME'])) {
                    $names[] = $row['NAME'];
                }
            }
            $names = array_slice($names, -3);
            $names = array_values(array_filter($names, static fn($name) => is_string($name) && trim($name) !== ''));
            return implode(' ', $names);
        }

        return '';
    }

    private static function buildDetailUrlWithContext(array $fields, array $context): string
    {
        $detailUrl = isset($fields['DETAIL_PAGE_URL']) ? (string)$fields['DETAIL_PAGE_URL'] : '#';
        $iblockId = (int)($fields['IBLOCK_ID'] ?? 0);
        $sectionPath = self::buildContextSectionPath($context, $iblockId);

        if ($sectionPath === '') {
            return $detailUrl;
        }

        $fieldsWithContext = $fields;
        $fieldsWithContext['SECTION_CODE_PATH'] = $sectionPath;
        $sectionParts = array_values(array_filter(explode('/', $sectionPath), static fn($part) => $part !== ''));
        if (!empty($sectionParts)) {
            $fieldsWithContext['SECTION_CODE'] = end($sectionParts);
        }

        $template = '';
        if ($iblockId > 0 && Loader::includeModule('iblock')) {
            $template = (string)\CIBlock::GetArrayByID($iblockId, 'DETAIL_PAGE_URL');
        }

        if ($template !== '') {
            $resolved = \CIBlock::ReplaceDetailUrl($template, $fieldsWithContext, false, 'E');
            if (strpos($resolved, $sectionPath) !== false) {
                return $resolved;
            }
        }

        if (strpos($detailUrl, '#') !== false) {
            $resolved = \CIBlock::ReplaceDetailUrl($detailUrl, $fieldsWithContext, false, 'E');
            if (strpos($resolved, $sectionPath) !== false) {
                return $resolved;
            }
        }

        $elementCode = isset($fields['CODE']) ? (string)$fields['CODE'] : '';
        if ($elementCode !== '') {
            return '/catalog/' . $sectionPath . '/' . $elementCode . '/';
        }

        return $detailUrl;
    }

    private static function pruneMissingProducts(): void
    {
        if (self::$currentItems === null || self::$currentItems === []) {
            return;
        }

        $ids = [];
        foreach (self::$currentItems as $item) {
            $productId = (int)($item['productId'] ?? 0);
            if ($productId > 0) {
                $ids[$productId] = true;
            }
        }
        $ids = array_keys($ids);
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

        $removeItems = [];
        foreach (self::$currentItems as $key => $item) {
            $productId = (int)($item['productId'] ?? 0);
            if ($productId > 0 && in_array($productId, $missing, true)) {
                $removeItems[$key] = $item;
            }
        }

        foreach ($removeItems as $key => $item) {
            unset(self::$currentItems[$key]);
        }

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId > 0) {
                $removedByProduct = [];
                foreach ($removeItems as $item) {
                    $productId = (int)($item['productId'] ?? 0);
                    if ($productId <= 0) {
                        continue;
                    }
                    $rowId = (int)($item['rowId'] ?? 0);
                    if ($rowId > 0) {
                        Favorites::removeRow($userId, $rowId);
                    } elseif (!isset($removedByProduct[$productId])) {
                        Favorites::removeProduct($userId, $productId);
                        $removedByProduct[$productId] = true;
                    }
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
        $context = isset($item['CONTEXT']) && is_array($item['CONTEXT']) ? $item['CONTEXT'] : [];
        $contextSectionId = isset($context['section_id']) ? (int)$context['section_id'] : (int)($item['CONTEXT_SECTION_ID'] ?? 0);
        $contextSectionPath = isset($context['section_path']) ? (string)$context['section_path'] : (string)($item['CONTEXT_SECTION_PATH'] ?? '');
        $favoriteKey = isset($item['FAVORITES_KEY']) ? (string)$item['FAVORITES_KEY'] : '';
        if ($favoriteKey === '') {
            $favoriteKey = self::buildFavoriteKey($id, [
                'section_id' => $contextSectionId,
                'section_path' => $contextSectionPath,
            ]);
        }

        $imageData = [];
        if (isset($item['IMAGE']) && is_array($item['IMAGE'])) {
            $imageData = $item['IMAGE'];
        }

        $pictureUrlRaw = !empty($imageData['src']) ? (string)$imageData['src'] : (string)($item['PICTURE'] ?? '');
        $pictureUrl = $pictureUrlRaw !== '' ? htmlspecialcharsbx($pictureUrlRaw) : '';
        $pictureWidth = isset($imageData['width']) ? (int)$imageData['width'] : 0;
        $pictureHeight = isset($imageData['height']) ? (int)$imageData['height'] : 0;
        $priceHtml = $item['PRICE_HTML'] ?? null;
        $priceText = $item['PRICE'] ?? null;

        ob_start();
        ?>
        <a class="favorit-box__item" data-fls-like-product="<?= $id ?>"<?php if ($favoriteKey !== '') { ?> data-favorite-key="<?= htmlspecialcharsbx($favoriteKey) ?>"<?php } ?><?php if ($contextSectionId > 0) { ?> data-context-section-id="<?= $contextSectionId ?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?= htmlspecialcharsbx($contextSectionPath) ?>"<?php } ?> href="<?= $url ?>">
            <div class="favorit-box__item-foto">
                <img class="favorit-box__img"
                     alt="<?= $name ?>"
                     src="<?= $pictureUrl ?>"
                     <?php if ($pictureWidth > 0) { ?>width="<?= $pictureWidth ?>"<?php } ?>
                     <?php if ($pictureHeight > 0) { ?>height="<?= $pictureHeight ?>"<?php } ?>
                     loading="lazy">
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
            <button class="favorit-box__delete" data-fls-like-button data-product-id="<?= $id ?>"<?php if ($favoriteKey !== '') { ?> data-favorite-key="<?= htmlspecialcharsbx($favoriteKey) ?>"<?php } ?><?php if ($contextSectionId > 0) { ?> data-context-section-id="<?= $contextSectionId ?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?= htmlspecialcharsbx($contextSectionPath) ?>"<?php } ?> aria-label="Remove from favorites">
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
     * @return array<string, array{productId: int, context: array, options: array}>
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
            $productId = 0;
            $context = [];
            $options = [];
            $favoriteKey = '';

            if (is_array($item)) {
                if (isset($item['key']) && is_string($item['key']) && $item['key'] !== '') {
                    $favoriteKey = $item['key'];
                } elseif (isset($item['favoriteKey']) && is_string($item['favoriteKey']) && $item['favoriteKey'] !== '') {
                    $favoriteKey = $item['favoriteKey'];
                } elseif (isset($item['FAVORITE_KEY']) && is_string($item['FAVORITE_KEY']) && $item['FAVORITE_KEY'] !== '') {
                    $favoriteKey = $item['FAVORITE_KEY'];
                }

                if (isset($item['id'])) {
                    $productId = (int)$item['id'];
                } elseif (isset($item['PRODUCT_ID'])) {
                    $productId = (int)$item['PRODUCT_ID'];
                }

                if (isset($item['options']) && is_array($item['options'])) {
                    $options = $item['options'];
                } elseif (isset($item['OPTIONS']) && is_array($item['OPTIONS'])) {
                    $options = $item['OPTIONS'];
                }

                if (isset($item['context']) && is_array($item['context'])) {
                    $context = $item['context'];
                }
            } else {
                $productId = (int)$item;
            }

            if ($favoriteKey !== '' && $productId <= 0) {
                $parsed = self::parseFavoriteKey($favoriteKey);
                $productId = (int)($parsed['productId'] ?? 0);
                $context = is_array($parsed['context'] ?? null) ? $parsed['context'] : $context;
            }

            if ($productId <= 0) {
                continue;
            }

            $normalizedPayload = self::normalizePayload(['options' => $options, 'context' => $context]);
            $favoriteKey = $favoriteKey !== '' ? $favoriteKey : self::buildFavoriteKey($productId, $normalizedPayload['context']);
            if ($favoriteKey === '') {
                continue;
            }

            $itemState = self::makeItemState($normalizedPayload, $productId);

            if (isset($item['optionsHash']) && is_string($item['optionsHash']) && $item['optionsHash'] !== '') {
                $itemState['optionsHash'] = (string)$item['optionsHash'];
            }

            $pricePublic = self::normalizePublicPrice($item['price'] ?? null);
            if ($pricePublic !== null) {
                $itemState['pricePublic'] = $pricePublic;
                $itemState['priceHash'] = $itemState['optionsHash'];
            }

            $normalized[$favoriteKey] = $itemState;
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
        foreach ($items as $key => $data) {
            $productId = (int)($data['productId'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $metaEntry = $meta[$key] ?? self::buildClientMetaEntry($key, $data);

            $payload[] = [
                'key' => $key,
                'id' => $productId,
                'options' => $metaEntry['options'],
                'optionsHash' => $metaEntry['optionsHash'],
                'price' => $metaEntry['price'],
                'context' => $data['context'] ?? [],
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

    private static function makeItemState(array $payload, int $productId, int $rowId = 0): array
    {
        $optionsPayload = self::prepareOptionsPayload($payload['options'] ?? [], true);
        $context = self::normalizeContext($payload['context'] ?? []);

        $hash = self::hashOptionsPayload($optionsPayload);

        return [
            'productId' => $productId,
            'options' => $optionsPayload,
            'context' => $context,
            'optionsHash' => $hash,
            'priceHash' => null,
            'priceData' => null,
            'pricePublic' => null,
            'rowId' => $rowId > 0 ? $rowId : null,
        ];
    }

    /**
     * @param array{options?: array} $optionsPayload
     */
    private static function getCachedPriceData(string $key, int $productId, array $optionsPayload, string $optionsHash): ?array
    {
        $cachedHash = self::$currentItems[$key]['priceHash'] ?? null;
        $cachedData = self::$currentItems[$key]['priceData'] ?? null;

        if ($cachedData !== null && $cachedHash === $optionsHash) {
            return $cachedData;
        }

        $priceData = self::calculatePrice($productId, $optionsPayload);
        if (isset(self::$currentItems[$key])) {
            self::$currentItems[$key]['priceHash'] = $priceData !== null ? $optionsHash : null;
            self::$currentItems[$key]['priceData'] = $priceData;
        }

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

    private static function normalizePayload(array $payload): array
    {
        $options = [];
        if (isset($payload['options']) && is_array($payload['options'])) {
            $options = $payload['options'];
        } elseif (!empty($payload) && !array_key_exists('options', $payload)) {
            // backward compatibility: payload could be just options map
            $options = $payload;
        }

        $context = self::normalizeContext($payload['context'] ?? []);

        return [
            'options' => self::prepareOptionsPayload($options, false),
            'context' => $context,
        ];
    }

    private static function normalizeContext($value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $sectionId = isset($value['section_id']) ? (int)$value['section_id'] : (isset($value['sectionId']) ? (int)$value['sectionId'] : 0);
        $sectionPath = isset($value['section_path']) ? (string)$value['section_path'] : (isset($value['sectionPath']) ? (string)$value['sectionPath'] : '');

        $context = [];
        if ($sectionId > 0) {
            $context['section_id'] = $sectionId;
        }
        if ($sectionPath !== '') {
            $sectionPath = trim((string)$sectionPath, " \t\n\r\0\x0B/");
            if ($sectionPath !== '') {
                $context['section_path'] = $sectionPath;
            }
        }

        return $context;
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
