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
                        <h3 class="main-cataloge__item-title"><a href="<?=$item['DETAIL_PAGE_URL']?>"><?=$item['NAME']?></a></h3>
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
                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn" data-add-basket>
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
