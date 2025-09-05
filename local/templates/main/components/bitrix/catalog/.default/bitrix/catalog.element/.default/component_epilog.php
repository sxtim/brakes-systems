<?php

use App\Brakes\Helper\Storage;
use Bitrix\Main\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

Storage::set('RECOMMENDED', $arResult['RECOMMENDED']);
Storage::set('ITEM_ID', $arResult['ID']);

$session = Application::getInstance()->getSession();
$data = $session->get('CATALOG_ITEM_VIEWED');

if ( ! is_array($data)) {
    $data = [];
}

$data[$arResult['ID']] = $arResult['ID'];

$session->set('CATALOG_ITEM_VIEWED', $data);
