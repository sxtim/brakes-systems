<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div data-fls-spollers="" class="choose-auto__spollers spollers">
    <details class="choose-auto__spollers-item spollers__item" open data-fls-spollers-open>
        <summary class="choose-auto__spollers-title spollers__title">Выберите автомобиль</summary>
        <div class="choose-auto__spollers-body spollers__body">
            <?php if (!empty($arResult['CATEGORIES'])): ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Категория</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div class="selector-row__wrapper swiper-wrapper">
                                <?php foreach ($arResult['CATEGORIES'] as $item): ?>
                                    <a href="<?=$item['SECTION_PAGE_URL']?>"
                                       class="selector-row__slide-2 swiper-slide<?php if (($arResult['CATEGORY_SELECT_ID'] ?? 0) == $item['ID']) {echo ' active';}?>">
                                        <div class="selector-item-2">
                                            <div class="selector-icon"><?=$item['NAME']?></div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($arResult['MARKS'])): ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Марка</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div data-fls-slider="" class="selector-row__slider-1 swiper">
                                <div class="selector-row__wrapper-1 swiper-wrapper">
                                    <?php

                                    foreach ($arResult['MARKS'] as $item) {
                                    ?>
                                        <div class="selector-row__slide-1 swiper-slide">
                                            <div class="selector-item<?php if (($arResult['MARK_SELECT_ID'] ?? 0) == $item['ID']) {echo ' active';}?>">
                                                <a class="selector-icon" href="<?=$item['SECTION_PAGE_URL']?>">
                                                    <picture>
                                                        <source media="(max-width: 600px)" srcset="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>" type="image/webp">
                                                        <source media="(max-width: 1200px)" srcset="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>" type="image/webp">
                                                        <img class="icon-inactive" alt="<?=htmlspecialcharsbx($item['NAME'] ?? 'Img')?>" src="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>">
                                                    </picture>
                                                    <picture>
                                                        <source media="(max-width: 600px)" srcset="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>" type="image/webp">
                                                        <source media="(max-width: 1200px)" srcset="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>" type="image/webp">
                                                        <img class="icon-active" alt="<?=htmlspecialcharsbx($item['NAME'] ?? 'Img')?>" src="<?=htmlspecialcharsbx($item['UF_SVG'] ?? '')?>">
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
            <?php endif; ?>
            <?php

            if (!empty($arResult['MODELS'])) {
            ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Модель</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div data-fls-slider="" class="selector-row__slider-2 swiper">
                                <div class="selector-row__wrapper swiper-wrapper">
                                    <?php

                                    foreach ($arResult['MODELS'] as $item) {
                                        ?>
                                        <a href="<?=$item['SECTION_PAGE_URL']?>" class="selector-row__slide-2 swiper-slide<?php if (($arResult['MODEL_SELECT_ID'] ?? 0) == $item['ID']) {echo ' active';}?>">
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

            if (!empty($arResult['BODIES'])) {
            ?>
                <div class="choose-auto__spollers-block">
                    <h3 class="choose-auto__block-title">Поколение</h3>
                    <div class="choose-auto__block-row">
                        <div class="selector-row">
                            <div data-fls-slider="" class="selector-row__slider swiper">
                                <div class="selector-row__wrapper swiper-wrapper">
                                    <?php

                                    foreach ($arResult['BODIES'] as $item) {
                                    ?>
                                        <a href="<?=$item['SECTION_PAGE_URL']?>" class="selector-row__slide swiper-slide<?php if (($arResult['BODY_SELECT_ID'] ?? 0) == $item['ID']) {echo ' active';}?>">
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
