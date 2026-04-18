<?php

namespace App\Brakes\Order;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale\Internals\OrderPropsGroupTable;
use Bitrix\Sale\Internals\OrderPropsTable;
use Bitrix\Sale\Internals\PersonTypeTable;
use Bitrix\Sale\Internals\StatusLangTable;
use Bitrix\Sale\Internals\StatusTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;

class PaymentFlow
{
    public const STATUS_AWAITING_PAYMENT = 'AP';
    private const PAYMENT_LINK_PROP_CODE = 'PAYMENT_LINK';
    private const PAYMENT_LINK_COMMENT_MARKER = '[BRKS_PAYMENT_LINK]';

    public static function bootstrap(): void
    {
        if (!Loader::includeModule('sale')) {
            return;
        }

        try {
            self::ensureAwaitingPaymentStatus();
            self::ensurePaymentLinkProperty();
            self::ensureAllowPayStartsFromAwaitingStatus();
            self::ensureGuestCanOpenAwaitingPaymentOrders();
        } catch (\Throwable $exception) {
            // Keep the storefront alive even if the setup step cannot touch Sale tables.
        }
    }

    public static function prepareOrderBeforeSave(Order $order): void
    {
        self::syncSingleExternalPaymentWithGoodsTotal($order);

        if ($order->getField('STATUS_ID') === self::STATUS_AWAITING_PAYMENT) {
            self::syncPaymentLinkProperty($order);
            self::syncPaymentLinkComment($order);
        }
    }

    public static function buildPaymentPath(Order $order, ?Payment $payment = null): string
    {
        if ($payment === null) {
            $payment = self::getSingleExternalPayment($order);
        }

        $orderNumber = urlencode((string)$order->getField('ACCOUNT_NUMBER'));
        $paymentNumber = $payment instanceof Payment
            ? urlencode((string)$payment->getField('ACCOUNT_NUMBER'))
            : '';

        $path = '/personal/order/payment/?ORDER_ID=' . $orderNumber;
        if ($paymentNumber !== '') {
            $path .= '&PAYMENT_ID=' . $paymentNumber;
        }
        $hash = trim((string)$order->getHash());
        if ($hash !== '') {
            $path .= '&HASH=' . urlencode($hash);
        }

        $host = '';
        $scheme = 'https';
        try {
            $request = Context::getCurrent()->getRequest();
            $host = (string)$request->getHttpHost();
            $scheme = $request->isHttps() ? 'https' : 'http';
        } catch (\Throwable $exception) {
            $host = '';
        }

        if ($host === '') {
            $host = (string)Option::get('main', 'server_name', '');
            $scheme = 'https';
        }

        return $host !== '' ? $scheme . '://' . $host . $path : $path;
    }

    private static function ensureAwaitingPaymentStatus(): void
    {
        $status = StatusTable::getByPrimary(self::STATUS_AWAITING_PAYMENT)->fetch();
        if (!$status) {
            StatusTable::add([
                'ID' => self::STATUS_AWAITING_PAYMENT,
                'TYPE' => StatusTable::TYPE_ORDER,
                'SORT' => 150,
                'NOTIFY' => 'Y',
                'COLOR' => '#f5a623',
                'XML_ID' => 'brakes_awaiting_payment',
            ]);
        }

        self::ensureStatusLang('ru', 'Ожидает оплаты', 'Заказ согласован менеджером и ожидает онлайн-оплаты');
        self::ensureStatusLang('en', 'Awaiting payment', 'The order has been approved and is awaiting online payment');
    }

    private static function ensureStatusLang(string $languageId, string $name, string $description): void
    {
        $exists = StatusLangTable::getList([
            'filter' => [
                '=STATUS_ID' => self::STATUS_AWAITING_PAYMENT,
                '=LID' => $languageId,
            ],
            'select' => ['STATUS_ID'],
            'limit' => 1,
        ])->fetch();

        if ($exists) {
            return;
        }

        StatusLangTable::add([
            'STATUS_ID' => self::STATUS_AWAITING_PAYMENT,
            'LID' => $languageId,
            'NAME' => $name,
            'DESCRIPTION' => $description,
        ]);
    }

    private static function ensureAllowPayStartsFromAwaitingStatus(): void
    {
        if (Option::get('sale', 'allow_pay_status', '') !== self::STATUS_AWAITING_PAYMENT) {
            Option::set('sale', 'allow_pay_status', self::STATUS_AWAITING_PAYMENT);
        }
    }

    private static function ensureGuestCanOpenAwaitingPaymentOrders(): void
    {
        if (Option::get('sale', 'allow_guest_order_view', 'N') !== 'Y') {
            Option::set('sale', 'allow_guest_order_view', 'Y');
        }

        $rawStatuses = (string)Option::get('sale', 'allow_guest_order_view_status', '');
        $statuses = $rawStatuses !== '' ? @unserialize($rawStatuses, ['allowed_classes' => false]) : [];
        if (!is_array($statuses)) {
            $statuses = [];
        }

        if (!in_array(self::STATUS_AWAITING_PAYMENT, $statuses, true)) {
            $statuses[] = self::STATUS_AWAITING_PAYMENT;
            Option::set('sale', 'allow_guest_order_view_status', serialize(array_values(array_unique($statuses))));
        }
    }

    private static function ensurePaymentLinkProperty(): void
    {
        $personTypes = PersonTypeTable::getList([
            'filter' => ['=ACTIVE' => 'Y'],
            'select' => ['ID'],
        ]);

        while ($personType = $personTypes->fetch()) {
            $personTypeId = (int)$personType['ID'];
            if ($personTypeId <= 0) {
                continue;
            }

            $exists = OrderPropsTable::getList([
                'filter' => [
                    '=PERSON_TYPE_ID' => $personTypeId,
                    '=CODE' => self::PAYMENT_LINK_PROP_CODE,
                ],
                'select' => ['ID', 'UTIL', 'ACTIVE', 'NAME', 'DESCRIPTION', 'SORT'],
                'limit' => 1,
            ])->fetch();
            if ($exists) {
                $updates = [];
                if (($exists['UTIL'] ?? 'N') !== 'N') {
                    $updates['UTIL'] = 'N';
                }
                if (($exists['ACTIVE'] ?? 'Y') !== 'Y') {
                    $updates['ACTIVE'] = 'Y';
                }
                if ((string)($exists['NAME'] ?? '') !== 'Ссылка на оплату') {
                    $updates['NAME'] = 'Ссылка на оплату';
                }
                if ((string)($exists['DESCRIPTION'] ?? '') !== 'Ссылка для клиента. Отправляется менеджером после согласования заказа.') {
                    $updates['DESCRIPTION'] = 'Ссылка для клиента. Отправляется менеджером после согласования заказа.';
                }
                if ((int)($exists['SORT'] ?? 0) !== 700) {
                    $updates['SORT'] = 700;
                }

                if ($updates !== []) {
                    OrderPropsTable::update((int)$exists['ID'], $updates);
                }
                continue;
            }

            $groupId = self::getOrderPropertyGroupId($personTypeId);
            if ($groupId <= 0) {
                continue;
            }

            OrderPropsTable::add([
                'PERSON_TYPE_ID' => $personTypeId,
                'NAME' => 'Ссылка на оплату',
                'TYPE' => 'STRING',
                'REQUIRED' => 'N',
                'DEFAULT_VALUE' => '',
                'SORT' => 700,
                'USER_PROPS' => 'N',
                'IS_LOCATION' => 'N',
                'PROPS_GROUP_ID' => $groupId,
                'DESCRIPTION' => 'Ссылка для клиента. Отправляется менеджером после согласования заказа.',
                'IS_EMAIL' => 'N',
                'IS_PROFILE_NAME' => 'N',
                'IS_PAYER' => 'N',
                'IS_LOCATION4TAX' => 'N',
                'IS_FILTERED' => 'N',
                'CODE' => self::PAYMENT_LINK_PROP_CODE,
                'IS_ZIP' => 'N',
                'IS_PHONE' => 'N',
                'ACTIVE' => 'Y',
                'UTIL' => 'N',
                'INPUT_FIELD_LOCATION' => 0,
                'MULTIPLE' => 'N',
                'IS_ADDRESS' => 'N',
                'IS_ADDRESS_FROM' => 'N',
                'IS_ADDRESS_TO' => 'N',
                'SETTINGS' => [
                    'MINLENGTH' => '',
                    'MAXLENGTH' => '',
                    'PATTERN' => '',
                    'MULTILINE' => 'N',
                    'SIZE' => '',
                ],
                'ENTITY_REGISTRY_TYPE' => 'ORDER',
                'ENTITY_TYPE' => 'ORDER',
            ]);
        }
    }

    private static function getOrderPropertyGroupId(int $personTypeId): int
    {
        $group = OrderPropsGroupTable::getList([
            'filter' => ['=PERSON_TYPE_ID' => $personTypeId],
            'select' => ['ID'],
            'order' => ['SORT' => 'ASC', 'ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        return (int)($group['ID'] ?? 0);
    }

    private static function syncSingleExternalPaymentWithGoodsTotal(Order $order): void
    {
        $payment = self::getSingleExternalPayment($order);
        if (!$payment instanceof Payment || $payment->isPaid()) {
            return;
        }

        $basket = $order->getBasket();
        if (!$basket) {
            return;
        }

        $goodsTotal = round((float)$basket->getPrice(), 2);
        if ($goodsTotal <= 0) {
            return;
        }

        if (abs((float)$payment->getSum() - $goodsTotal) > 0.01) {
            $payment->setField('SUM', $goodsTotal);
        }
    }

    private static function syncPaymentLinkProperty(Order $order): void
    {
        $payment = self::getSingleExternalPayment($order);
        if (!$payment instanceof Payment || $payment->isPaid()) {
            return;
        }

        $property = $order->getPropertyCollection()->getItemByOrderPropertyCode(self::PAYMENT_LINK_PROP_CODE);
        if (!$property) {
            return;
        }

        $link = self::buildPaymentPath($order, $payment);
        if ((string)$property->getValue() !== $link) {
            $property->setValue($link);
        }
    }

    private static function syncPaymentLinkComment(Order $order): void
    {
        $payment = self::getSingleExternalPayment($order);
        if (!$payment instanceof Payment || $payment->isPaid()) {
            return;
        }

        $linkLine = self::PAYMENT_LINK_COMMENT_MARKER . ' Ссылка на оплату: ' . self::buildPaymentPath($order, $payment);
        $comments = trim((string)$order->getField('COMMENTS'));

        if (strpos($comments, self::PAYMENT_LINK_COMMENT_MARKER) !== false) {
            $comments = (string)preg_replace(
                '/^' . preg_quote(self::PAYMENT_LINK_COMMENT_MARKER, '/') . '.*$/m',
                $linkLine,
                $comments
            );
        } else {
            $comments = trim($comments . "\n" . $linkLine);
        }

        if ((string)$order->getField('COMMENTS') !== $comments) {
            $order->setField('COMMENTS', $comments);
        }
    }

    private static function getSingleExternalPayment(Order $order): ?Payment
    {
        $payments = [];
        foreach ($order->getPaymentCollection() as $payment) {
            if (!$payment->isInner()) {
                $payments[] = $payment;
            }
        }

        return count($payments) === 1 ? $payments[0] : null;
    }
}
