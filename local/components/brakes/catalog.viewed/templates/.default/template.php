<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (empty($arResult['ITEMS']) || !is_array($arResult['ITEMS'])) {
    return;
}

$templatePath = defined('SITE_TEMPLATE_PATH') ? SITE_TEMPLATE_PATH : '/local/templates/main';
$partialRelative = $templatePath . '/components/bitrix/catalog/.default/bitrix/catalog.section/.default/partials/product-card.php';
$partialPath = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $partialRelative;
$hasPartial = $partialPath !== '' && file_exists($partialPath);
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
                        <?php if ($hasPartial && is_array($item)) {
                            $card = $item;
                            include $partialPath;
                            unset($card);
                        } elseif (is_array($item)) { ?>
                            <a class="products-slider__card" href="<?=htmlspecialcharsbx((string)($item['DETAIL_PAGE_URL'] ?? '#'))?>">
                                <div class="products-slider__picture">
                                    <picture>
                                        <?php $img = htmlspecialcharsbx((string)($item['IMG'] ?? '')); ?>
                                        <source media="(max-width: 600px)" srcset="<?=$img?>" type="image/jpeg">
                                        <source media="(max-width: 1200px)" srcset="<?=$img?>" type="image/jpeg">
                                        <img class="products-slider__img" alt="Img" src="<?=$img?>">
                                    </picture>
                                </div>
                                <div class="products-slider__descr">
                                    <h3 class="products-slider__title"><?=htmlspecialcharsbx((string)($item['NAME'] ?? ''))?></h3>
                                </div>
                            </a>
                        <?php } ?>
                    </div>
                <?php

                }
                ?>
            </div>
        </div>
        <button class="watched-slider__next"></button>
    </div>
</div>
