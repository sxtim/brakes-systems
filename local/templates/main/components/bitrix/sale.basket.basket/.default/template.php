<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Loader;

$items = $arResult['ITEMS']['AnDelCanBuy'] ?? [];
if ($items === []) {
    echo '<div class="basket__message">Корзина пуста.</div>';
    return;
}

$partialPath = $_SERVER['DOCUMENT_ROOT'] . '/local/templates/main/components/bitrix/catalog/.default/bitrix/catalog.section/.default/partials/product-card.php';
if (!is_file($partialPath)) {
    echo '<div class="basket__message">Не найден шаблон карточки товара.</div>';
    return;
}

$normalize = static function (string $value): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }
    return strtolower($value);
};

$extractProps = static function (array $props): array {
    $map = [];
    foreach ($props as $prop) {
        if (!is_array($prop)) {
            continue;
        }
        $code = (string)($prop['CODE'] ?? '');
        if ($code === '') {
            continue;
        }
        $map[$code] = $prop['VALUE'] ?? '';
    }
    return $map;
};

$normalizePropValue = static function ($value): string {
    if (is_array($value)) {
        if (array_key_exists('VALUE', $value)) {
            $value = $value['VALUE'];
        } elseif (array_key_exists('value', $value)) {
            $value = $value['value'];
        } else {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($encoded) ? $encoded : '';
        }
    }

    if (is_string($value)) {
        $trimmed = trim($value);
        if ($trimmed !== '' && $trimmed[0] === '{') {
            $decoded = json_decode(htmlspecialcharsback($trimmed), true);
            if (is_array($decoded)) {
                if (array_key_exists('VALUE', $decoded)) {
                    return (string)$decoded['VALUE'];
                }
                if (array_key_exists('value', $decoded)) {
                    return (string)$decoded['value'];
                }
            }
        }
    }

    if ($value === null) {
        return '';
    }

    return (string)$value;
};

$extractPropsAll = static function ($propsAll): array {
    if (!is_array($propsAll)) {
        return [];
    }

    $map = [];
    foreach ($propsAll as $code => $value) {
        if (!is_string($code) || $code === '') {
            continue;
        }
        if (is_array($value)) {
            if (array_key_exists('VALUE', $value)) {
                $value = $value['VALUE'];
            } elseif (array_key_exists('value', $value)) {
                $value = $value['value'];
            }
        }
        if ($value === null) {
            continue;
        }
        $map[$code] = $value;
    }
    return $map;
};

$parseOptions = static function ($optionsRaw) use ($normalize): array {
    $raw = [];

    if (is_array($optionsRaw)) {
        if (isset($optionsRaw['options']) && is_array($optionsRaw['options'])) {
            $raw = $optionsRaw;
        } elseif (count($optionsRaw) === 1) {
            $first = reset($optionsRaw);
            if (is_string($first)) {
                $optionsRaw = $first;
            } else {
                $raw = $optionsRaw;
            }
        } else {
            $raw = $optionsRaw;
        }
    }

    if ($raw === [] && is_string($optionsRaw) && $optionsRaw !== '') {
        $decodedRaw = htmlspecialcharsback($optionsRaw);
        $decoded = json_decode($decodedRaw, true);
        if (is_array($decoded)) {
            $raw = $decoded;
        }
    }

    $options = $raw;
    if (isset($options['options']) && is_array($options['options'])) {
        $options = $options['options'];
    }

    if (class_exists(FavoritesManager::class)) {
        $normalized = FavoritesManager::prepareOptionsPayload($options, false);
        $selected = $normalized;
        $options = $normalized;
    } else {
    $selected = [];
    foreach ($options as $key => $value) {
        if (!is_string($key) || $key === '') {
            continue;
        }
        $normalizedKey = $normalize($key);
        if ($normalizedKey === 'electric_handbrake') {
            unset($options[$key]);
            continue;
        }
        if (is_array($value) && isset($value['value'])) {
            $value = $value['value'];
        }
        if (!is_scalar($value)) {
            continue;
        }
        $selected[$normalizedKey] = $normalize((string)$value);
    }
    }

    $optionsAttr = '{}';
    if ($options !== []) {
        $encoded = json_encode(['options' => $options], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (is_string($encoded) && $encoded !== '') {
            $optionsAttr = htmlspecialcharsbx($encoded);
        }
    }

    return [$options, $selected, $optionsAttr];
};

$buildDetailUrl = static function (string $detailUrl, string $contextPath): string {
    $detailUrl = trim($detailUrl);
    if ($detailUrl === '') {
        return '#';
    }

    $detailUrl = strtok($detailUrl, '?') ?: $detailUrl;
    $contextPath = trim($contextPath, " \t\n\r\0\x0B/");
    if ($contextPath === '') {
        return $detailUrl;
    }

    $path = (string)parse_url($detailUrl, PHP_URL_PATH);
    $path = trim($path, '/');
    if ($path === '') {
        return $detailUrl;
    }

    $parts = array_values(array_filter(explode('/', $path), 'strlen'));
    $elementCode = $parts !== [] ? end($parts) : '';
    if (!is_string($elementCode) || $elementCode === '') {
        return $detailUrl;
    }

    return '/catalog/' . $contextPath . '/' . $elementCode . '/';
};

$formatCurrency = static function (float $value, string $currency): string {
    if (Loader::includeModule('currency') && class_exists(\CCurrencyLang::class)) {
        return \CCurrencyLang::CurrencyFormat($value, $currency, true);
    }

    return number_format($value, 0, '.', ' ') . ' ' . $currency;
};

$basketTotalValue = 0.0;
$basketTotalCurrency = null;

$keys = [];
$metaByKey = [];

foreach ($items as $item) {
    $productId = (int)($item['PRODUCT_ID'] ?? 0);
    if ($productId <= 0) {
        continue;
    }

    $propsMap = $extractProps($item['PROPS'] ?? []);
    $propsAllMap = $extractPropsAll($item['PROPS_ALL'] ?? []);
    if ($propsAllMap !== []) {
        foreach ($propsAllMap as $code => $value) {
            if (!array_key_exists($code, $propsMap) || $propsMap[$code] === '' || $propsMap[$code] === null) {
                $propsMap[$code] = $value;
            }
        }
    }
    $contextSectionId = (int)$normalizePropValue($propsMap['CONTEXT_SECTION_ID'] ?? 0);
    $contextPath = trim($normalizePropValue($propsMap['CONTEXT_PATH'] ?? ''), " \t\n\r\0\x0B/");
    $contextLabel = $normalizePropValue($propsMap['CONTEXT_LABEL'] ?? '');

    $optionsRaw = $normalizePropValue($propsAllMap['OPTIONS_JSON'] ?? ($propsMap['OPTIONS_JSON'] ?? ($propsMap['OPTIONS'] ?? '')));
    [$options, $selected, $optionsAttr] = $parseOptions($optionsRaw);

    $context = [];
    if ($contextSectionId > 0) {
        $context['section_id'] = $contextSectionId;
    }
    if ($contextPath !== '') {
        $context['section_path'] = $contextPath;
    }

    $key = class_exists(FavoritesManager::class)
        ? FavoritesManager::buildFavoriteKey($productId, $context)
        : (string)$productId;

    $keys[] = $key;
    $metaByKey[$key] = [
        'item' => $item,
        'context_section_id' => $contextSectionId,
        'context_path' => $contextPath,
        'context_label' => $contextLabel,
        'options' => $options,
        'selected' => $selected,
        'options_attr' => $optionsAttr,
    ];
}

$cardsByKey = [];
if (class_exists(FavoritesManager::class) && $keys !== []) {
    $favoriteItems = FavoritesManager::getFavoritesProductsData($keys);
    foreach ($favoriteItems as $favoriteItem) {
        $key = (string)($favoriteItem['FAVORITES_KEY'] ?? '');
        if ($key === '' || empty($favoriteItem['CARD'])) {
            continue;
        }
        $cardsByKey[$key] = $favoriteItem['CARD'];
    }
}

?>
<main class="page">
    <div class="page__container page__container--single">
        <div class="page__main">
            <div class="main__inner">
                <h1 class="main__title"><?php $APPLICATION->ShowTitle(false); ?></h1>
                <div class="basket__body">
                    <div class="basket__products main-cataloge__body view-list">
                        <?php foreach ($keys as $key) {
                            if (!isset($metaByKey[$key])) {
                                continue;
                            }

                            $meta = $metaByKey[$key];
                            $item = $meta['item'];

                            $card = $cardsByKey[$key] ?? [];
                            $productId = (int)($item['PRODUCT_ID'] ?? 0);
                            if ($productId <= 0) {
                                continue;
                            }

                            $detailUrl = (string)($card['DETAIL_PAGE_URL'] ?? $item['DETAIL_PAGE_URL'] ?? '#');
                            $detailUrl = $buildDetailUrl($detailUrl, (string)$meta['context_path']);

                            $card['ID'] = (int)($card['ID'] ?? $productId);
                            $card['NAME'] = (string)($card['NAME'] ?? $item['NAME'] ?? '');
                            $card['DETAIL_PAGE_URL'] = $detailUrl;
                            $card['CONTEXT_LABEL'] = (string)$meta['context_label'];
                            $card['CONTEXT_SECTION_ID'] = (int)$meta['context_section_id'];
                            $card['CONTEXT_SECTION_PATH'] = (string)$meta['context_path'];
                            $basketQuantity = (float)($item['QUANTITY'] ?? 1);
                            $priceValue = null;
                            $priceCurrency = null;
                            if (isset($item['PRICE']) && is_numeric($item['PRICE'])) {
                                $priceValue = (float)$item['PRICE'];
                                $priceCurrency = is_string($item['CURRENCY'] ?? null) ? (string)$item['CURRENCY'] : 'RUB';
                            } else {
                                $priceData = null;
                                if (class_exists(FavoritesManager::class)) {
                                    try {
                                        $priceData = FavoritesManager::getProductPrice($productId, $meta['options'] ?? []);
                                    } catch (\Throwable $exception) {
                                        $priceData = null;
                                    }
                                }

                                if (is_array($priceData)) {
                                    if (isset($priceData['DISCOUNT_PRICE']) && is_numeric($priceData['DISCOUNT_PRICE'])) {
                                        $priceValue = (float)$priceData['DISCOUNT_PRICE'];
                                    } elseif (isset($priceData['BASE_PRICE']) && is_numeric($priceData['BASE_PRICE'])) {
                                        $priceValue = (float)$priceData['BASE_PRICE'];
                                    }
                                    if (isset($priceData['CURRENCY']) && is_string($priceData['CURRENCY'])) {
                                        $priceCurrency = $priceData['CURRENCY'];
                                    }
                                }
                            }

                            if ($priceValue === null) {
                                $priceValue = 0.0;
                            }
                            if ($priceCurrency === null || $priceCurrency === '') {
                                $priceCurrency = 'RUB';
                            }
                            $lineTotalValue = $priceValue * $basketQuantity;
                            $lineTotalFormatted = $formatCurrency($lineTotalValue, $priceCurrency);
                            $basketTotalValue += $lineTotalValue;
                            if ($basketTotalCurrency === null) {
                                $basketTotalCurrency = $priceCurrency;
                            }

                            $card['PRICE_HTML'] = $lineTotalFormatted;
                            $card['OPTIONS_ATTR'] = (string)$meta['options_attr'];
                            $card['SELECTED'] = $meta['selected'];
                            $card['EXPAND_FEATURES'] = true;
                            $card['FAVORITES_VIEW'] = true;
                            $card['BASKET_VIEW'] = true;
                            $card['BASKET_ID'] = (int)($item['ID'] ?? 0);
                            $card['BASKET_QUANTITY'] = $basketQuantity;

                            ?>
                            <div class="basket__item basket__item--card" data-basket-item-id="<?= (int)($item['ID'] ?? 0) ?>">
                                <?php
                                $cardData = $card;
                                $card = $cardData;
                                include $partialPath;
                                unset($card);
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="basket__summary">
            <div class="basket__summary-row">
                <span>Итого:</span>
            <strong><?= $formatCurrency($basketTotalValue, $basketTotalCurrency ?? 'RUB') ?></strong>
            </div>
                        <a class="basket__summary-action" href="<?= htmlspecialcharsbx($arParams['PATH_TO_ORDER'] ?? '/personal/order/') ?>">
                            Оформить заказ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
