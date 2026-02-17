<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

require $_SERVER['DOCUMENT_ROOT'] . '/local/templates/main/components/bitrix/catalog/.default/bitrix/catalog.section/.default/result_modifier.php';

$context = $GLOBALS['catalogSearchContext'] ?? [
    'query' => (string)($_REQUEST['q'] ?? ''),
    'ids' => [],
    'sample' => [],
];

$arResult['SEARCH_CONTEXT'] = $context;
$GLOBALS['CATALOG_SEARCH_CONTEXT'] = $context;

$items = $arResult['ITEMS'] ?? [];

// Filter expanded contexts by mark token in search query (only for search page).
$query = trim((string)($context['query'] ?? ''));
if ($query !== '' && !empty($items) && is_array($items)) {
    $normalize = static function (string $value): string {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value);
        } else {
            $value = strtolower($value);
        }
        return $value;
    };

    $normalizedQuery = $normalize($query);
    $queryTokens = preg_split('/[^0-9a-zа-я]+/iu', $normalizedQuery, -1, PREG_SPLIT_NO_EMPTY);
    $queryTokens = is_array($queryTokens) ? array_values(array_unique($queryTokens)) : [];

    // Handle hyphen/underscore-separated model codes like "uni-k" -> "unik".
    $collapsedQueryToken = preg_replace('/[^0-9a-zа-я]+/iu', '', $normalizedQuery);
    if (is_string($collapsedQueryToken) && $collapsedQueryToken !== '' && strlen($collapsedQueryToken) >= 3) {
        if (!in_array($collapsedQueryToken, $queryTokens, true)) {
            $queryTokens[] = $collapsedQueryToken;
        }
    }

    // Avoid matching by 1-letter tokens (e.g. "k" from "uni-k") which often cause noise.
    $queryTokensForMatching = array_values(array_filter($queryTokens, static function ($token): bool {
        return is_string($token) && strlen($token) >= 2;
    }));

    $normalizeCategory = static function (string $value) use ($normalize): string {
        $value = $normalize($value);
        if ($value === '') {
            return '';
        }
        $value = str_replace(['ё'], ['е'], $value);
        if (str_contains($value, 'колод')) {
            return 'tormoznye_kolodki';
        }
        if (str_contains($value, 'диск')) {
            return 'tormoznye_diski';
        }
        if (str_contains($value, 'систем')) {
            return 'tormoznye_sistemy';
        }
        return '';
    };

    $queryCategoryCode = $normalizeCategory($normalizedQuery);

    $getContextParts = static function (array $item): array {
        $path = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
        if ($path === '') {
            $detailUrl = (string)($item['DETAIL_PAGE_URL'] ?? '');
            if ($detailUrl !== '') {
                $path = (string)parse_url($detailUrl, PHP_URL_PATH);
            }
        }
        $path = trim($path, '/');
        if ($path === '') {
            return [];
        }

        $parts = array_values(array_filter(explode('/', $path), 'strlen'));
        if (isset($parts[0]) && $parts[0] === 'catalog') {
            $parts = array_slice($parts, 1);
        }
        return $parts;
    };

    $isStrongBodyToken = static function (string $token): bool {
        // Examples: f15, wk2, u70, v3, g20
        return preg_match('/\\d/', $token) && preg_match('/[a-zа-я]/iu', $token);
    };

    $getContextTokens = static function (array $item) use ($normalize, $getContextParts, $isStrongBodyToken): array {
        $parts = $getContextParts($item);
        $count = count($parts);
        if ($count < 3) {
            return ['mark' => '', 'model' => '', 'body_tokens' => []];
        }

        $markSlug = (string)($parts[$count - 3] ?? '');
        $modelSlug = (string)($parts[$count - 2] ?? '');
        $bodySlug = (string)($parts[$count - 1] ?? '');
        $categorySlug = ($count >= 4) ? (string)($parts[$count - 4] ?? '') : '';

        if ($categorySlug !== '' && strncmp($markSlug, $categorySlug . '_', strlen($categorySlug) + 1) === 0) {
            $markSlug = substr($markSlug, strlen($categorySlug) + 1);
        }

        $prefix = '';
        if ($categorySlug !== '') {
            $prefix = $categorySlug . '_';
        }
        if ($markSlug !== '') {
            $prefix .= $markSlug . '_';
        }
        if ($prefix !== '' && strncmp($modelSlug, $prefix, strlen($prefix)) === 0) {
            $modelSlug = substr($modelSlug, strlen($prefix));
        }

        $markSlugForPrefix = $markSlug;
        $modelSlugForPrefix = $modelSlug;

        $markSlug = preg_replace('/[^0-9a-zа-я]+/iu', '', $markSlug);
        $modelSlug = preg_replace('/[^0-9a-zа-я]+/iu', '', $modelSlug);

        $bodyTokens = [];
        if ($bodySlug !== '') {
            $bodyPrefix = '';
            if ($categorySlug !== '') {
                $bodyPrefix = $categorySlug . '_';
            }
            if ($markSlugForPrefix !== '') {
                $bodyPrefix .= $markSlugForPrefix . '_';
            }
            if ($modelSlugForPrefix !== '') {
                $bodyPrefix .= $modelSlugForPrefix . '_';
            }
            if ($bodyPrefix !== '' && strncmp($bodySlug, $bodyPrefix, strlen($bodyPrefix)) === 0) {
                $bodySlug = substr($bodySlug, strlen($bodyPrefix));
            }

            $bodySlug = $normalize($bodySlug);
            $parts = preg_split('/[^0-9a-zа-я]+/iu', $bodySlug, -1, PREG_SPLIT_NO_EMPTY);
            if (is_array($parts)) {
                foreach ($parts as $part) {
                    $part = trim((string)$part);
                    if ($part === '' || strlen($part) < 2) {
                        continue;
                    }
                    if (!$isStrongBodyToken($part)) {
                        continue;
                    }
                    $bodyTokens[$part] = true;
                }
            }
        }

        return [
            'mark' => $normalize($markSlug),
            'model' => $normalize($modelSlug),
            'body_tokens' => array_keys($bodyTokens),
        ];
    };

    $getItemCategoryCode = static function (array $item) use ($normalize, $normalizeCategory): string {
        $propertyValue = '';
        if (!empty($item['PROPERTIES']['PRODUCT_CATEGORY']['VALUE'])) {
            $propertyValue = (string)$item['PROPERTIES']['PRODUCT_CATEGORY']['VALUE'];
        } elseif (!empty($item['DISPLAY_PROPERTIES']['PRODUCT_CATEGORY']['VALUE'])) {
            $propertyValue = (string)$item['DISPLAY_PROPERTIES']['PRODUCT_CATEGORY']['VALUE'];
        }

        if ($propertyValue !== '') {
            return $normalizeCategory($propertyValue);
        }

        $contextPath = (string)($item['CONTEXT_SECTION_PATH'] ?? '');
        if ($contextPath === '') {
            $detailUrl = (string)($item['DETAIL_PAGE_URL'] ?? '');
            if ($detailUrl !== '') {
                $contextPath = (string)parse_url($detailUrl, PHP_URL_PATH);
            }
        }

        $contextPath = trim($contextPath, '/');
        if ($contextPath !== '') {
            $parts = array_values(array_filter(explode('/', $contextPath), 'strlen'));
            if ($parts !== []) {
                $first = (string)($parts[0] ?? '');
                if ($first === 'catalog' && isset($parts[1])) {
                    $first = (string)$parts[1];
                }
                if ($first !== '') {
                    return $normalize($first);
                }
            }
        }

        return '';
    };

    $allMarkTokens = [];
    $allModelTokens = [];
    $allBodyTokens = [];
    $itemMarkTokens = [];
    $itemModelTokens = [];
    $itemBodyTokens = [];
    $itemCategoryCodes = [];
    foreach ($items as $index => $item) {
        $tokens = $getContextTokens($item);
        $markToken = $tokens['mark'] ?? '';
        $modelToken = $tokens['model'] ?? '';
        $bodyTokens = $tokens['body_tokens'] ?? [];

        if ($markToken !== '') {
            $allMarkTokens[$markToken] = true;
            $itemMarkTokens[$index] = $markToken;
        }
        if ($modelToken !== '') {
            $allModelTokens[$modelToken] = true;
            $itemModelTokens[$index] = $modelToken;
        }

        if (is_array($bodyTokens) && $bodyTokens !== []) {
            $itemBodyTokens[$index] = $bodyTokens;
            foreach ($bodyTokens as $bodyToken) {
                if (is_string($bodyToken) && $bodyToken !== '') {
                    $allBodyTokens[$bodyToken] = true;
                }
            }
        }

        if ($queryCategoryCode !== '') {
            $itemCategoryCodes[$index] = $getItemCategoryCode($item);
        }
    }

    $matchedMarks = [];
    $matchedModels = [];
    $matchedBodies = [];
    foreach ($queryTokensForMatching as $token) {
        if (isset($allMarkTokens[$token])) {
            $matchedMarks[$token] = true;
        }
        if (isset($allModelTokens[$token])) {
            $matchedModels[$token] = true;
        }
        if (isset($allBodyTokens[$token])) {
            $matchedBodies[$token] = true;
        }
    }

    $isLikelyPartNumberToken = static function (string $token) use ($normalize): bool {
        $token = $normalize($token);
        if ($token === '' || strlen($token) < 4) {
            return false;
        }
        if (!preg_match('/\\d/', $token)) {
            return false;
        }
        if (preg_match('/[a-zа-я]/iu', $token)) {
            return true;
        }

        // Digits-only: allow longer tokens (avoid collapsing on short numbers like "15").
        return strlen($token) >= 6;
    };

    $queryLooksLikePartNumber = false;
    foreach ($queryTokensForMatching as $token) {
        if ($isLikelyPartNumberToken($token)) {
            $queryLooksLikePartNumber = true;
            break;
        }
    }

    // "Better noisy than missing": apply strictest filter first, but fall back if it would empty the list.
    $originalItems = $items;

    $filterByMarkModel = static function (array $source) use ($itemMarkTokens, $itemModelTokens, $matchedMarks, $matchedModels): array {
        $filtered = [];
        foreach ($source as $index => $item) {
            $markToken = $itemMarkTokens[$index] ?? '';
            $modelToken = $itemModelTokens[$index] ?? '';
            if ($markToken !== '' && $modelToken !== '' && isset($matchedMarks[$markToken]) && isset($matchedModels[$modelToken])) {
                $filtered[$index] = $item;
            }
        }
        return $filtered;
    };

    $filterByMark = static function (array $source) use ($itemMarkTokens, $matchedMarks): array {
        $filtered = [];
        foreach ($source as $index => $item) {
            $markToken = $itemMarkTokens[$index] ?? '';
            if ($markToken !== '' && isset($matchedMarks[$markToken])) {
                $filtered[$index] = $item;
            }
        }
        return $filtered;
    };

    $filterByModel = static function (array $source) use ($itemModelTokens, $matchedModels): array {
        $filtered = [];
        foreach ($source as $index => $item) {
            $modelToken = $itemModelTokens[$index] ?? '';
            if ($modelToken !== '' && isset($matchedModels[$modelToken])) {
                $filtered[$index] = $item;
            }
        }
        return $filtered;
    };

    if ($matchedMarks !== [] && $matchedModels !== []) {
        $filtered = $filterByMarkModel($originalItems);
        if ($filtered !== []) {
            $items = $filtered;
        } else {
            $filtered = $filterByMark($originalItems);
            if ($filtered !== []) {
                $items = $filtered;
            } else {
                $filtered = $filterByModel($originalItems);
                $items = $filtered !== [] ? $filtered : $originalItems;
            }
        }
    } elseif ($matchedMarks !== []) {
        $filtered = $filterByMark($originalItems);
        if ($filtered !== []) {
            $items = $filtered;
        }
    } elseif ($matchedModels !== []) {
        $filtered = $filterByModel($originalItems);
        if ($filtered !== []) {
            $items = $filtered;
        }
    }

    $arResult['ITEMS'] = $items;

    if ($matchedBodies !== []) {
        $queryHasStrongToken = false;
        foreach ($queryTokensForMatching as $token) {
            if ($isStrongBodyToken($token)) {
                $queryHasStrongToken = true;
                break;
            }
        }

        // Apply body tokens as:
        // - a refinement when mark/model matched;
        // - a standalone filter only for strong alnum codes like "f15", "wk2", "u70".
        if (($matchedMarks !== [] || $matchedModels !== []) || $queryHasStrongToken) {
            $filteredItems = [];
            foreach ($items as $index => $item) {
                $bodyTokens = $itemBodyTokens[$index] ?? [];
                if (!is_array($bodyTokens) || $bodyTokens === []) {
                    continue;
                }
                foreach ($bodyTokens as $bodyToken) {
                    if (isset($matchedBodies[$bodyToken])) {
                        $filteredItems[$index] = $item;
                        break;
                    }
                }
            }

            if ($filteredItems !== []) {
                $arResult['ITEMS'] = $filteredItems;
                $items = $filteredItems;
            }
        }
    }

    if ($queryCategoryCode !== '' && $items !== []) {
        $itemsBeforeCategory = $items;
        $filteredItems = [];
        foreach ($items as $index => $item) {
            $itemCategoryCode = $itemCategoryCodes[$index] ?? $getItemCategoryCode($item);
            if ($itemCategoryCode !== '' && $itemCategoryCode === $queryCategoryCode) {
                $filteredItems[$index] = $item;
            }
        }
        if ($filteredItems !== []) {
            $arResult['ITEMS'] = $filteredItems;
            $items = $filteredItems;
        } else {
            $arResult['ITEMS'] = $itemsBeforeCategory;
            $items = $itemsBeforeCategory;
        }
    }

    // Normalize keys for templates/pagers.
    if ($items !== []) {
        $arResult['ITEMS'] = array_values($items);
        $items = $arResult['ITEMS'];
    }
}

// Debug log of search context (kept for production diagnostics).
$logPayload = [
    'timestamp' => date('c'),
    'query' => [
        'value' => (string)($context['query'] ?? ''),
        'raw' => $_REQUEST['q'] ?? null,
        'length' => mb_strlen((string)($context['query'] ?? '')),
        'is_empty' => ((string)($context['query'] ?? '') === ''),
    ],
    'request_uri' => $_SERVER['REQUEST_URI'] ?? null,
    'items' => [
        'count' => is_array($items) ? count($items) : 0,
        'ids' => array_map(static fn($item) => (int)($item['ID'] ?? 0), is_array($items) ? $items : []),
    ],
    'context' => [
        'ids' => $context['ids'] ?? [],
        'sample' => $context['sample'] ?? [],
    ],
    'params' => [
        'IBLOCK_ID' => $arParams['IBLOCK_ID'] ?? null,
        'IBLOCK_TYPE' => $arParams['IBLOCK_TYPE'] ?? null,
        'FILTER_NAME' => $arParams['FILTER_NAME'] ?? null,
    ],
];

$logFilePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
@file_put_contents(
    $logFilePath,
    json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

// Try to point search results to a "best matching" section path for multi-applicable products.
// Otherwise Bitrix uses the element's main section which can look wrong for queries like "audi".
$query = trim((string)($context['query'] ?? ''));
$iblockId = isset($arParams['IBLOCK_ID']) ? (int)$arParams['IBLOCK_ID'] : 0;
if ($query !== '' && $iblockId > 0 && !empty($arResult['ITEMS']) && is_array($arResult['ITEMS']) && class_exists('CIBlockElement') && class_exists('CIBlockSection')) {
    $normalize = static function ($value): string {
        if (!is_string($value)) {
            $value = (string)$value;
        }
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (function_exists('mb_strtolower')) {
            return mb_strtolower($value);
        }
        return strtolower($value);
    };

    $tokens = preg_split('/[^0-9a-zа-я]+/iu', $query, -1, PREG_SPLIT_NO_EMPTY);
    $tokens = is_array($tokens) ? array_values(array_unique(array_filter(array_map($normalize, $tokens), static function (string $token): bool {
        return $token !== '';
    }))) : [];

    if ($tokens !== []) {
        foreach ($arResult['ITEMS'] as &$item) {
            if (!empty($item['CONTEXT_SECTION_ID']) || !empty($item['CONTEXT_SECTION_PATH'])) {
                continue;
            }

            $elementId = isset($item['ID']) ? (int)$item['ID'] : 0;
            if ($elementId <= 0) {
                continue;
            }

            $elementCode = (string)($item['CODE'] ?? '');
            if ($elementCode === '' && !empty($item['DETAIL_PAGE_URL'])) {
                $path = (string)parse_url((string)$item['DETAIL_PAGE_URL'], PHP_URL_PATH);
                $path = trim($path, '/');
                if ($path !== '') {
                    $parts = explode('/', $path);
                    $elementCode = (string)end($parts);
                }
            }
            if ($elementCode === '') {
                continue;
            }

            $groups = [];
            $groupsRes = CIBlockElement::GetElementGroups($elementId, true, ['ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE', 'DEPTH_LEVEL']);
            while ($group = $groupsRes->Fetch()) {
                $groups[] = $group;
            }
            if ($groups === []) {
                continue;
            }

            $maxDepth = 0;
            foreach ($groups as $group) {
                $depth = isset($group['DEPTH_LEVEL']) ? (int)$group['DEPTH_LEVEL'] : 0;
                if ($depth > $maxDepth) {
                    $maxDepth = $depth;
                }
            }
            if ($maxDepth <= 0) {
                continue;
            }

            $bestScore = 0;
            $bestCodes = [];
            foreach ($groups as $group) {
                $groupDepth = isset($group['DEPTH_LEVEL']) ? (int)$group['DEPTH_LEVEL'] : 0;
                if ($groupDepth !== $maxDepth) {
                    continue;
                }

                $sectionId = isset($group['ID']) ? (int)$group['ID'] : 0;
                if ($sectionId <= 0) {
                    continue;
                }

                $chain = [];
                $chainRes = CIBlockSection::GetNavChain($iblockId, $sectionId, ['ID', 'NAME', 'CODE', 'DEPTH_LEVEL']);
                while ($sec = $chainRes->Fetch()) {
                    $chain[] = $sec;
                }
                if ($chain === []) {
                    continue;
                }

                // Prefer matches in auto context: last 3 levels (марка/модель/кузов), skip category noise.
                $contextChain = count($chain) > 3 ? array_slice($chain, -3) : $chain;

                $score = 0;
                foreach ($tokens as $token) {
                    foreach ($contextChain as $sec) {
                        $secName = $normalize($sec['NAME'] ?? '');
                        $secCode = $normalize($sec['CODE'] ?? '');
                        if ($token === '') {
                            continue;
                        }

                        if (($secName !== '' && $token === $secName) || ($secCode !== '' && $token === $secCode)) {
                            $score += 10;
                            continue;
                        }

                        if (($secName !== '' && strpos($secName, $token) !== false) || ($secCode !== '' && strpos($secCode, $token) !== false)) {
                            $score += 5;
                        }
                    }
                }

                if ($score <= $bestScore) {
                    continue;
                }

                $codes = [];
                foreach ($chain as $sec) {
                    $code = isset($sec['CODE']) ? (string)$sec['CODE'] : '';
                    if ($code !== '') {
                        $codes[] = $code;
                    }
                }
                if ($codes === []) {
                    continue;
                }

                $bestScore = $score;
                $bestCodes = $codes;
            }

            if ($bestScore > 0 && $bestCodes !== []) {
                $sectionCodePath = implode('/', $bestCodes);
                $newUrl = '/catalog/' . $sectionCodePath . '/' . $elementCode . '/';
                $item['DETAIL_PAGE_URL'] = $newUrl;
                $item['~DETAIL_PAGE_URL'] = $newUrl;
            }
        }
        unset($item);
    }
}
