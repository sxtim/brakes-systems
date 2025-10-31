<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$filterName = (string)($arParams['CATALOG_FILTER_NAME'] ?? 'catalogSearchFilter');
if ($filterName === '') {
    $filterName = 'catalogSearchFilter';
}

$originalQuery = trim((string)($_REQUEST['q'] ?? ''));
$effectiveQuery = trim((string)($arResult['REQUEST']['QUERY'] ?? $originalQuery));
$displayQuery = ($originalQuery !== '') ? $originalQuery : $effectiveQuery;
$searchItems = $arResult['SEARCH'] ?? [];

$ids = [];
$sample = [];

foreach ($searchItems as $item) {
    if (($item['MODULE_ID'] ?? '') !== 'iblock') {
        continue;
    }

    $itemIdRaw = (string)($item['ITEM_ID'] ?? '');
    if ($itemIdRaw === '') {
        continue;
    }

    if (ctype_digit($itemIdRaw)) {
        $itemId = (int)$itemIdRaw;
    } else {
        if (preg_match('/(\d+)(?!.*\d)/', $itemIdRaw, $matches)) {
            $itemId = (int)$matches[1];
        } else {
            continue;
        }
    }

    if (!in_array($itemId, $ids, true)) {
        $ids[] = $itemId;
    }

    if (count($sample) < 10) {
        $sample[] = [
            'ITEM_ID' => $itemId,
            'TITLE' => $item['TITLE'] ?? '',
            'URL' => $item['URL'] ?? '',
        ];
    }
}

if ($ids === []) {
    $ids = [-1];
}

global ${$filterName}, $catalogSearchContext;

${$filterName} = [
    'ID' => $ids,
];

$catalogSearchContext = [
    'query' => $displayQuery,
    'effective_query' => $effectiveQuery,
    'ids' => $ids,
    'sample' => $sample,
];

$logEntry = [
    'timestamp' => date('c'),
    'query' => $displayQuery,
    'effective_query' => $effectiveQuery,
    'ids' => $ids,
    'sample' => $sample,
    'uri' => $_SERVER['REQUEST_URI'] ?? null,
    'params' => [
        'FILTER_NAME' => $filterName,
        'COMPONENT' => 'bitrix:search.page',
    ],
];

$logFilePath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
file_put_contents(
    $logFilePath,
    json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

$arResult['SEARCH_CONTEXT'] = $catalogSearchContext;
