<?php

use Bitrix\Main\Application;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$session = Application::getInstance()->getSession();
$data = $session->get('CATALOG_ITEM_VIEWED');

if (!is_array($data)) {
    $data = [];
}

$data[$arResult['ID']] = $arResult['ID'];

$session->set('CATALOG_ITEM_VIEWED', $data);
