<?php

use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

class HomeMainComponent extends CBitrixComponent
{
    private const PRODUCTS_LIMIT = 2;

    public function onPrepareComponentParams($params): array
    {
        $params['SETTINGS_IBLOCK_CODE'] = trim((string)($params['SETTINGS_IBLOCK_CODE'] ?? 'home_main_settings'));
        $params['SETTINGS_ELEMENT_CODE'] = trim((string)($params['SETTINGS_ELEMENT_CODE'] ?? 'main'));
        $params['PRODUCTS_IBLOCK_CODE'] = trim((string)($params['PRODUCTS_IBLOCK_CODE'] ?? 'home_main_products'));
        $params['CACHE_TIME'] = isset($params['CACHE_TIME']) ? (int)$params['CACHE_TIME'] : 3600;

        if ($params['CACHE_TIME'] <= 0) {
            $params['CACHE_TIME'] = 3600;
        }

        return $params;
    }

    public function executeComponent(): void
    {
        if (!Loader::includeModule('iblock')) {
            return;
        }

        if ($this->startResultCache(false)) {
            $this->arResult = [
                'SETTINGS' => $this->loadSettings(),
                'PRODUCTS' => $this->loadProducts(),
            ];

            $this->includeComponentTemplate();
        }
    }

    private function loadSettings(): array
    {
        $iblockId = $this->resolveIblockIdByCode($this->arParams['SETTINGS_IBLOCK_CODE']);
        if ($iblockId <= 0) {
            return [];
        }

        $filter = [
            'IBLOCK_ID' => $iblockId,
            'ACTIVE' => 'Y',
        ];

        $elementCode = (string)$this->arParams['SETTINGS_ELEMENT_CODE'];
        if ($elementCode !== '') {
            $filter['=CODE'] = $elementCode;
        }

        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            $filter,
            false,
            ['nTopCount' => 1],
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE']
        );

        $item = $res ? $res->GetNextElement() : false;
        if (!$item) {
            return [];
        }

        $fields = $item->GetFields();
        $props = $item->GetProperties();
        $videoDesktopProp = $this->getPropertyByCodeInsensitive($props, 'VIDEO_DESKTOP');
        $videoMobileProp = $this->getPropertyByCodeInsensitive($props, 'VIDEO_MOBILE');
        $posterDesktopProp = $this->getPropertyByCodeInsensitive($props, 'POSTER_DESKTOP');
        $posterMobileProp = $this->getPropertyByCodeInsensitive($props, 'POSTER_MOBILE');
        $title1Prop = $this->getPropertyByCodeInsensitive($props, 'TITLE_1');
        $title2Prop = $this->getPropertyByCodeInsensitive($props, 'TITLE_2');
        $subtitleProp = $this->getPropertyByCodeInsensitive($props, 'HERO_SUBTITLE');
        $logosProp = $this->getPropertyByCodeInsensitive($props, 'LOGO_SLIDER');
        $projectsProp = $this->getPropertyByCodeInsensitive($props, 'PROJECTS');
        $aboutTitleProp = $this->getPropertyByCodeInsensitive($props, 'ABOUT_TITLE');
        $aboutTextProp = $this->getPropertyByCodeInsensitive($props, 'ABOUT_TEXT');
        $aboutPhotoProp = $this->getPropertyByCodeInsensitive($props, 'ABOUT_PHOTO');
        $certificatesProp = $this->getPropertyByCodeInsensitive($props, 'CERTIFICATES');

        $videoDesktopSrc = $this->resolveSingleFilePath($videoDesktopProp['VALUE'] ?? null);
        $videoMobileSrc = $this->resolveSingleFilePath($videoMobileProp['VALUE'] ?? null);
        $posterDesktopId = $this->resolveSingleFileId($posterDesktopProp['VALUE'] ?? null);
        $posterMobileId = $this->resolveSingleFileId($posterMobileProp['VALUE'] ?? null);

        return [
            'ID' => (int)($fields['ID'] ?? 0),
            'HERO_TITLE_1' => trim((string)($title1Prop['VALUE'] ?? '')),
            'HERO_TITLE_2' => trim((string)($title2Prop['VALUE'] ?? '')),
            'HERO_SUBTITLE' => trim((string)($subtitleProp['VALUE'] ?? '')),
            'VIDEO_DESKTOP' => $videoDesktopSrc,
            'VIDEO_MOBILE' => $videoMobileSrc,
            'VIDEO_DESKTOP_MIME' => $this->resolveVideoMime($videoDesktopSrc),
            'VIDEO_MOBILE_MIME' => $this->resolveVideoMime($videoMobileSrc),
            'POSTER_DESKTOP_ID' => $posterDesktopId,
            'POSTER_DESKTOP' => $this->resolveSingleFilePath($posterDesktopId),
            'POSTER_MOBILE_ID' => $posterMobileId,
            'POSTER_MOBILE' => $this->resolveSingleFilePath($posterMobileId),
            'LOGOS' => $this->resolveMultipleFilePaths($logosProp['VALUE'] ?? []),
            'PROJECTS' => $this->resolveMultipleFilePaths($projectsProp['VALUE'] ?? []),
            'ABOUT_TITLE' => trim((string)($aboutTitleProp['VALUE'] ?? '')),
            'ABOUT_TEXT' => $this->extractHtmlProperty($aboutTextProp),
            'ABOUT_PHOTO_ID' => $this->resolveSingleFileId($aboutPhotoProp['VALUE'] ?? null),
            'ABOUT_PHOTO' => $this->resolveSingleFilePath($aboutPhotoProp['VALUE'] ?? null),
            'CERTIFICATES' => $this->resolveMultipleFilePaths($certificatesProp['VALUE'] ?? []),
        ];
    }

    private function loadProducts(): array
    {
        $iblockId = $this->resolveIblockIdByCode($this->arParams['PRODUCTS_IBLOCK_CODE']);
        if ($iblockId <= 0) {
            return [];
        }

        $res = CIBlockElement::GetList(
            ['SORT' => 'ASC', 'ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'ACTIVE' => 'Y',
            ],
            false,
            false,
            ['ID', 'IBLOCK_ID', 'NAME', 'CODE', 'SORT']
        );

        $items = [];
        $usedCodes = [];
        while ($element = $res->GetNextElement()) {
            $fields = $element->GetFields();
            $code = trim((string)($fields['CODE'] ?? ''));
            $uniqueKey = $code !== '' ? $code : 'ID_' . (int)($fields['ID'] ?? 0);

            if (isset($usedCodes[$uniqueKey])) {
                continue;
            }

            $props = $element->GetProperties();
            $subtitleProp = $this->getPropertyByCodeInsensitive($props, 'SUBTITLE');
            $textHtmlProp = $this->getPropertyByCodeInsensitive($props, 'TEXT_HTML');
            $featuresHtmlProp = $this->getPropertyByCodeInsensitive($props, 'FEATURES_HTML');
            $photoProp = $this->getPropertyByCodeInsensitive($props, 'PHOTO');
            $photoId = $this->resolveSingleFileId($photoProp['VALUE'] ?? null);

            $items[] = [
                'ID' => (int)($fields['ID'] ?? 0),
                'TITLE' => trim((string)($fields['NAME'] ?? '')),
                'SUBTITLE' => trim((string)($subtitleProp['VALUE'] ?? '')),
                'TEXT_HTML' => $this->extractHtmlProperty($textHtmlProp),
                'FEATURES_HTML' => $this->extractHtmlProperty($featuresHtmlProp),
                'PHOTO_ID' => $photoId,
                'PHOTO' => $this->resolveSingleFilePath($photoId),
            ];
            $usedCodes[$uniqueKey] = true;

            if (count($items) >= self::PRODUCTS_LIMIT) {
                break;
            }
        }

        return $items;
    }

    private function extractHtmlProperty(array $property): string
    {
        // Bitrix often returns escaped HTML in VALUE and raw in ~VALUE for HTML user-type properties.
        // Prefer ~VALUE when available to avoid rendering entities as plain text.
        $value = $property['~VALUE'] ?? ($property['VALUE'] ?? '');
        $text = '';

        if (is_array($value)) {
            $text = (string)($value['TEXT'] ?? '');
        } else {
            $text = (string)$value;
        }

        if ($text === '' && isset($property['VALUE'])) {
            $fallbackValue = $property['VALUE'];
            if (is_array($fallbackValue)) {
                $text = (string)($fallbackValue['TEXT'] ?? '');
            } else {
                $text = (string)$fallbackValue;
            }
        }

        return $this->decodeHtmlEntitiesRecursively($text);
    }

    private function decodeHtmlEntitiesRecursively(string $html): string
    {
        $decoded = $html;
        for ($i = 0; $i < 2; $i++) {
            if (
                strpos($decoded, '&lt;') === false
                && strpos($decoded, '&gt;') === false
                && strpos($decoded, '&amp;lt;') === false
                && strpos($decoded, '&amp;gt;') === false
                && strpos($decoded, '&#0') === false
                && strpos($decoded, '&#x') === false
            ) {
                break;
            }

            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return $decoded;
    }

    private function getPropertyByCodeInsensitive(array $properties, string $code): array
    {
        $target = strtoupper(trim($code));
        foreach ($properties as $propertyCode => $property) {
            if (strtoupper((string)$propertyCode) === $target && is_array($property)) {
                return $property;
            }
        }

        return [];
    }

    private function resolveSingleFilePath($value): string
    {
        $id = $this->resolveSingleFileId($value);
        if ($id <= 0) {
            return '';
        }

        return (string)CFile::GetPath($id);
    }

    private function resolveSingleFileId($value): int
    {
        $id = (int)$value;

        return $id > 0 ? $id : 0;
    }

    private function resolveMultipleFilePaths($value): array
    {
        $fileIds = is_array($value) ? $value : [$value];
        $paths = [];

        foreach ($fileIds as $fileId) {
            $id = (int)$fileId;
            if ($id <= 0) {
                continue;
            }

            $path = (string)CFile::GetPath($id);
            if ($path === '') {
                continue;
            }

            $paths[] = $path;
        }

        return $paths;
    }

    private function resolveVideoMime(string $src): string
    {
        if ($src === '') {
            return '';
        }

        $path = parse_url($src, PHP_URL_PATH);
        $path = is_string($path) ? $path : $src;
        $ext = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'webm') {
            return 'video/webm';
        }

        if ($ext === 'ogg' || $ext === 'ogv') {
            return 'video/ogg';
        }

        return 'video/mp4';
    }

    private function resolveIblockIdByCode(string $code): int
    {
        if ($code === '') {
            return 0;
        }

        $res = CIBlock::GetList(
            [],
            [
                'CODE' => $code,
                'ACTIVE' => 'Y',
            ]
        );

        $row = $res ? $res->Fetch() : false;
        return (int)($row['ID'] ?? 0);
    }
}
