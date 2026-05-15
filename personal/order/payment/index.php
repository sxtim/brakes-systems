<?php

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Sale;

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle('Оплата заказа');
$APPLICATION->SetPageProperty('title', 'Оплата заказа');

$message = '';
$paymentContent = '';

if (!Loader::includeModule('sale')) {
    $message = 'Модуль магазина не подключен.';
} else {
    global $USER;

    $orderId = urldecode(urldecode((string)($_REQUEST['ORDER_ID'] ?? '')));
    $paymentId = (string)($_REQUEST['PAYMENT_ID'] ?? '');
    $hash = (string)($_REQUEST['HASH'] ?? '');
    $returnUrl = (string)($_REQUEST['RETURN_URL'] ?? '');

    $registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
    /** @var Sale\Order $orderClassName */
    $orderClassName = $registry->getOrderClassName();
    $useAccountNumber = Sale\Integration\Numerator\NumeratorOrder::isUsedNumeratorForOrder();

    $orderData = false;
    $checkedBySession = false;

    if (
        !$USER->IsAuthorized()
        && is_array($_SESSION['SALE_ORDER_ID'] ?? null)
        && $hash === ''
    ) {
        $realOrderId = 0;

        if ($useAccountNumber) {
            $dbRes = $orderClassName::getList([
                'filter' => [
                    '=LID' => SITE_ID,
                    '=ACCOUNT_NUMBER' => $orderId,
                ],
                'order' => ['DATE_UPDATE' => 'DESC'],
            ]);
            $orderData = $dbRes->fetch();
            if ($orderData) {
                $realOrderId = (int)$orderData['ID'];
            }
        } else {
            $realOrderId = (int)$orderId;
        }

        $checkedBySession = in_array($realOrderId, $_SESSION['SALE_ORDER_ID'], true);
    }

    if ($useAccountNumber && !$orderData) {
        $filter = [
            '=LID' => SITE_ID,
            '=ACCOUNT_NUMBER' => $orderId,
        ];

        if ($hash === '') {
            $filter['=USER_ID'] = (int)$USER->GetID();
        }

        $dbRes = $orderClassName::getList([
            'filter' => $filter,
            'order' => ['DATE_UPDATE' => 'DESC'],
        ]);
        $orderData = $dbRes->fetch();
    }

    if (!$orderData) {
        $filter = [
            '=LID' => SITE_ID,
            '=ID' => $orderId,
        ];

        if (!$checkedBySession && $hash === '') {
            $filter['=USER_ID'] = (int)$USER->GetID();
        }

        $dbRes = $orderClassName::getList([
            'filter' => $filter,
            'order' => ['DATE_UPDATE' => 'DESC'],
        ]);
        $orderData = $dbRes->fetch();
    }

    if (!$orderData) {
        $message = 'Заказ не найден.';
    } else {
        /** @var Sale\Order|null $order */
        $order = $orderClassName::load((int)$orderData['ID']);

        if (!$order) {
            $message = 'Заказ не найден.';
        } elseif (
            !Sale\OrderStatus::isAllowPay($order->getField('STATUS_ID'))
            || (
                $hash !== ''
                && (
                    $order->getHash() !== $hash
                    || !Sale\Helpers\Order::isAllowGuestView($order)
                )
            )
        ) {
            $message = 'Оплата для этого заказа сейчас недоступна.';
        } else {
            $payment = null;
            $paymentCollection = $order->getPaymentCollection();

            if ($paymentCollection) {
                if ($paymentId !== '') {
                    $paymentIds = Sale\PaySystem\Manager::getIdsByPayment($paymentId);
                    if (($paymentIds[1] ?? 0) > 0) {
                        $payment = $paymentCollection->getItemById((int)$paymentIds[1]);
                    }
                }

                if ($payment === null) {
                    foreach ($paymentCollection as $paymentItem) {
                        if (!$paymentItem->isInner() && !$paymentItem->isPaid()) {
                            $payment = $paymentItem;
                            break;
                        }
                    }
                }
            }

            if (!$payment) {
                $message = 'Не найдена неоплаченная оплата по заказу.';
            } else {
                $service = Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());

                if (!$service) {
                    $message = 'Платежная система недоступна.';
                } else {
                    if ($returnUrl !== '') {
                        $service->getContext()->setUrl($returnUrl);
                    }

                    ob_start();
                    $result = $service->initiatePay(
                        $payment,
                        Application::getInstance()->getContext()->getRequest()
                    );
                    $paymentContent = (string)ob_get_clean();

                    if (!$result->isSuccess()) {
                        $message = implode('<br>', array_map('htmlspecialcharsbx', $result->getErrorMessages()));
                    }
                }
            }
        }
    }
}

$currentRequestUri = (string)($_SERVER['REQUEST_URI'] ?? '/personal/order/payment/');
$safeRequestUri = htmlspecialcharsbx($currentRequestUri);

$paymentContentWithFallback = preg_replace(
    '/<form\s+action=(["\'])\1\s+method=(["\'])get\2>/i',
    '<form action="' . $safeRequestUri . '" method="get">',
    $paymentContent
);

if ($paymentContentWithFallback !== null && $paymentContentWithFallback !== $paymentContent) {
    $paymentContent = $paymentContentWithFallback;

    $hiddenPaymentFields = '';
    foreach ([
        'ORDER_ID' => $orderId ?? '',
        'PAYMENT_ID' => $paymentId ?? '',
        'HASH' => $hash ?? '',
        'RETURN_URL' => $returnUrl ?? '',
    ] as $fieldName => $fieldValue) {
        $fieldValue = (string)$fieldValue;
        if ($fieldValue === '') {
            continue;
        }

        $hiddenPaymentFields .= '<input type="hidden" name="' . htmlspecialcharsbx($fieldName) . '" value="' . htmlspecialcharsbx($fieldValue) . '">';
    }

    if ($hiddenPaymentFields !== '') {
        $paymentContent = preg_replace('/(<form\b[^>]*>)/i', '$1' . $hiddenPaymentFields, $paymentContent, 1);
    }
}
?>
<style>
    .payment-page {
        width: 100%;
        max-width: 1180px;
        min-height: 420px;
        margin: 0 auto;
        padding: 48px 16px 72px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
    }

    .payment-page__content {
        width: 100%;
        max-width: 520px;
    }

    .payment-page__content form {
        display: flex;
        justify-content: center;
    }

    .payment-page__content input[type="submit"],
    .payment-page__content button,
    .payment-page__content .btn {
        min-width: 180px;
        min-height: 44px;
        border: 1px solid #1f6fb2;
        border-radius: 8px;
        background: #1f6fb2;
        color: #fff;
        font-weight: 600;
        font-size: 14px;
        line-height: 1.2;
        padding: 10px 18px;
        cursor: pointer;
    }

    .payment-page__content input[type="submit"]:hover,
    .payment-page__content button:hover,
    .payment-page__content .btn:hover {
        border-color: #1a5f98;
        background: #1a5f98;
        color: #fff;
    }
</style>
<main class="payment-page">
    <div class="payment-page__content">
        <?php if ($message !== ''): ?>
            <p><?= $message ?></p>
        <?php else: ?>
            <?= $paymentContent ?>
        <?php endif; ?>
    </div>
</main>
<?php

require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php');
