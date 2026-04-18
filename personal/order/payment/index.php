<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

$APPLICATION->SetTitle('Оплата заказа');

ob_start();
$APPLICATION->IncludeComponent(
    'bitrix:sale.order.payment',
    '',
    []
);
$content = ob_get_clean();

$currentRequestUri = (string)($_SERVER['REQUEST_URI'] ?? '/personal/order/payment/');
$safeRequestUri = htmlspecialcharsbx($currentRequestUri);

$content = preg_replace(
    '/<form\s+action=(["\'])\1\s+method=(["\'])get\2>/i',
    '<form action="' . $safeRequestUri . '" method="get">',
    $content
);

echo $content;

?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var query = window.location.search;
    if (!query) {
        return;
    }

    document.querySelectorAll('form').forEach(function (form) {
        var action = form.getAttribute('action') || '';
        var method = (form.getAttribute('method') || '').toLowerCase();

        if (action === '' && method === 'get') {
            form.setAttribute('action', window.location.pathname + query);
        }
    });
});
</script>
<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
