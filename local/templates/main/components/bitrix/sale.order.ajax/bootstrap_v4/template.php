<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;

$originalTemplateFolder = '/bitrix/modules/sale/install/components/bitrix/sale.order.ajax/templates/bootstrap_v4';
$request = Application::getInstance()->getContext()->getRequest();

if ((string)$request->get('ORDER_ID') !== '') {
    include __DIR__ . '/confirm.php';
    return;
}

Asset::getInstance()->addCss($originalTemplateFolder . '/style.css');

?>
<style>
    #bx-soa-paysystem {
        display: none !important;
    }
</style>
<?php

$templateFolder = $originalTemplateFolder;
include Application::getDocumentRoot() . $originalTemplateFolder . '/template.php';
