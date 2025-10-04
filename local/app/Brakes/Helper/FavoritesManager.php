<?php
namespace App\Brakes\Helper;

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

        return self::$currentItems;
    }

    public static function getClientState(): array
    {
        $items = self::getCurrentFavorites();

        return [
            'items' => $items,
            'count' => count($items),
            'isAuthorized' => self::$isAuthorized === true,
        ];
    }

    public static function isFavorite(int $productId): bool
    {
        return in_array($productId, self::getCurrentFavorites(), true);
    }

    public static function toggleProduct(int $productId): array
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return self::getCurrentFavorites();
        }

        self::ensureLoaded();

        if (self::$isAuthorized) {
            $userId = self::getCurrentUserId();
            if ($userId === 0) {
                return self::$currentItems;
            }

            if (in_array($productId, self::$currentItems, true)) {
                Favorites::removeProduct($userId, $productId);
                self::$currentItems = array_values(array_diff(self::$currentItems, [$productId]));
            } else {
                Favorites::addProduct($userId, $productId);
                self::$currentItems[] = $productId;
            }
        } else {
            $items = self::$currentItems;
            $index = array_search($productId, $items, true);

            if ($index !== false) {
                unset($items[$index]);
            } else {
                $items[] = $productId;
            }

            $items = Favorites::normalizeProductIds($items);
            self::setCookieFavorites($items);
            self::$currentItems = $items;
        }

        sort(self::$currentItems);

        return array_values(self::$currentItems);
    }

    public static function getFavoritesProductsData(array $productIds): array
    {
        $ids = Favorites::normalizeProductIds($productIds);

        if ($ids === []) {
            return [];
        }

        if (!Loader::includeModule('iblock')) {
            return [];
        }

        $elements = [];
        $result = \CIBlockElement::GetList(
            [],
            ['ID' => $ids],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY_LINK_PHOTO']
        );

        while ($row = $result->GetNext()) {
            $id = (int)$row['ID'];
            $elements[$id] = [
                'ID' => $id,
                'NAME' => $row['~NAME'] ?? $row['NAME'],
                'URL' => $row['DETAIL_PAGE_URL'],
                'PICTURE' => getPreviewImgCatalog($row['PROPERTY_LINK_PHOTO_VALUE']),
                'PRICE' => null,
                'PRICE_HTML' => null,
            ];
        }

        $basePrice = \CCatalogGroup::GetBaseGroup();
        if (is_array($basePrice) && isset($basePrice['ID'])) {
            $priceRes = \CPrice::GetList([], ['@PRODUCT_ID' => $ids, 'CATALOG_GROUP_ID' => (int)$basePrice['ID']]);
            while ($priceRow = $priceRes->Fetch()) {
                $productId = (int)$priceRow['PRODUCT_ID'];
                if (!isset($elements[$productId])) {
                    continue;
                }

                $value = isset($priceRow['PRICE']) ? (float)$priceRow['PRICE'] : null;
                if ($value === null) {
                    continue;
                }

                $formatted = number_format($value, 0, '.', ' ') . ' руб.';
                $elements[$productId]['PRICE'] = $formatted;
                $elements[$productId]['PRICE_HTML'] = $formatted;
            }
        }

        $ordered = [];
        foreach ($ids as $id) {
            if (isset($elements[$id])) {
                $ordered[] = $elements[$id];
            }
        }

        return $ordered;
    }

    public static function buildFavoritesPopupHtml(array $items): string
    {
        if ($items === []) {
            return '<div class="favorit-box__empty">В избранном пока нет товаров.</div>';
        }

        $templatePath = defined('SITE_TEMPLATE_PATH') ? SITE_TEMPLATE_PATH : '/local/templates/main';

        ob_start();

        foreach ($items as $item) {
            $id = (int)($item['ID'] ?? 0);
            if ($id <= 0) {
                continue;
            }

            $name = htmlspecialcharsbx($item['NAME'] ?? '');
            $url = htmlspecialcharsbx($item['URL'] ?? '#');
            $pictureUrl = !empty($item['PICTURE']) ? htmlspecialcharsbx($item['PICTURE']) : '';
            $price = $item['PRICE'] ?? null;
            ?>
            <a class="favorit-box__item" data-fls-like-product="<?= $id ?>" href="<?= $url ?>">
                <div class="favorit-box__item-foto">
                    <img class="favorit-box__img" alt="<?= $name ?>" src="<?= $pictureUrl ?>">
                </div>
                <div class="favorit-box__inner">
                    <h3 class="favorit-box__item-title"><?= $name ?></h3>
                    <div class="favorit-box__item-bottom">
                        <div class="favorit-box__item-price"><?= htmlspecialcharsbx($price ?? '') ?></div>
                    </div>
                </div>
                <button class="favorit-box__delete" data-fls-like-button data-product-id="<?= $id ?>" aria-label="Удалить из избранного">
                    <img src="<?= $templatePath ?>/assets/img/favorite/trash.svg" alt="Удалить">
                </button>
            </a>
            <?php
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
            self::$currentItems = Favorites::getUserProductIds($userId);
        } else {
            self::$currentItems = self::getCookieFavorites();
        }
    }

    private static function resetCache(): void
    {
        self::$currentItems = null;
        self::$isAuthorized = null;
    }

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

        return Favorites::normalizeProductIds($decoded);
    }

    private static function setCookieFavorites(array $items): void
    {
        $items = Favorites::normalizeProductIds($items);

        $context = Context::getCurrent();
        $response = $context->getResponse();

        $cookie = new Cookie(self::COOKIE_NAME, json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
}
