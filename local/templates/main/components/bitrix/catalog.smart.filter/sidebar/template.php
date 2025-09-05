<?php

use Bitrix\Main\Application;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$request = Application::getInstance()->getContext()->getRequest();
$path = $request->getRequestedPageDirectory();
$getData = $request->getQueryList()->toArray();
?>
<aside class="aside" data-fls-dynamic=".main__inner, 1199.98, 2">
    <form action="<?= $arResult["FORM_ACTION"] ?>" method="get"
          data-fls-spollers="1199.98,max" class="aside-main__spoller spollers">
        <input type="hidden" name="set_filter" value="1">
        <details class="aside-main__spoller-item spollers__item">
            <summary class="aside-main__spoller-title spollers__title">Фильтр
            </summary>
            <div class="aside-main__spoller-body spollers__body">
                <div class="aside-main__articul">
                    <div class="aside-main__articul-title aside-title">Артикул
                    </div>
                    <input name="art_number" class="aside-main__articul-input" type="text" placeholder="Введите номер запчасти"
                        value="<?=$getData['art_number']?>"
                    >
                </div>
                <?php

                foreach ($arResult['ITEMS'] as $item) {
                    ?>
                    <div data-fls-spollers="360,min"
                         class="aside__spoller spollers">
                        <details class="aside__spoller-item spollers__item">
                            <summary
                                    class="aside__spoller-title aside-title spollers__title"><?= $item['NAME'] ?></summary>
                            <div class="aside__spoller-body spollers__body">
                                <?php

                                foreach ($item['VALUES'] as $val) {
                                    ?>
                                    <div class="aside__form-item form__item">
                                        <div class="aside__form-checkbox checkbox">
                                            <input class="aside__form-input checkbox__input"
                                                   id="<?= $val['CONTROL_ID'] ?>"
                                                   type="checkbox"
                                                   name="<?= $val['CONTROL_NAME'] ?>"
                                                   value="<?= $val['HTML_VALUE'] ?>"
                                                   <?php

                                                   if ($val['CHECKED']) {
                                                   ?>
                                                       checked
                                                   <?php

                                                   }
                                                   ?>
                                            >
                                            <label class="aside__form-label checkbox__label"
                                                   for="<?= $val['CONTROL_ID'] ?>">
															<span class="aside__form-span">
																<?= $val['VALUE'] ?>
<!--																<span class="aside__form-count">(253)</span>-->
															</span>
                                            </label>
                                        </div>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                        </details>
                    </div>
                    <?php
                }
                ?>
            </div>
            <div class="aside-main__controls">
                <a href="<?=$path?>" class="aside-main__btn aside-main__btn--reset">
                    Сбросить
                </a>
                <button class="aside-main__btn aside-main__btn--show">Показать
                </button>
            </div>
        </details>
    </form>
</aside>
