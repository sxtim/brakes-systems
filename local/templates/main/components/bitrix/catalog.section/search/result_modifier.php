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
file_put_contents(
    $logFilePath,
    json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
    FILE_APPEND | LOCK_EX
);

$arResult['SEARCH_CONTEXT'] = $context;
