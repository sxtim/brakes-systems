<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div class="main__cataloge main-cataloge">
    <div class="main-cataloge__top">
        <div class="main-cataloge__select custom-select-container" id="sort-select">
            <button class="select-toggle" aria-haspopup="listbox" aria-expanded="false">Сортировать по</button>
            <ul class="select-options" role="listbox">
                <li class="option" data-value="price_asc">По цене (возростание)</li>
                <li class="option" data-value="price_desc">По цене (снижение)</li>
                <li class="option" data-value="size">По размеру</li>
                <li class="option" data-value="year">По году выпуска</li>
            </ul>
        </div>
        <div class="main-cataloge__top-controls">
            <button class="main-cataloge__btn-grid active main-cataloge__btn" data-view="grid"></button>
            <button class="main-cataloge__btn-list main-cataloge__btn" data-view="list"></button>
        </div>
    </div>
    <div class="main-cataloge__body view-grid">
        <?php

        foreach ($arResult['ITEMS'] as $item) {
        ?>
            <div class="main-cataloge__item">
                <a class="main-cataloge__picture" href="<?=$item['DETAIL_PAGE_URL']?>">
                    <picture>
                        <source media="(max-width: 600px)" srcset="<?=$item['IMG']?>" type="image/webp">
                        <source media="(max-width: 1200px)" srcset="<?=$item['IMG']?>" type="image/webp">
                        <img class="main-cataloge__img" alt="Image" src="<?=$item['IMG']?>">
                    </picture>
                </a>
                <div class="main-cataloge__item-content">
                    <div class="main-cataloge__item-top">
                        <h3 class="main-cataloge__item-title"><a href="<?=$item['DETAIL_PAGE_URL']?>"><?=str_replace(["&nbsp;", "\xC2\xA0"], " ", $item['NAME'])?></a></h3>
                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                    </div>
                    <div class="main-cataloge__details main__details details">
                        <div class="main-cataloge__details-row details-row">
                            <span class="main-cataloge__details-label details-label">Артикул</span>
                            <span class="main-cataloge__details-dots details-dots"></span>
                            <span class="main-cataloge__details-value details-value"><?=$item['PROPERTIES']['CML2_ARTICLE']['VALUE']?></span>
                        </div>
                        <div class="main-cataloge__details-row details-row">
                            <span class="main-cataloge__details-label details-label">Производитель:</span>
                            <span class="main-cataloge__details-dots details-dots"></span>
                            <span class="main-cataloge__details-value details-value"><?=$item['PROPERTIES']['MANUFACTURER']['VALUE']?></span>
                        </div>
                        <div class="main-cataloge__details-row details-row">
                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                            <span class="main-cataloge__details-dots details-dots"></span>
                            <span class="main-cataloge__details-value details-value"><?=$item['PROPERTIES']['NUMBER_PISTONS']['VALUE']?></span>
                        </div>
                        <div class="main-cataloge__details-row details-row">
                            <span class="main-cataloge__details-label details-label">Ось:</span>
                            <span class="main-cataloge__details-dots details-dots"></span>
                            <span class="main-cataloge__details-value details-value"><?=$item['PROPERTIES']['INSTALLATION_AXIS']['VALUE']?></span>
                        </div>
                    </div>
                    <?php

                    if ($item['DISPLAY_PROPERTIES']['COLOR']['VALUE']) {
                    ?>
                        <div class="main-cataloge__colors">
                            <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                            <div class="main-cataloge__colors-box">
                                <?php

                                foreach ($item['DISPLAY_PROPERTIES']['COLOR']['VALUE'] as $i => $val) {
                                ?>
                                    <button class="main-cataloge__colors-item cataloge__color--<?=$item['DISPLAY_PROPERTIES']['COLOR']['VALUE_XML_ID'][$i]?>"></button>
                                <?php

                                }
                                ?>
                            </div>
                        </div>
                    <?php

                    }
                    ?>
                </div>
                <div class="main-cataloge__info">
                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                        <details class="spollers__item main-cataloge__feature-item--big">
                            <summary class="main-cataloge__feature-item spollers__title">Двусоставная конструкция диска:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Да</div>
                                <div class="main-cataloge__sublist-item">Нет</div>
                            </div>
                        </details>
                        <details class="spollers__item main-cataloge__feature-item--big">
                            <summary class="main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-cataloge__feature-item spollers__title">Электроручник</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Да</div>
                                <div class="main-cataloge__sublist-item">Нет</div>
                            </div>
                        </details>
                    </div>
                    <div class="main-cataloge__price"><?=number_format($item['ITEM_PRICES'][0]['PRICE'], 0, '.', ' ')?> ₽</div>
                    <div class="main-cataloge__bottom-controls">
                        <button data-fls-popup-link="speedBuy"
                                class="main-cataloge__buy"
                                data-product-name="<?= $item['NAME'] ?>"
                                data-product-url="<?= $item['DETAIL_PAGE_URL'] ?>"
                                data-options='{}'>Купить в один клик</button>
                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                            <span class="main-cataloge__shoping-text">В корзину</span>
                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                        </button>
                    </div>
                </div>
            </div>
        <?php

        }
        ?>
    </div>
</div>
<?php

echo $arResult['NAV_STRING'];
?>
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
                <?$APPLICATION->IncludeComponent(
                    "bitrix:form.result.new",
                    "one_click",
                    Array(
                        "AJAX_MODE" => "Y",
                        "AJAX_OPTION_ADDITIONAL" => "",
                        "AJAX_OPTION_HISTORY" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "CACHE_TIME" => "3600",
                        "CACHE_TYPE" => "A",
                        "CHAIN_ITEM_LINK" => "",
                        "CHAIN_ITEM_TEXT" => "",
                        "EDIT_URL" => "",
                        "IGNORE_CUSTOM_TEMPLATE" => "N",
                        "LIST_URL" => "",
                        "SEF_MODE" => "N",
                        "SUCCESS_URL" => "",
                        "USE_EXTENDED_ERRORS" => "N",
                        "VARIABLE_ALIASES" => Array(
                            "RESULT_ID" => "RESULT_ID",
                            "WEB_FORM_ID" => "WEB_FORM_ID"
                        ),
                        "WEB_FORM_ID" => "QUICK_ORDER"
                    )
                );?>
            </div>
        </div>
    </div>
</div>
