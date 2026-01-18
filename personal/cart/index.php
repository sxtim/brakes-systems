<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Корзина");

$APPLICATION->IncludeComponent(
    "bitrix:sale.basket.basket",
    ".default",
    [
        "COLUMNS_LIST" => ["NAME", "PRICE", "QUANTITY", "DELETE", "DISCOUNT"],
        "PATH_TO_ORDER" => "/personal/order/",
        "HIDE_COUPON" => "N",
        "SET_TITLE" => "Y",
    ],
    false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
