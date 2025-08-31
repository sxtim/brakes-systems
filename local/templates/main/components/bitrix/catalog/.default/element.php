<?php

use Bitrix\Main\Page\Asset;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

Asset::getInstance()->addString('<script type="module" crossorigin="" src="' . SITE_TEMPLATE_PATH . '/js/product-page.min.js"></script>');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/product-page.min.css');
?>
    <main class="page">
        <div class="page__container">
            <aside class="aside" data-fls-dynamic=".main__inner, 1199.98, 2">
                <div data-fls-spollers="1199.98,max" class="aside-main__spoller spollers">
                    <details class="aside-main__spoller-item spollers__item">
                        <summary class="aside-main__spoller-title spollers__title">Фильтр</summary>
                        <div class="aside-main__spoller-body spollers__body">
                            <div class="aside-main__articul">
                                <div class="aside-main__articul-title aside-title">Артикул</div>
                                <form class="aside-main__articul-form" action="#">
                                    <input class="aside-main__articul-input" type="text" placeholder="Введите номер запчасти">
                                </form>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Производитель</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-1">
															<span class="aside__form-span">
																Akebono
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-2">
															<span class="aside__form-span">
																Endless
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-3">
															<span class="aside__form-span">
																Dicase
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-4">
															<span class="aside__form-span">
																SB Aprcng
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-5">
															<span class="aside__form-span">
																SB Brmb
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="manufacturer-6" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="manufacturer-6">
															<span class="aside__form-span">
																Brembo
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Модель</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="model-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="model-1">
															<span class="aside__form-span">
																Model Brakes 1
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="model-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="model-2">
															<span class="aside__form-span">
																Model Brakes 2
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="model-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="model-3">
															<span class="aside__form-span">
																Model Brakes 3
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="model-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="model-4">
															<span class="aside__form-span">
																Model Brakes 4
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="model-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="model-5">
															<span class="aside__form-span">
																Model Brakes 5
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Диаметр тормозного ротора</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="diametr-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="diametr-1">
															<span class="aside__form-span">
																287 мм
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="diametr-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="diametr-2">
															<span class="aside__form-span">
																311 мм
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="diametr-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="diametr-3">
															<span class="aside__form-span">
																320мм
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="diametr-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="diametr-4">
															<span class="aside__form-span">
																400 мм
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Толщина тормозного ротора</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="thickness-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="thickness-1">
															<span class="aside__form-span">
																20 мм
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="thickness-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="thickness-2">
															<span class="aside__form-span">
																2 мм
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="thickness-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="thickness-3">
															<span class="aside__form-span">
																25 мм
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="thickness-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="thickness-4">
															<span class="aside__form-span">
																27 мм
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="thickness-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="thickness-5">
															<span class="aside__form-span">
																30 мм
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Количество поршней</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="quantity-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="quantity-1">
															<span class="aside__form-span">
																2
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="quantity-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="quantity-2">
															<span class="aside__form-span">
																4
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="quantity-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="quantity-3">
															<span class="aside__form-span">
																6
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="quantity-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="quantity-4">
															<span class="aside__form-span">
																8
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="quantity-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="quantity-5">
															<span class="aside__form-span">
																10
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Минимальный диаметр колесного диска</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-1">
															<span class="aside__form-span">
																15
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-2">
															<span class="aside__form-span">
																16
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-3">
															<span class="aside__form-span">
																17
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-4">
															<span class="aside__form-span">
																18
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-5">
															<span class="aside__form-span">
																19
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-6" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-6">
															<span class="aside__form-span">
																20
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-7" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-7">
															<span class="aside__form-span">
																21
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="min-8" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="min-8">
															<span class="aside__form-span">
																22
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Ось установки</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="arg-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="arg-1">
                                                    <span class="aside__form-span">Передняя ось</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="arg-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="arg-2">
                                                    <span class="aside__form-span">Задняя ось</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Тип конструкции суппорта</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="typ-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="typ-1">
															<span class="aside__form-span">
																Model Brakes
																<span class="aside__form-count">(253)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="typ-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="typ-2">
															<span class="aside__form-span">
																Model Brakes
																<span class="aside__form-count">(18)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="typ-3" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="typ-3">
															<span class="aside__form-span">
																Model Brakes
																<span class="aside__form-count">(51112)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="typ-4" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="typ-4">
															<span class="aside__form-span">
																Model Brakes
																<span class="aside__form-count">(54)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="typ-5" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="typ-5">
															<span class="aside__form-span">
																Model Brakes
																<span class="aside__form-count">(87)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Страна производства</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input checked class="aside__form-input checkbox__input" id="country-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="country-1">
                                                    <span class="aside__form-span">Китай</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Гарантия на суппорта</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input checked class="aside__form-input checkbox__input" id="garanty-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="garanty-1">
                                                    <span class="aside__form-span">6 месяцев</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Срок поставки на заказ</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input checked class="aside__form-input checkbox__input" id="order-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="order-1">
                                                    <span class="aside__form-span">35 рабочих дней</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                            <div data-fls-spollers="360,min" class="aside__spoller spollers">
                                <details class="aside__spoller-item spollers__item">
                                    <summary class="aside__spoller-title aside-title spollers__title">Наличие</summary>
                                    <div class="aside__spoller-body spollers__body">
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="availability-1" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="availability-1">
															<span class="aside__form-span">
																В наличии
																<span class="aside__form-count">(25)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="aside__form-item form__item">
                                            <div class="aside__form-checkbox checkbox">
                                                <input class="aside__form-input checkbox__input" id="availability-2" type="checkbox" name="agreement">
                                                <label class="aside__form-label checkbox__label" for="availability-2">
															<span class="aside__form-span">
																Под заказ
																<span class="aside__form-count">(25)</span>
															</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </details>
                            </div>
                        </div>
                        <div class="aside-main__controls">
                            <button class="aside-main__btn aside-main__btn--reset">Сбросить</button>
                            <button class="aside-main__btn aside-main__btn--show">Пока</button>
                        </div>
                    </details>
                </div>
            </aside>
            <div class="page__main">
                <div class="main__inner">
                    <?php $APPLICATION->IncludeComponent("bitrix:breadcrumb","",Array(
                            "START_FROM" => "0",
                            "PATH" => "",
                            "SITE_ID" => "s1"
                        )
                    );?>
                    <h1 class="main__title"><?=$APPLICATION->ShowTitle(false)?></h1>
                    <?php

                    $componentElementParams = array(
                        'IBLOCK_TYPE'                => $arParams['IBLOCK_TYPE'],
                        'IBLOCK_ID'                  => $arParams['IBLOCK_ID'],
                        'PROPERTY_CODE'              => (isset($arParams['DETAIL_PROPERTY_CODE'])
                            ? $arParams['DETAIL_PROPERTY_CODE'] : []),
                        'META_KEYWORDS'              => $arParams['DETAIL_META_KEYWORDS'],
                        'META_DESCRIPTION'           => $arParams['DETAIL_META_DESCRIPTION'],
                        'BROWSER_TITLE'              => $arParams['DETAIL_BROWSER_TITLE'],
                        'SET_CANONICAL_URL'          => $arParams['DETAIL_SET_CANONICAL_URL'],
                        'BASKET_URL'                 => $arParams['BASKET_URL'],
                        'SHOW_SKU_DESCRIPTION'       => $arParams['SHOW_SKU_DESCRIPTION'],
                        'ACTION_VARIABLE'            => $arParams['ACTION_VARIABLE'],
                        'PRODUCT_ID_VARIABLE'        => $arParams['PRODUCT_ID_VARIABLE'],
                        'SECTION_ID_VARIABLE'        => $arParams['SECTION_ID_VARIABLE'],
                        'CHECK_SECTION_ID_VARIABLE'  => (isset($arParams['DETAIL_CHECK_SECTION_ID_VARIABLE'])
                            ? $arParams['DETAIL_CHECK_SECTION_ID_VARIABLE'] : ''),
                        'PRODUCT_QUANTITY_VARIABLE'  => $arParams['PRODUCT_QUANTITY_VARIABLE'],
                        'PRODUCT_PROPS_VARIABLE'     => $arParams['PRODUCT_PROPS_VARIABLE'],
                        'CACHE_TYPE'                 => $arParams['CACHE_TYPE'],
                        'CACHE_TIME'                 => $arParams['CACHE_TIME'],
                        'CACHE_GROUPS'               => $arParams['CACHE_GROUPS'],
                        'SET_TITLE'                  => $arParams['SET_TITLE'],
                        'SET_LAST_MODIFIED'          => $arParams['SET_LAST_MODIFIED'],
                        'MESSAGE_404'                => $arParams['~MESSAGE_404'],
                        'SET_STATUS_404'             => $arParams['SET_STATUS_404'],
                        'SHOW_404'                   => $arParams['SHOW_404'],
                        'FILE_404'                   => $arParams['FILE_404'],
                        'PRICE_CODE'                 => $arParams['~PRICE_CODE'],
                        'USE_PRICE_COUNT'            => $arParams['USE_PRICE_COUNT'],
                        'SHOW_PRICE_COUNT'           => $arParams['SHOW_PRICE_COUNT'],
                        'PRICE_VAT_INCLUDE'          => $arParams['PRICE_VAT_INCLUDE'],
                        'PRICE_VAT_SHOW_VALUE'       => $arParams['PRICE_VAT_SHOW_VALUE'],
                        'USE_PRODUCT_QUANTITY'       => $arParams['USE_PRODUCT_QUANTITY'],
                        'PRODUCT_PROPERTIES'         => (isset($arParams['PRODUCT_PROPERTIES'])
                            ? $arParams['PRODUCT_PROPERTIES'] : []),
                        'ADD_PROPERTIES_TO_BASKET'   => (isset($arParams['ADD_PROPERTIES_TO_BASKET'])
                            ? $arParams['ADD_PROPERTIES_TO_BASKET'] : ''),
                        'PARTIAL_PRODUCT_PROPERTIES' => (isset($arParams['PARTIAL_PRODUCT_PROPERTIES'])
                            ? $arParams['PARTIAL_PRODUCT_PROPERTIES'] : ''),
                        'LINK_IBLOCK_TYPE'           => $arParams['LINK_IBLOCK_TYPE'],
                        'LINK_IBLOCK_ID'             => $arParams['LINK_IBLOCK_ID'],
                        'LINK_PROPERTY_SID'          => $arParams['LINK_PROPERTY_SID'],
                        'LINK_ELEMENTS_URL'          => $arParams['LINK_ELEMENTS_URL'],

                        'OFFERS_CART_PROPERTIES' => (isset($arParams['OFFERS_CART_PROPERTIES'])
                            ? $arParams['OFFERS_CART_PROPERTIES'] : []),
                        'OFFERS_FIELD_CODE'      => $arParams['DETAIL_OFFERS_FIELD_CODE'],
                        'OFFERS_PROPERTY_CODE'   => (isset($arParams['DETAIL_OFFERS_PROPERTY_CODE'])
                            ? $arParams['DETAIL_OFFERS_PROPERTY_CODE'] : []),
                        'OFFERS_SORT_FIELD'      => $arParams['OFFERS_SORT_FIELD'],
                        'OFFERS_SORT_ORDER'      => $arParams['OFFERS_SORT_ORDER'],
                        'OFFERS_SORT_FIELD2'     => $arParams['OFFERS_SORT_FIELD2'],
                        'OFFERS_SORT_ORDER2'     => $arParams['OFFERS_SORT_ORDER2'],

                        'ELEMENT_ID'                      => $arResult['VARIABLES']['ELEMENT_ID'],
                        'ELEMENT_CODE'                    => $arResult['VARIABLES']['ELEMENT_CODE'],
                        'SECTION_ID'                      => $arResult['VARIABLES']['SECTION_ID'],
                        'SECTION_CODE'                    => $arResult['VARIABLES']['SECTION_CODE'],
                        'SECTION_URL'                     => $arResult['FOLDER']
                            .$arResult['URL_TEMPLATES']['section'],
                        'DETAIL_URL'                      => $arResult['FOLDER']
                            .$arResult['URL_TEMPLATES']['element'],
                        'CONVERT_CURRENCY'                => $arParams['CONVERT_CURRENCY'],
                        'CURRENCY_ID'                     => $arParams['CURRENCY_ID'],
                        'HIDE_NOT_AVAILABLE'              => $arParams['HIDE_NOT_AVAILABLE'],
                        'HIDE_NOT_AVAILABLE_OFFERS'       => $arParams['HIDE_NOT_AVAILABLE_OFFERS'],
                        'USE_ELEMENT_COUNTER'             => $arParams['USE_ELEMENT_COUNTER'],
                        'SHOW_DEACTIVATED'                => $arParams['SHOW_DEACTIVATED'],
                        'USE_MAIN_ELEMENT_SECTION'        => $arParams['USE_MAIN_ELEMENT_SECTION'],
                        'STRICT_SECTION_CHECK'            => (isset($arParams['DETAIL_STRICT_SECTION_CHECK'])
                            ? $arParams['DETAIL_STRICT_SECTION_CHECK'] : ''),
                        'ADD_PICT_PROP'                   => $arParams['ADD_PICT_PROP'],
                        'LABEL_PROP'                      => $arParams['LABEL_PROP'],
                        'LABEL_PROP_MOBILE'               => $arParams['LABEL_PROP_MOBILE'],
                        'LABEL_PROP_POSITION'             => $arParams['LABEL_PROP_POSITION'],
                        'OFFER_ADD_PICT_PROP'             => $arParams['OFFER_ADD_PICT_PROP'],
                        'OFFER_TREE_PROPS'                => (isset($arParams['OFFER_TREE_PROPS'])
                            ? $arParams['OFFER_TREE_PROPS'] : []),
                        'PRODUCT_SUBSCRIPTION'            => $arParams['PRODUCT_SUBSCRIPTION'],
                        'SHOW_DISCOUNT_PERCENT'           => $arParams['SHOW_DISCOUNT_PERCENT'],
                        'DISCOUNT_PERCENT_POSITION'       => (isset($arParams['DISCOUNT_PERCENT_POSITION'])
                            ? $arParams['DISCOUNT_PERCENT_POSITION'] : ''),
                        'SHOW_OLD_PRICE'                  => $arParams['SHOW_OLD_PRICE'],
                        'SHOW_MAX_QUANTITY'               => $arParams['SHOW_MAX_QUANTITY'],
                        'MESS_SHOW_MAX_QUANTITY'          => (isset($arParams['~MESS_SHOW_MAX_QUANTITY'])
                            ? $arParams['~MESS_SHOW_MAX_QUANTITY'] : ''),
                        'RELATIVE_QUANTITY_FACTOR'        => (isset($arParams['RELATIVE_QUANTITY_FACTOR'])
                            ? $arParams['RELATIVE_QUANTITY_FACTOR'] : ''),
                        'MESS_RELATIVE_QUANTITY_MANY'     => (isset($arParams['~MESS_RELATIVE_QUANTITY_MANY'])
                            ? $arParams['~MESS_RELATIVE_QUANTITY_MANY'] : ''),
                        'MESS_RELATIVE_QUANTITY_FEW'      => (isset($arParams['~MESS_RELATIVE_QUANTITY_FEW'])
                            ? $arParams['~MESS_RELATIVE_QUANTITY_FEW'] : ''),
                        'MESS_BTN_BUY'                    => (isset($arParams['~MESS_BTN_BUY'])
                            ? $arParams['~MESS_BTN_BUY'] : ''),
                        'MESS_BTN_ADD_TO_BASKET'          => (isset($arParams['~MESS_BTN_ADD_TO_BASKET'])
                            ? $arParams['~MESS_BTN_ADD_TO_BASKET'] : ''),
                        'MESS_BTN_SUBSCRIBE'              => (isset($arParams['~MESS_BTN_SUBSCRIBE'])
                            ? $arParams['~MESS_BTN_SUBSCRIBE'] : ''),
                        'MESS_BTN_DETAIL'                 => (isset($arParams['~MESS_BTN_DETAIL'])
                            ? $arParams['~MESS_BTN_DETAIL'] : ''),
                        'MESS_NOT_AVAILABLE'              => $arParams['~MESS_NOT_AVAILABLE'] ?? '',
                        'MESS_NOT_AVAILABLE_SERVICE'      => $arParams['~MESS_NOT_AVAILABLE_SERVICE']
                            ?? '',
                        'MESS_BTN_COMPARE'                => (isset($arParams['~MESS_BTN_COMPARE'])
                            ? $arParams['~MESS_BTN_COMPARE'] : ''),
                        'MESS_PRICE_RANGES_TITLE'         => (isset($arParams['~MESS_PRICE_RANGES_TITLE'])
                            ? $arParams['~MESS_PRICE_RANGES_TITLE'] : ''),
                        'MESS_DESCRIPTION_TAB'            => (isset($arParams['~MESS_DESCRIPTION_TAB'])
                            ? $arParams['~MESS_DESCRIPTION_TAB'] : ''),
                        'MESS_PROPERTIES_TAB'             => (isset($arParams['~MESS_PROPERTIES_TAB'])
                            ? $arParams['~MESS_PROPERTIES_TAB'] : ''),
                        'MESS_COMMENTS_TAB'               => (isset($arParams['~MESS_COMMENTS_TAB'])
                            ? $arParams['~MESS_COMMENTS_TAB'] : ''),
                        'MAIN_BLOCK_PROPERTY_CODE'        => (isset($arParams['DETAIL_MAIN_BLOCK_PROPERTY_CODE'])
                            ? $arParams['DETAIL_MAIN_BLOCK_PROPERTY_CODE'] : ''),
                        'MAIN_BLOCK_OFFERS_PROPERTY_CODE' => (isset($arParams['DETAIL_MAIN_BLOCK_OFFERS_PROPERTY_CODE'])
                            ? $arParams['DETAIL_MAIN_BLOCK_OFFERS_PROPERTY_CODE'] : ''),
                        'USE_VOTE_RATING'                 => $arParams['DETAIL_USE_VOTE_RATING'],
                        'VOTE_DISPLAY_AS_RATING'          => (isset($arParams['DETAIL_VOTE_DISPLAY_AS_RATING'])
                            ? $arParams['DETAIL_VOTE_DISPLAY_AS_RATING'] : ''),
                        'USE_COMMENTS'                    => $arParams['DETAIL_USE_COMMENTS'],
                        'BLOG_USE'                        => (isset($arParams['DETAIL_BLOG_USE'])
                            ? $arParams['DETAIL_BLOG_USE'] : ''),
                        'BLOG_URL'                        => (isset($arParams['DETAIL_BLOG_URL'])
                            ? $arParams['DETAIL_BLOG_URL'] : ''),
                        'BLOG_EMAIL_NOTIFY'               => (isset($arParams['DETAIL_BLOG_EMAIL_NOTIFY'])
                            ? $arParams['DETAIL_BLOG_EMAIL_NOTIFY'] : ''),
                        'VK_USE'                          => (isset($arParams['DETAIL_VK_USE'])
                            ? $arParams['DETAIL_VK_USE'] : ''),
                        'VK_API_ID'                       => (isset($arParams['DETAIL_VK_API_ID'])
                            ? $arParams['DETAIL_VK_API_ID'] : 'API_ID'),
                        'FB_USE'                          => (isset($arParams['DETAIL_FB_USE'])
                            ? $arParams['DETAIL_FB_USE'] : ''),
                        'FB_APP_ID'                       => (isset($arParams['DETAIL_FB_APP_ID'])
                            ? $arParams['DETAIL_FB_APP_ID'] : ''),
                        'BRAND_USE'                       => (isset($arParams['DETAIL_BRAND_USE'])
                            ? $arParams['DETAIL_BRAND_USE'] : 'N'),
                        'BRAND_PROP_CODE'                 => (isset($arParams['DETAIL_BRAND_PROP_CODE'])
                            ? $arParams['DETAIL_BRAND_PROP_CODE'] : ''),
                        'DISPLAY_NAME'                    => (isset($arParams['DETAIL_DISPLAY_NAME'])
                            ? $arParams['DETAIL_DISPLAY_NAME'] : ''),
                        'IMAGE_RESOLUTION'                => (isset($arParams['DETAIL_IMAGE_RESOLUTION'])
                            ? $arParams['DETAIL_IMAGE_RESOLUTION'] : ''),
                        'PRODUCT_INFO_BLOCK_ORDER'        => (isset($arParams['DETAIL_PRODUCT_INFO_BLOCK_ORDER'])
                            ? $arParams['DETAIL_PRODUCT_INFO_BLOCK_ORDER'] : ''),
                        'PRODUCT_PAY_BLOCK_ORDER'         => (isset($arParams['DETAIL_PRODUCT_PAY_BLOCK_ORDER'])
                            ? $arParams['DETAIL_PRODUCT_PAY_BLOCK_ORDER'] : ''),
                        'ADD_DETAIL_TO_SLIDER'            => (isset($arParams['DETAIL_ADD_DETAIL_TO_SLIDER'])
                            ? $arParams['DETAIL_ADD_DETAIL_TO_SLIDER'] : ''),
                        'TEMPLATE_THEME'                  => (isset($arParams['TEMPLATE_THEME'])
                            ? $arParams['TEMPLATE_THEME'] : ''),
                        'ADD_SECTIONS_CHAIN'              => (isset($arParams['ADD_SECTIONS_CHAIN'])
                            ? $arParams['ADD_SECTIONS_CHAIN'] : ''),
                        'ADD_ELEMENT_CHAIN'               => (isset($arParams['ADD_ELEMENT_CHAIN'])
                            ? $arParams['ADD_ELEMENT_CHAIN'] : ''),
                        'DISPLAY_PREVIEW_TEXT_MODE'       => (isset($arParams['DETAIL_DISPLAY_PREVIEW_TEXT_MODE'])
                            ? $arParams['DETAIL_DISPLAY_PREVIEW_TEXT_MODE'] : ''),
                        'DETAIL_PICTURE_MODE'             => (isset($arParams['DETAIL_DETAIL_PICTURE_MODE'])
                            ? $arParams['DETAIL_DETAIL_PICTURE_MODE'] : array()),
                        'ADD_TO_BASKET_ACTION'            => $basketAction,
                        'ADD_TO_BASKET_ACTION_PRIMARY'    => (isset($arParams['DETAIL_ADD_TO_BASKET_ACTION_PRIMARY'])
                            ? $arParams['DETAIL_ADD_TO_BASKET_ACTION_PRIMARY'] : null),
                        'SHOW_CLOSE_POPUP'                => isset($arParams['COMMON_SHOW_CLOSE_POPUP'])
                            ? $arParams['COMMON_SHOW_CLOSE_POPUP'] : '',
                        'DISPLAY_COMPARE'                 => (isset($arParams['USE_COMPARE'])
                            ? $arParams['USE_COMPARE'] : ''),
                        'COMPARE_PATH'                    => $arResult['FOLDER']
                            .$arResult['URL_TEMPLATES']['compare'],
                        'USE_COMPARE_LIST'                => 'Y',
                        'BACKGROUND_IMAGE'                => (isset($arParams['DETAIL_BACKGROUND_IMAGE'])
                            ? $arParams['DETAIL_BACKGROUND_IMAGE'] : ''),
                        'COMPATIBLE_MODE'                 => (isset($arParams['COMPATIBLE_MODE'])
                            ? $arParams['COMPATIBLE_MODE'] : ''),
                        'DISABLE_INIT_JS_IN_COMPONENT'    => (isset($arParams['DISABLE_INIT_JS_IN_COMPONENT'])
                            ? $arParams['DISABLE_INIT_JS_IN_COMPONENT'] : ''),
                        'SET_VIEWED_IN_COMPONENT'         => (isset($arParams['DETAIL_SET_VIEWED_IN_COMPONENT'])
                            ? $arParams['DETAIL_SET_VIEWED_IN_COMPONENT'] : ''),
                        'SHOW_SLIDER'                     => (isset($arParams['DETAIL_SHOW_SLIDER'])
                            ? $arParams['DETAIL_SHOW_SLIDER'] : ''),
                        'SLIDER_INTERVAL'                 => (isset($arParams['DETAIL_SLIDER_INTERVAL'])
                            ? $arParams['DETAIL_SLIDER_INTERVAL'] : ''),
                        'SLIDER_PROGRESS'                 => (isset($arParams['DETAIL_SLIDER_PROGRESS'])
                            ? $arParams['DETAIL_SLIDER_PROGRESS'] : ''),
                        'USE_ENHANCED_ECOMMERCE'          => (isset($arParams['USE_ENHANCED_ECOMMERCE'])
                            ? $arParams['USE_ENHANCED_ECOMMERCE'] : ''),
                        'DATA_LAYER_NAME'                 => (isset($arParams['DATA_LAYER_NAME'])
                            ? $arParams['DATA_LAYER_NAME'] : ''),
                        'BRAND_PROPERTY'                  => (isset($arParams['BRAND_PROPERTY'])
                            ? $arParams['BRAND_PROPERTY'] : ''),

                        'USE_GIFTS_DETAIL'                => $arParams['USE_GIFTS_DETAIL'] ?: 'Y',
                        'USE_GIFTS_MAIN_PR_SECTION_LIST'  => $arParams['USE_GIFTS_MAIN_PR_SECTION_LIST']
                            ?: 'Y',
                        'GIFTS_SHOW_DISCOUNT_PERCENT'     => $arParams['GIFTS_SHOW_DISCOUNT_PERCENT'],
                        'GIFTS_SHOW_OLD_PRICE'            => $arParams['GIFTS_SHOW_OLD_PRICE'],
                        'GIFTS_DETAIL_PAGE_ELEMENT_COUNT' => $arParams['GIFTS_DETAIL_PAGE_ELEMENT_COUNT'],
                        'GIFTS_DETAIL_HIDE_BLOCK_TITLE'   => $arParams['GIFTS_DETAIL_HIDE_BLOCK_TITLE'],
                        'GIFTS_DETAIL_TEXT_LABEL_GIFT'    => $arParams['GIFTS_DETAIL_TEXT_LABEL_GIFT'],
                        'GIFTS_DETAIL_BLOCK_TITLE'        => $arParams['GIFTS_DETAIL_BLOCK_TITLE'],
                        'GIFTS_SHOW_NAME'                 => $arParams['GIFTS_SHOW_NAME'],
                        'GIFTS_SHOW_IMAGE'                => $arParams['GIFTS_SHOW_IMAGE'],
                        'GIFTS_MESS_BTN_BUY'              => $arParams['~GIFTS_MESS_BTN_BUY'],
                        'GIFTS_PRODUCT_BLOCKS_ORDER'      => $arParams['LIST_PRODUCT_BLOCKS_ORDER'],
                        'GIFTS_SHOW_SLIDER'               => $arParams['LIST_SHOW_SLIDER'],
                        'GIFTS_SLIDER_INTERVAL'           => isset($arParams['LIST_SLIDER_INTERVAL'])
                            ? $arParams['LIST_SLIDER_INTERVAL'] : '',
                        'GIFTS_SLIDER_PROGRESS'           => isset($arParams['LIST_SLIDER_PROGRESS'])
                            ? $arParams['LIST_SLIDER_PROGRESS'] : '',

                        'GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT' => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_PAGE_ELEMENT_COUNT'],
                        'GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE'        => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_BLOCK_TITLE'],
                        'GIFTS_MAIN_PRODUCT_DETAIL_HIDE_BLOCK_TITLE'   => $arParams['GIFTS_MAIN_PRODUCT_DETAIL_HIDE_BLOCK_TITLE'],
                    );

                    if (isset($arParams['USER_CONSENT'])) {
                        $componentElementParams['USER_CONSENT'] = $arParams['USER_CONSENT'];
                    }

                    if (isset($arParams['USER_CONSENT_ID'])) {
                        $componentElementParams['USER_CONSENT_ID'] = $arParams['USER_CONSENT_ID'];
                    }

                    if (isset($arParams['USER_CONSENT_IS_CHECKED'])) {
                        $componentElementParams['USER_CONSENT_IS_CHECKED']
                            = $arParams['USER_CONSENT_IS_CHECKED'];
                    }

                    if (isset($arParams['USER_CONSENT_IS_LOADED'])) {
                        $componentElementParams['USER_CONSENT_IS_LOADED']
                            = $arParams['USER_CONSENT_IS_LOADED'];
                    }

                    $APPLICATION->IncludeComponent(
                        'bitrix:catalog.element',
                        '',
                        $componentElementParams,
                        $component
                    );
                    ?>
                </div>
            </div>
        </div>
        <div class="slider__container">
            <div class="products__block slider-block">
                <h2 class="products__title">Рекомендуемые товары</h2>
                <div class="products__body">
                    <button class="products-slider__prev"></button>
                    <div data-fls-slider="" class="products-slider__slider swiper">
                        <div class="products-slider__wrapper swiper-wrapper">
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <button class="products-slider__next"></button>
                </div>
            </div>
            <div class="watched__block slider-block">
                <h2 class="products__title">Вы смотрели</h2>
                <div class="products__body">
                    <button class="watched-slider__prev"></button>
                    <div data-fls-slider="" class="watched-slider__slider swiper">
                        <div class="products-slider__wrapper swiper-wrapper">
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/1.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Колодки тормозные DICASE для усиленных тормозных систем</h3>
                                    </div>
                                </a>
                            </div>
                            <div class="products-slider__slide swiper-slide">
                                <a class="products-slider__card" href="#">
                                    <div class="products-slider__picture">
                                        <picture>
                                            <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-600.webp" type="image/webp">
                                            <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2-1200.webp" type="image/webp">
                                            <img class="products-slider__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/products-slider/2.webp">
                                        </picture>
                                    </div>
                                    <div class="products-slider__descr">
                                        <h3 class="products-slider__title">Усиленная тормозная система DICASE DR73</h3>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                    <button class="watched-slider__next"></button>
                </div>
            </div>
        </div>
    </main>