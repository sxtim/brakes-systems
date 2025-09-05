<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (!$arResult['ITEMS']) {
    return;
}
?>
<div class="watched__block slider-block">
    <h2 class="products__title">Вы смотрели</h2>
    <div class="products__body">
        <button class="watched-slider__prev"></button>
        <div data-fls-slider="" class="watched-slider__slider swiper">
            <div class="products-slider__wrapper swiper-wrapper">
                <?php

                foreach ($arResult['ITEMS'] as $item) {
                ?>
                    <div class="products-slider__slide swiper-slide">
                        <a class="products-slider__card" href="<?=$item['DETAIL_PAGE_URL']?>">
                            <div class="products-slider__picture">
                                <picture>
                                    <source media="(max-width: 600px)" srcset="<?=$item['IMG']?>" type="image/webp">
                                    <source media="(max-width: 1200px)" srcset="<?=$item['IMG']?>" type="image/webp">
                                    <img class="products-slider__img" alt="Img" src="<?=$item['IMG']?>">
                                </picture>
                            </div>
                            <div class="products-slider__descr">
                                <h3 class="products-slider__title"><?=$item['NAME']?></h3>
                            </div>
                        </a>
                    </div>
                <?php

                }
                ?>
            </div>
        </div>
        <button class="watched-slider__next"></button>
    </div>
</div>
