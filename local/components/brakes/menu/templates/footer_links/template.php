<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<ul class="footer__content-list">
    <?php foreach ((array)$arResult['ITEMS'] as $item): ?>
        <?php
        $title = trim((string)($item[0] ?? ''));
        if ($title === '') {
            continue;
        }

        $url = (string)($item[1] ?? '#');
        ?>
        <li class="footer__content-li">
            <a class="footer__content-link" href="<?= htmlspecialcharsbx($url !== '' ? $url : '#') ?>">
                <?= htmlspecialcharsbx($title) ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>
