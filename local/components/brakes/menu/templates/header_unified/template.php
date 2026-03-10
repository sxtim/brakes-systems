<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<nav class="header__top-nav header__top-nav--unified" id="header__top-nav">
    <ul class="header__top-list header__top-list--unified">
        <?php foreach ((array)$arResult['ITEMS'] as $index => $item): ?>
            <?php
            $title = trim((string)($item[0] ?? ''));
            if ($title === '') {
                continue;
            }

            $url = (string)($item[1] ?? '#');
            $params = is_array($item[3] ?? null) ? $item[3] : [];
            $isPrimary = (($params['PRIMARY'] ?? '') === 'Y') || $index === 0;
            ?>
            <li class="header__top-item<?= $isPrimary ? ' header__top-item--primary' : '' ?>">
                <a href="<?= htmlspecialcharsbx($url !== '' ? $url : '#') ?>" class="header__nav-link">
                    <?php if ($isPrimary): ?>
                        <img class="header__nav-link-arrow"
                             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/arrow_bottom.svg"
                             alt=""
                             aria-hidden="true">
                    <?php endif; ?>
                    <span class="header__nav-link-text"><?= htmlspecialcharsbx($title) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <div class="header__top-phone header__top-phone--unified">
        <a href="tel:84955555555">8 495 555-55-55</a>
    </div>
    <div class="header__contacts header__contacts--unified">
        <p class="header__contacts-text">Присоединяйтесь к
            нам:</p>
        <ul class="header__contacts-list">
            <li class="header__contacts-li">
                <a class="header__contacts-link" href="#">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/1.svg"
                         alt="Image">
                </a>
            </li>
            <li class="header__contacts-li">
                <a class="header__contacts-link" href="#">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/2.svg"
                         alt="Image">
                </a>
            </li>
            <li class="header__contacts-li">
                <a class="header__contacts-link" href="#">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/3.svg"
                         alt="Image">
                </a>
            </li>
            <li class="header__contacts-li">
                <a class="header__contacts-link" href="#">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/4.svg"
                         alt="Image">
                </a>
            </li>
            <li class="header__contacts-li">
                <a class="header__contacts-link" href="#">
                    <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/5.svg"
                         alt="Image">
                </a>
            </li>
        </ul>
    </div>
</nav>
