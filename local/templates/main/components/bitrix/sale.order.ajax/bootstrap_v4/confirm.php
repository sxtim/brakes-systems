<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (($arParams['SET_TITLE'] ?? 'Y') === 'Y') {
    $APPLICATION->SetTitle('Заказ принят');
}

$order = $arResult['ORDER'] ?? [];
$accountNumber = (string)($order['ACCOUNT_NUMBER'] ?? $arResult['ACCOUNT_NUMBER'] ?? '');
$dateInsert = $order['DATE_INSERT'] ?? null;
$dateText = is_object($dateInsert) && method_exists($dateInsert, 'toUserTime')
    ? $dateInsert->toUserTime()->format('d.m.Y H:i')
    : '';
?>

<main class="page">
    <div class="page__container page__container--single">
        <div class="page__main">
            <div class="main__inner">
                <h1 class="main__title"><?php $APPLICATION->ShowTitle(false); ?></h1>

                <?php if (!empty($order)): ?>
                    <div class="row mb-5">
                        <div class="col">
                            <p>
                                <?php if ($accountNumber !== ''): ?>
                                    Номер заказа: <b><?=htmlspecialcharsbx($accountNumber)?></b><?php if ($dateText !== ''): ?> от <?=htmlspecialcharsbx($dateText)?><?php endif; ?>.
                                <?php else: ?>
                                    Ваш заказ принят.
                                <?php endif; ?>
                            </p>
                            <p>Менеджер свяжется с вами, проверит наличие, доставку и итоговую сумму.</p>
                            <p>Оплата будет доступна после согласования. Ссылку на оплату менеджер отправит в мессенджер.</p>
                        </div>
                    </div>

                    <?php if (($arParams['NO_PERSONAL'] ?? 'N') !== 'Y'): ?>
                        <div class="row mb-5">
                            <div class="col">
                                Статус заказа можно посмотреть в <a href="<?=htmlspecialcharsbx($arParams['PATH_TO_PERSONAL'] ?? '/personal/')?>">личном кабинете</a>.
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="row mb-2">
                        <div class="col">
                            <div class="alert alert-danger" role="alert">
                                <strong>Заказ не найден.</strong><br>
                                Проверьте номер заказа или обратитесь к менеджеру.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
