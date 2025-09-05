<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<nav class="menu__body">
    <div class="menu__container">
        <ul class="menu__list">
            <?php

            foreach ($arResult['SECTIONS'] as $i => $item) {
                if ($item['DEPTH_LEVEL'] == 1) {
            ?>
                    <li class="menu__item">
                        <div data-fls-spollers="576,max"
                             class="spollers">
                            <details class="menu-spollers__item spollers__item">
                                <summary
                                        class="menu-spollers__title spollers__title">
                                    <div class="spollers__icon-box">
                                        <img class="spollers__title-icon"
                                             src="<?=$item['SVG']?>"
                                             alt="Image">
                                    </div>
                                    <p class="spollers__title-text">
                                        <?=$item['NAME']?>
                                    </p>
                                </summary>
                                <?php

                                if ($arResult['SECTIONS'][$i + 1]['DEPTH_LEVEL'] > $item['DEPTH_LEVEL']) {
                                ?>
                                    <div class="menu-spollers__body spollers__body">
                                        <!-- -------------------------------------------------- -->
                                        <div data-fls-spollers="1920,max"
                                             class="spollers">
                                <?php

                                }
                                ?>
                <?php

                } elseif ($item['DEPTH_LEVEL'] == 2) {
                ?>
                    <details
                            class="submenu-spollers__item spollers__item">
                        <summary class="submenu-spollers__title spollers__title">
                            <?=$item['NAME']?>
                        </summary>
                            <?php

                            if ($arResult['SECTIONS'][$i + 1]['DEPTH_LEVEL'] > $item['DEPTH_LEVEL']) {
                            ?>
                                <div class="submenu-spollers__body spollers__body">
                                    <ul class="submenu-spollers__list">
                            <?php

                            }
                            ?>
                    <?php

                    if ($arResult['SECTIONS'][$i + 1]['DEPTH_LEVEL'] < $item['DEPTH_LEVEL']) {
                    ?>
                    </details>
                        </div>
                        </div>
                        </details>
                        </div>
                        </li>
                    <?php

                    }
                    ?>
                <?php

                } elseif ($item['DEPTH_LEVEL'] == 3) {

                ?>
                    <li class="submenu-spollers__li">
                        <a href="<?=$item['SECTION_PAGE_URL']?>" class="">
                            <?=$item['NAME']?>
                        </a>
                    </li>

                    <?php

                    if ($arResult['SECTIONS'][$i + 1]['DEPTH_LEVEL'] < $item['DEPTH_LEVEL']) {
                    ?>
                            </ul>
                        </div>
                        </details>
                        <?php

                        if (
                            $arResult['SECTIONS'][$i + 1]['DEPTH_LEVEL'] == 1
                            || !isset($arResult['SECTIONS'][$i + 1])
                        ) {
                        ?>
                            </div>
                            </div>
                            </details>
                            </div>
                            </li>
                        <?php

                        }
                        ?>
                    <?php

                    }
                    ?>

                    <?php

                    }
                    ?>
            <?php

            }
            ?>
        </ul>
    </div>
</nav>
