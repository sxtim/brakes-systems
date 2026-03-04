<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

use Bitrix\Main\Loader;

global $USER;

if (!$USER || !$USER->IsAdmin()) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

if (!Loader::includeModule('iblock')) {
    echo 'Iblock module is not available';
    exit;
}

$siteId = defined('SITE_ID') ? SITE_ID : 's1';
$iblockTypeId = 'brakes_content';

$messages = [];

function upsertIblockType(string $typeId): void
{
    $type = CIBlockType::GetByID($typeId);
    if ($type && $type->Fetch()) {
        return;
    }

    $ib = new CIBlockType();
    $result = $ib->Add([
        'ID' => $typeId,
        'SECTIONS' => 'N',
        'IN_RSS' => 'N',
        'LANG' => [
            'ru' => [
                'NAME' => 'Контент Brakes',
                'SECTION_NAME' => '',
                'ELEMENT_NAME' => 'Элемент',
            ],
            'en' => [
                'NAME' => 'Brakes Content',
                'SECTION_NAME' => '',
                'ELEMENT_NAME' => 'Element',
            ],
        ],
    ]);

    if (!$result) {
        throw new RuntimeException((string)$ib->LAST_ERROR);
    }
}

function getIblockIdByCode(string $code): int
{
    $res = CIBlock::GetList([], ['CODE' => $code, 'ACTIVE' => 'Y']);
    $row = $res ? $res->Fetch() : false;
    return (int)($row['ID'] ?? 0);
}

function upsertIblock(string $typeId, string $siteId, string $code, string $name, int $sort = 100): int
{
    $existingId = getIblockIdByCode($code);
    if ($existingId > 0) {
        return $existingId;
    }

    $ib = new CIBlock();
    $iblockId = (int)$ib->Add([
        'ACTIVE' => 'Y',
        'NAME' => $name,
        'CODE' => $code,
        'LIST_PAGE_URL' => '',
        'DETAIL_PAGE_URL' => '',
        'IBLOCK_TYPE_ID' => $typeId,
        'SITE_ID' => [$siteId],
        'SORT' => $sort,
        'GROUP_ID' => [2 => 'R'],
        'INDEX_ELEMENT' => 'N',
        'INDEX_SECTION' => 'N',
    ]);

    if ($iblockId <= 0) {
        throw new RuntimeException((string)$ib->LAST_ERROR);
    }

    return $iblockId;
}

function upsertProperty(int $iblockId, array $property): void
{
    $code = (string)($property['CODE'] ?? '');
    if ($code === '') {
        return;
    }

    $res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $code]);
    $exists = $res ? $res->Fetch() : false;
    if ($exists) {
        return;
    }

    $prop = new CIBlockProperty();
    $property['IBLOCK_ID'] = $iblockId;
    $property['ACTIVE'] = 'Y';
    $property['FILTRABLE'] = 'N';
    $property['SEARCHABLE'] = 'N';

    $result = $prop->Add($property);
    if (!$result) {
        throw new RuntimeException((string)$prop->LAST_ERROR);
    }
}

function upsertElement(int $iblockId, string $code, string $name, int $sort = 100): void
{
    $res = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '=CODE' => $code],
        false,
        ['nTopCount' => 1],
        ['ID']
    );
    $row = $res ? $res->Fetch() : false;
    if (!empty($row['ID'])) {
        return;
    }

    $el = new CIBlockElement();
    $result = $el->Add([
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        'CODE' => $code,
        'NAME' => $name,
        'SORT' => $sort,
    ]);

    if (!$result) {
        throw new RuntimeException((string)$el->LAST_ERROR);
    }
}

try {
    upsertIblockType($iblockTypeId);
    $messages[] = 'IBlock type brakes_content: OK';

    $settingsIblockId = upsertIblock(
        $iblockTypeId,
        $siteId,
        'home_main_settings',
        'Главная: настройки'
    );
    $messages[] = 'IBlock home_main_settings: OK (' . $settingsIblockId . ')';

    $settingsProperties = [
        ['NAME' => 'Видео файл', 'CODE' => 'VIDEO_FILE', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 100],
        ['NAME' => 'Ссылка на видео', 'CODE' => 'VIDEO_URL', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 110],
        ['NAME' => 'Заголовок 1', 'CODE' => 'TITLE_1', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 120],
        ['NAME' => 'Заголовок 2', 'CODE' => 'TITLE_2', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 130],
        ['NAME' => 'Логотипы в слайде', 'CODE' => 'LOGO_SLIDER', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 140],
        ['NAME' => 'Наши проекты', 'CODE' => 'PROJECTS', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 150],
        ['NAME' => 'О нас: заголовок', 'CODE' => 'ABOUT_TITLE', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 160],
        ['NAME' => 'О нас: текст', 'CODE' => 'ABOUT_TEXT', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 170],
        ['NAME' => 'О нас: фото', 'CODE' => 'ABOUT_PHOTO', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 180],
        ['NAME' => 'Сертификаты', 'CODE' => 'CERTIFICATES', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'Y', 'SORT' => 190],
    ];

    foreach ($settingsProperties as $property) {
        upsertProperty($settingsIblockId, $property);
    }
    $messages[] = 'Properties for home_main_settings: OK';

    upsertElement($settingsIblockId, 'main', 'Главная', 100);
    $messages[] = 'Element main in home_main_settings: OK';

    $productsIblockId = upsertIblock(
        $iblockTypeId,
        $siteId,
        'home_main_products',
        'Главная: товары'
    );
    $messages[] = 'IBlock home_main_products: OK (' . $productsIblockId . ')';

    $productProperties = [
        ['NAME' => 'Подзаголовок', 'CODE' => 'SUBTITLE', 'PROPERTY_TYPE' => 'S', 'MULTIPLE' => 'N', 'SORT' => 100],
        ['NAME' => 'Текст (HTML)', 'CODE' => 'TEXT_HTML', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 110],
        ['NAME' => 'Характеристики (HTML)', 'CODE' => 'FEATURES_HTML', 'PROPERTY_TYPE' => 'S', 'USER_TYPE' => 'HTML', 'MULTIPLE' => 'N', 'SORT' => 120],
        ['NAME' => 'Фото', 'CODE' => 'PHOTO', 'PROPERTY_TYPE' => 'F', 'MULTIPLE' => 'N', 'SORT' => 130],
    ];

    foreach ($productProperties as $property) {
        upsertProperty($productsIblockId, $property);
    }
    $messages[] = 'Properties for home_main_products: OK';

    upsertElement($productsIblockId, 'product-1', 'Товар 1', 100);
    upsertElement($productsIblockId, 'product-2', 'Товар 2', 200);
    $messages[] = 'Elements product-1 and product-2: OK';

    echo implode('<br>', array_map('htmlspecialcharsbx', $messages));
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialcharsbx($e->getMessage());
}
