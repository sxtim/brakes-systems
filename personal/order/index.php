<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оформление заказа");

$APPLICATION->IncludeComponent(
    "bitrix:sale.order.ajax",
    "bootstrap_v4",
    [
        "PRODUCT_COLUMNS_VISIBLE" => ["PREVIEW_PICTURE", "PROPS", "PRICE_FORMATED", "SUM"],
        "PATH_TO_BASKET" => "/personal/cart/",
        "PATH_TO_PERSONAL" => "/personal/",
        "PATH_TO_PAYMENT" => "/personal/order/payment/",
        "PATH_TO_AUTH" => "/auth/",
        "ALLOW_AUTO_REGISTER" => "Y",
        "SEND_NEW_USER_NOTIFY" => "Y",
        "DELIVERY_TO_PAYSYSTEM" => "d2p",
        "SHOW_ORDER_BUTTON" => "final_step",
        "SHOW_TOTAL_ORDER_BUTTON" => "Y",
        "SHOW_BASKET_HEADERS" => "N",
        "SHOW_COUPONS" => "N",
        "HIDE_ORDER_DESCRIPTION" => "Y",
        "ALLOW_USER_PROFILES" => "N",
        "ALLOW_NEW_PROFILE" => "N",
        "SHOW_STORES_IMAGES" => "N",
        "TEMPLATE_LOCATION" => "popup",
        "USE_PRELOAD" => "Y",
        "SET_TITLE" => "Y",
    ],
    false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
