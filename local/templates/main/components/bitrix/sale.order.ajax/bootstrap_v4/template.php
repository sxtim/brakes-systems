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
?>
<script>
BX.ready(function () {
    var source = 'Ошибка регистрации нового пользователя: Указан некорректный номер телефона.';
    var target = 'Укажите корректный номер телефона, например +79001234567.';

    var normalizePhoneError = function () {
        var errors = document.querySelectorAll('.alert.alert-danger');
        for (var i = 0; i < errors.length; i++) {
            if (errors[i].textContent.indexOf(source) !== -1) {
                errors[i].textContent = target;
            }
        }
    };

    normalizePhoneError();
    new MutationObserver(normalizePhoneError).observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>
