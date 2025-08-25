<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Каталог');
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
                    <div class="main__breadcrumbs">
                        <a class="main__breadcrumbs-item" href="#">Главная</a>
                        <a class="main__breadcrumbs-item active" href="#">Каталог</a>
                    </div>
                    <h1 class="main__title">Тюнингованные (усиленные) многопоршневые спортивные тормозные системы</h1>
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
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/1--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">BMW</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/2--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">Mercedes</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/3--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">AUDI</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/4--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">PORSCHE</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/5--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">
																		LAND
																		<br>
																		ROVER
																	</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/6--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">INFINITI</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/7--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">TOYOTA</span>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-1 swiper-slide">
                                                        <div class="selector-item">
                                                            <div class="selector-icon">
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8-1200.webp" type="image/webp">
                                                                    <img class="icon-inactive" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8.webp">
                                                                </picture>
                                                                <picture>
                                                                    <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8--active-600.webp" type="image/webp">
                                                                    <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8--active-1200.webp" type="image/webp">
                                                                    <img class="icon-active" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/choose-auto/8--active.webp">
                                                                </picture>
                                                            </div>
                                                            <span class="selector-name">LEXUS</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="selector-row__scrollbar-1 swiper-scrollbar"></div>
                                            </div>
                                            <button class="selector-row__prev-1 selector-row__btn"></button>
                                            <button class="selector-row__next-1 selector-row__btn"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="choose-auto__spollers-block">
                                    <h3 class="choose-auto__block-title">Модель</h3>
                                    <div class="choose-auto__block-row">
                                        <div class="selector-row">
                                            <div data-fls-slider="" class="selector-row__slider-2 swiper">
                                                <div class="selector-row__wrapper swiper-wrapper">
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">1-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">2-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">3-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">4-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">5-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X2</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X3</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">1-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">2-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">3-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">4-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">5-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">1-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">2-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">3-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">4-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">5-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X2</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">X3</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">1-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">2-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">3-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">4-я серия</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide-2 swiper-slide">
                                                        <div class="selector-item-2">
                                                            <div class="selector-icon">5-я серия</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="selector-row__scrollbar-2 swiper-scrollbar"></div>
                                            </div>
                                            <button class="selector-row__prev-2 selector-row__btn"></button>
                                            <button class="selector-row__next-2 selector-row__btn"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="choose-auto__spollers-block">
                                    <h3 class="choose-auto__block-title">Поколение</h3>
                                    <div class="choose-auto__block-row">
                                        <div class="selector-row">
                                            <div data-fls-slider="" class="selector-row__slider swiper">
                                                <div class="selector-row__wrapper swiper-wrapper">
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                    <div class="selector-row__slide swiper-slide">
                                                        <div class="selector-item-3">
                                                            <div class="selector-icon">Gen.1</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="selector-row__scrollbar swiper-scrollbar"></div>
                                            </div>
                                            <button class="selector-row__prev selector-row__btn"></button>
                                            <button class="selector-row__next selector-row__btn"></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </details>
                    </div>
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
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
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
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="main-cataloge__item">
                                <a class="main-cataloge__picture" href="product-page.html">
                                    <picture>
                                        <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-600.webp" type="image/webp">
                                        <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1-1200.webp" type="image/webp">
                                        <img class="main-cataloge__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/cataloge/1.webp">
                                    </picture>
                                </a>
                                <div class="main-cataloge__item-content">
                                    <div class="main-cataloge__item-top">
                                        <h3 class="main-cataloge__item-title">DICASE DR73</h3>
                                        <button data-fls-like-image="" data-fls-like-button="" class="main-cataloge__like main-details__shoping-like"></button>
                                    </div>
                                    <div class="main-cataloge__details main__details details">
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Применимость</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">задняя ось</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ротор:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">4</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Кол-во поршней:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">до 330 мм</span>
                                        </div>
                                        <div class="main-cataloge__details-row details-row">
                                            <span class="main-cataloge__details-label details-label">Ось:</span>
                                            <span class="main-cataloge__details-dots details-dots"></span>
                                            <span class="main-cataloge__details-value details-value">BMW 3 Серия E46 (1998-2007)</span>
                                        </div>
                                    </div>
                                    <div class="main-cataloge__colors">
                                        <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                                        <div class="main-cataloge__colors-box">
                                            <button class="main-cataloge__colors-item cataloge__color--red"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--black"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--yellow"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--white"></button>
                                            <button class="main-cataloge__colors-item cataloge__color--blue"></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="main-cataloge__info">
                                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item main-cataloge__feature-item--big">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Рисунок ротора:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Лого на суппорт:</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                                            </div>
                                        </details>
                                        <details class="spollers__item">
                                            <summary class="main-cataloge__feature-item main-cataloge__feature-item spollers__title">Электроручник</summary>
                                            <div class="main-cataloge__sublist spollers__body">
                                                <div class="main-cataloge__sublist-item">Да</div>
                                                <div class="main-cataloge__sublist-item">Нет</div>
                                            </div>
                                        </details>
                                    </div>
                                    <div class="main-cataloge__price">118 700 ₽</div>
                                    <div class="main-cataloge__bottom-controls">
                                        <button data-fls-popup-link="speedBuy" class="main-cataloge__buy">Купить в один клик</button>
                                        <button data-fls-addtocart-button="" class="main-cataloge__shoping-btn">
                                            <span class="main-cataloge__shoping-text">В корзину</span>
                                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="breadcrumbs-nav">
                        <a class="breadcrumbs-nav__link active" href="#">1</a>
                        <a class="breadcrumbs-nav__link" href="#">2</a>
                        <a class="breadcrumbs-nav__link" href="#">3</a>
                        <a class="breadcrumbs-nav__link" href="#">4</a>
                        <a class="breadcrumbs-nav__link breadcrumbs-nav__link--not" href="#">5</a>
                        <a class="breadcrumbs-nav__link breadcrumbs-nav__link--not" href="#">6</a>
                        <a class="breadcrumbs-nav__link breadcrumbs-nav__link--not" href="#">7</a>
                        <a class="breadcrumbs-nav__link breadcrumbs-nav__link--not" href="#">8</a>
                        <a class="breadcrumbs-nav__link breadcrumbs-nav__link--not" href="#">9</a>
                        <a class="breadcrumbs-nav__link" href="#">&gt;</a>
                        <a class="breadcrumbs-nav__link" href="#">&gt;|</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="slider__container">
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
<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');
?>