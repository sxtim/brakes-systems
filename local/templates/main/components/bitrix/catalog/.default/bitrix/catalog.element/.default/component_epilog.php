<?php

use App\Brakes\Helper\Storage;
use App\Brakes\Helper\FavoritesManager;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

Storage::set('RECOMMENDED', $arResult['RECOMMENDED']);
Storage::set('ITEM_ID', $arResult['ID']);

$session = Application::getInstance()->getSession();
$data = $session->get('CATALOG_ITEM_VIEWED');

if (!is_array($data)) {
    $data = [];
}

$context = [];
$contextSectionId = 0;
$contextSectionPath = '';
if (!empty($arResult['SECTION']['PATH']) && is_array($arResult['SECTION']['PATH'])) {
    $path = array_values($arResult['SECTION']['PATH']);
    $pathCount = count($path);
    if ($pathCount > 0) {
        $contextSectionId = (int)($path[$pathCount - 1]['ID'] ?? 0);
        $contextCodes = array_map(static function ($item) {
            return isset($item['CODE']) ? (string)$item['CODE'] : '';
        }, $path);
        $contextCodes = array_values(array_filter($contextCodes, static fn($code) => $code !== ''));
        if ($contextCodes !== []) {
            $contextSectionPath = implode('/', $contextCodes);
        }
    }
}

$favkeyParam = trim((string)($_GET['favkey'] ?? ''));
if ($favkeyParam !== '' && preg_match('/^(\\d+):(s|p|n)(.*)$/', $favkeyParam, $matches)) {
    $favType = $matches[2];
    $favTail = $matches[3] ?? '';
    if ($favType === 's') {
        $favSectionId = (int)$favTail;
        if ($favSectionId > 0) {
            $contextSectionId = $favSectionId;
        }
    } elseif ($favType === 'p') {
        $favSectionPath = trim((string)$favTail, " \t\n\r\0\x0B/");
        if ($favSectionPath !== '') {
            $contextSectionPath = $favSectionPath;
        }
    }
}

if ($contextSectionPath === '' && $contextSectionId > 0 && !empty($arParams['IBLOCK_ID']) && Loader::includeModule('iblock')) {
    $nav = \CIBlockSection::GetNavChain((int)$arParams['IBLOCK_ID'], $contextSectionId, ['CODE']);
    $codes = [];
    while ($row = $nav->Fetch()) {
        if (!empty($row['CODE'])) {
            $codes[] = $row['CODE'];
        }
    }
    if ($codes !== []) {
        $contextSectionPath = implode('/', $codes);
    }
}

if ($contextSectionId > 0) {
    $context['section_id'] = $contextSectionId;
}
if ($contextSectionPath !== '') {
    $context['section_path'] = $contextSectionPath;
}

$normalized = [];
foreach ($data as $key => $value) {
    $id = 0;
    $itemContext = [];
    $favkey = '';

    if (is_array($value)) {
        $id = isset($value['ID']) ? (int)$value['ID'] : (isset($value['id']) ? (int)$value['id'] : 0);
        if (isset($value['CONTEXT']) && is_array($value['CONTEXT'])) {
            $itemContext = $value['CONTEXT'];
        } elseif (isset($value['context']) && is_array($value['context'])) {
            $itemContext = $value['context'];
        }
        if (isset($value['FAVORITE_KEY'])) {
            $favkey = (string)$value['FAVORITE_KEY'];
        } elseif (isset($value['favkey'])) {
            $favkey = (string)$value['favkey'];
        } elseif (isset($value['key'])) {
            $favkey = (string)$value['key'];
        }
    } else {
        $id = (int)$value;
        if ($id <= 0 && is_scalar($key)) {
            $id = (int)$key;
        }
    }

    if ($id <= 0) {
        continue;
    }

    if ($favkey === '' && class_exists(FavoritesManager::class)) {
        $favkey = FavoritesManager::buildFavoriteKey($id, is_array($itemContext) ? $itemContext : []);
    }
    if ($favkey === '') {
        $favkey = (string)$id;
    }

    $normalized[$favkey] = [
        'ID' => $id,
        'CONTEXT' => $itemContext,
        'FAVORITE_KEY' => $favkey,
    ];
}

$favoriteKey = class_exists(FavoritesManager::class)
    ? FavoritesManager::buildFavoriteKey((int)$arResult['ID'], $context)
    : (string)$arResult['ID'];

Storage::set('ITEM_VIEWED_KEY', $favoriteKey);

if (isset($normalized[$favoriteKey])) {
    unset($normalized[$favoriteKey]);
}

$normalized[$favoriteKey] = [
    'ID' => (int)$arResult['ID'],
    'CONTEXT' => $context,
    'FAVORITE_KEY' => $favoriteKey,
];

$data = $normalized;

$session->set('CATALOG_ITEM_VIEWED', $data);
