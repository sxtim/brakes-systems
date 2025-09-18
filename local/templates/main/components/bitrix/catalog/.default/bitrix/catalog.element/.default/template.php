<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div class="main__overlay">
    <div class="main__content">
        <div class="main__media">
            <?php

            if ($arResult['GALLERY']) {
            ?>
                <div data-fls-slider="" class="swiper main-swiper">
                    <div class="swiper-wrapper main-swiper__wrapper gallery" data-fls-gallery="">
                        <?php

                        foreach ($arResult['GALLERY'] as $val) {
                        ?>
                            <div class="swiper-slide main-swiper__slide">
                                <a class="main-swiper__gallery__image gallery__image" href="<?=$val?>">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=$val?>" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=$val?>" type="image/webp">
                                        <img class="main-swiper__img gallery__preview" alt="Img" src="<?=$val?>">
                                    </picture>
                                </a>
                            </div>
                        <?php

                        }
                        ?>
                    </div>
                </div>

                <!-- Слайдер мініатюр -->
                <div class="thumbs-swiper__overlay">
                    <button class="thumbs-swiper__prev"></button>
                    <div data-fls-slider="" class="swiper thumbs-swiper">
                        <div class="thumbs-swiper__wrapper swiper-wrapper">
                            <?php

                            foreach ($arResult['GALLERY'] as $val) {
                            ?>
                                <div class="thumbs-swiper__slide swiper-slide">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=$val?>" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=$val?>" type="image/webp">
                                        <img class="thumbs-swiper__img" alt="Img" src="<?=$val?>">
                                    </picture>
                                </div>
                            <?php

                            }
                            ?>
                        </div>
                    </div>
                    <button class="thumbs-swiper__next"></button>
                </div>
            <?php

            }
            ?>
        </div>
        <div class="main__details main-details" data-fls-dynamic=".main__overlay, 1199.98">
            <div data-fls-dynamic=".main__media, 1199.98, 0" class="main-details__status">
                <div class="main-details__status-item status-item--1 active">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-1.svg" alt="Image">
                    <span class="main-details__status-text">В наличии</span>
                </div>
                <div class="main-details__status-item status-item--2">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-2.svg" alt="Image">
                    <span class="main-details__status-text">Нет в наличии</span>
                </div>
                <div class="main-details__status-item status-item--3">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-3.svg" alt="Image">
                    <span class="main-details__status-text">Под заказ</span>
                </div>
            </div>
            <div class="main-details__price">
<!--                <div class="main-details__price-top">-->
<!--                    <span class="main-details__price-action">-25%</span>-->
<!--                    <span class="main-details__price-old">170 000 ₽%</span>-->
<!--                </div>-->
                <?php

                if (isset($arResult['ITEM_PRICES'][0]['PRICE'])) {
                ?>
                    <span class="main-details__price-new"><?=number_format($arResult['ITEM_PRICES'][0]['PRICE'], 0, '.', ' ')?> ₽</span>
                <?php

                }
                ?>
            </div>
            <div class="main-details__feature">
                <div class="main-cataloge__info">
                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                        <details class="spollers__item">
                            <summary class="main-details__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Да</div>
                                <div class="main-cataloge__sublist-item">Нет</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Рисунок ротора:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">НЕТ</div>
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Лого на суппорт:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Электроручник</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Да</div>
                                <div class="main-cataloge__sublist-item">Нет</div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
            <div class="main-details__shoping">
                <button data-fls-addtocart-button="" class="main-details__shoping-btn">
                    <span class="main-details__shoping-text">В корзину</span>
                </button>
                <button data-fls-like-image="" data-fls-like-button="" class="main-details__shoping-like"></button>
            </div>
            <button data-fls-popup-link="speedBuy" class="main-details__buy" href="#">Купить в один клик</button>
        </div>
    </div>
</div>
<div class="main__services">
    <?php

    if ($arResult['DELIVERY']) {
    ?>
        <a href="<?=$arResult['DELIVERY']['UF_LINK']?>" target="_blank" class="main__services-item main__services-item--1">
            <img class="main__services-icon" src="<?=$arResult['DELIVERY']['UF_FILE']?>" alt="Image">
            <span class="main__services-text"><?=$arResult['DELIVERY']['UF_DESCRIPTION']?></span>
        </a>
    <?php

    }
    ?>
    <div class="main__services-item main__services-item--2">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-2.svg" alt="Image">
        <span class="main__services-text">Доставка CDEK</span>
    </div>
    <div class="main__services-item main__services-item--3 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-3.svg" alt="Image">
        <span class="main__services-text">Самовывоз</span>
    </div>
    <div class="main__services-item main__services-item--4 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-4.svg" alt="Image">
        <span class="main__services-text">Гарантия</span>
    </div>
    <div class="main__services-item main__services-item--5 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-5.svg" alt="Image">
        <span class="main__services-text">Рассрочка</span>
    </div>
</div>
<div class="main__descr">
    <?php

    if (!empty($arResult['DETAIL_TEXT'])) {
        echo $arResult['DETAIL_TEXT'];
    } elseif (!empty($arResult['PROPERTIES']['CUSTOM_DESCRIPTION']['~VALUE']['TEXT'])) {
        echo $arResult['PROPERTIES']['CUSTOM_DESCRIPTION']['~VALUE']['TEXT'];
    }
    ?>
</div>
<div class="main__details details">
    <?php

    foreach ($arResult['PROPERTIES'] as $prop) {
        switch ($prop['CODE']) {
            case 'VIDEO_LINK':
            case 'LINK_PHOTO':
            case 'CML2_TRAITS':
            case 'COLOR':
            case 'RECOMMENDED':
            case 'CML2_BASE_UNIT':
            case 'CUSTOM_DESCRIPTION':
            case 'DELIVERY':
                continue(2);
        }

        if (!$prop['VALUE']) {
            continue;
        }
    ?>
        <div class="details-row">
            <span class="details-label"><?=$prop['NAME']?>:</span>
            <span class="details-dots"></span>
            <span class="details-value"><?=$prop['VALUE']?></span>
        </div>
    <?php

    }
    ?>
</div>
<div data-fls-popup="speedBuy" aria-hidden="true" class="popup">
    <div data-fls-popup-wrapper="" class="popup__wrapper">
        <div data-fls-popup-body="" class="popup__body">
            <button data-fls-popup-close="" type="button" class="popup__close">
                <svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
                    <path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
                </svg>
            </button>
            <div data-fls-popup-content="" class="popup__text">
                <div class="popup__login-signin login__signin">
                    <h1 class="main__title">Купить в один клик</h1>
                    <form class="basket__form contacts-form" action="#">
                        <div class="basket__form-item contacts-form__item">
                            <label class="basket__label contacts-form__label" for="basketInput1">Имя</label>
                            <div class="basket__input-wrapper contacts-form__input-wrapper">
                                <img class="basket__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
                                <input class="basket__input contacts-form__input contacts-form__input--user" id="basketInput1" placeholder="" type="text">
                            </div>
                        </div>
                        <div class="basket__form-item contacts-form__item">
                            <label class="basket__label contacts-form__label" for="basketInput2">Номер телефона</label>
                            <div class="basket__input-wrapper contacts-form__input-wrapper">
                                <img class="basket__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                                <input class="basket__input contacts-form__input contacts-form__input--tel" id="basketInput2" placeholder="" type="number">
                            </div>
                        </div>
                        <button class="contacts-form-btn main-cataloge__shoping-btn">
                            <span class="main-cataloge__shoping-text">Заказать</span>
                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
