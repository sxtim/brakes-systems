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

$items = $arResult['ITEMS'] ?? [];

$arResult['SEARCH_CONTEXT'] = $context;
$GLOBALS['CATALOG_SEARCH_CONTEXT'] = $context;

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
