<?php

use Bitrix\Iblock\SectionTable;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$APPLICATION->IncludeComponent(
    "brakes:catalog.filter",
    "",
    [
        "IBLOCK_ID" => 1,
        "SECTION"   => $arResult["VARIABLES"]["SECTION_CODE"],
    ]
);