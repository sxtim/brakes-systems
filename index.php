<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Главная");
?>

<?$APPLICATION->IncludeComponent(
    "brakes:home.main",
    "",
    [
        "SETTINGS_IBLOCK_CODE" => "home_main_settings",
        "SETTINGS_ELEMENT_CODE" => "main",
        "PRODUCTS_IBLOCK_CODE" => "home_main_products",
        "CACHE_TYPE" => "A",
        "CACHE_TIME" => "3600",
    ]
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
