<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<nav class="header__top-nav" id="header__top-nav">
    <ul class="header__top-list">
        <?php

        foreach ($arResult['ITEMS'] as $item) {
        ?>
            <li>
                <a href="<?=$item[1]?>" class="header__nav-link"><?=$item[0]?></a>
            </li>
        <?php

        }
        ?>
    </ul>
    <div class="header__top-phone">
        <a href="tel:84955555555">8 495 555-55-55</a>
    </div>
    <div class="header__contacts">
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
