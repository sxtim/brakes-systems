<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Оформление заказа");

$APPLICATION->IncludeComponent(
    "bitrix:sale.order.ajax",
    ".default",
    [
        "PATH_TO_BASKET" => "/personal/cart/",
        "PATH_TO_PERSONAL" => "/personal/",
        "PATH_TO_PAYMENT" => "/personal/order/payment/",
        "PATH_TO_AUTH" => "/auth/",
        "ALLOW_AUTO_REGISTER" => "Y",
        "SEND_NEW_USER_NOTIFY" => "Y",
        "DELIVERY_TO_PAYSYSTEM" => "d2p",
        "SHOW_ORDER_BUTTON" => "final_step",
        "SHOW_TOTAL_ORDER_BUTTON" => "Y",
        "TEMPLATE_LOCATION" => "popup",
        "USE_PRELOAD" => "Y",
        "SET_TITLE" => "Y",
    ],
    false
);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
