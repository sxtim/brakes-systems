<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div data-fls-spollers="" class="choose-auto__spollers spollers">
    <details class="choose-auto__spollers-item spollers__item">
        <summary class="choose-auto__spollers-title spollers__title">Выберите автомобиль</summary>
        <div class="choose-auto__spollers-body spollers__body">
            <div class="choose-auto__spollers-block">
                <h3 class="choose-auto__block-title">Марка</h3>
                <div class="choose-auto__block-row">
                    <div class="selector-row">
                        <div data-fls-slider="" class="selector-row__slider-1 swiper">
                            <div class="selector-row__wrapper-1 swiper-wrapper">
                                <?php

                                foreach ($arResult['FIRST'] as $item) {
                                ?>
                                    <div class="selector-row__slide-1 swiper-slide">
                                        <div class="selector-item<?php if ($arResult['FIRST_SELECT_ID'] == $item['ID']) {echo ' active';}?>">
                                            <a class="selector-icon" href="<?=$item['SECTION_PAGE_URL']?>">
                                                <picture>
                                                    <source media="(max-width: 600px)" srcset="<?=$item['UF_SVG']?>" type="image/webp">
                                                    <source media="(max-width: 1200px)" srcset="<?=$item['UF_SVG']?>" type="image/webp">
                                                    <img class="icon-inactive" alt="Img" src="<?=$item['UF_SVG']?>">
                                                </picture>
                                                <picture>
                                                    <source media="(max-width: 600px)" srcset="<?=$item['UF_SVG']?>" type="image/webp">
                                                    <source media="(max-width: 1200px)" srcset="<?=$item['UF_SVG']?>" type="image/webp">
                                                    <img class="icon-active" alt="Img" src="<?=$item['UF_SVG']?>">
                                                </picture>
                                            </a>
                                            <span class="selector-name"><?=$item['NAME']?></span>
                                        </div>
                                    </div>
                                <?php

                                }
                                ?>
                            </div>
                            <div class="selector-row__scrollbar-1 swiper-scrollbar"></div>
                        </div>
                        <button class="selector-row__prev-1 selector-row__btn"></button>
                        <button class="selector-row__next-1 selector-row__btn"></button>
                    </div>
                </div>
            </div>
            <?php

            if ($arResult['SECOND']) {
            ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Модель</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div data-fls-slider="" class="selector-row__slider-2 swiper">
                                <div class="selector-row__wrapper swiper-wrapper">
                                    <?php

                                    foreach ($arResult['SECOND'] as $item) {
                                        ?>
                                        <a href="<?=$item['SECTION_PAGE_URL']?>" class="selector-row__slide-2 swiper-slide<?php if ($arResult['SECOND_SELECT_ID'] == $item['ID']) {echo ' active';}?>">
                                            <div class="selector-item-2">
                                                <div class="selector-icon"><?=$item['NAME']?></div>
                                            </div>
                                        </a>
                                        <?php

                                    }
                                    ?>
                                </div>
                                <div class="selector-row__scrollbar-2 swiper-scrollbar"></div>
                            </div>
                            <button class="selector-row__prev-2 selector-row__btn"></button>
                            <button class="selector-row__next-2 selector-row__btn"></button>
                        </div>
                    </div>
                </div>
            <?php

            }
            ?>
            <?php

            if ($arResult['THIRD']) {
            ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Поколение</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div data-fls-slider="" class="selector-row__slider swiper">
                                <div class="selector-row__wrapper swiper-wrapper">
                                    <?php

                                    foreach ($arResult['THIRD'] as $item) {
                                    ?>
                                        <a href="<?=$item['SECTION_PAGE_URL']?>" class="selector-row__slide swiper-slide<?php if ($arResult['THIRD_SELECT_ID'] == $item['ID']) {echo ' active';}?>">
                                            <div class="selector-item-3">
                                                <div class="selector-icon"><?=$item['NAME']?></div>
                                            </div>
                                        </a>
                                    <?php

                                    }
                                    ?>
                                </div>
                                <div class="selector-row__scrollbar swiper-scrollbar"></div>
                            </div>
                            <button class="selector-row__prev selector-row__btn"></button>
                            <button class="selector-row__next selector-row__btn"></button>
                        </div>
                    </div>
                </div>
            <?php

            }
            ?>
        </div>
    </details>
</div>
