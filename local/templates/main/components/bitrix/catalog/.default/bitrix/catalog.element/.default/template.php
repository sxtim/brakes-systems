<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div class="main__overlay">
    <div class="main__content">
        <div class="main__media">
            <div data-fls-slider="" class="swiper main-swiper">
                <div class="swiper-wrapper main-swiper__wrapper gallery" data-fls-gallery="">
                    <div class="swiper-slide main-swiper__slide">
                        <a class="main-swiper__gallery__image gallery__image" href="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="main-swiper__img gallery__preview" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </a>
                    </div>
                    <div class="swiper-slide main-swiper__slide">
                        <a class="main-swiper__gallery__image gallery__image" href="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="main-swiper__img gallery__preview" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </a>
                    </div>
                    <div class="swiper-slide main-swiper__slide">
                        <a class="main-swiper__gallery__image gallery__image" href="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="main-swiper__img gallery__preview" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </a>
                    </div>
                    <div class="swiper-slide main-swiper__slide">
                        <a class="main-swiper__gallery__image gallery__image" href="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="main-swiper__img gallery__preview" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </a>
                    </div>
                </div>
            </div>
            <!-- Слайдер мініатюр -->
            <div class="thumbs-swiper__overlay">
                <button class="thumbs-swiper__prev"></button>
                <div data-fls-slider="" class="swiper thumbs-swiper">
                    <div class="thumbs-swiper__wrapper swiper-wrapper">
                        <div class="thumbs-swiper__slide swiper-slide">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="thumbs-swiper__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </div>
                        <div class="thumbs-swiper__slide swiper-slide">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2-1200.webp" type="image/webp">
                                <img class="thumbs-swiper__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2.webp">
                            </picture>
                        </div>
                        <div class="thumbs-swiper__slide swiper-slide">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1-1200.webp" type="image/webp">
                                <img class="thumbs-swiper__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-1.webp">
                            </picture>
                        </div>
                        <div class="thumbs-swiper__slide swiper-slide">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2-1200.webp" type="image/webp">
                                <img class="thumbs-swiper__img" alt="Img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-slider/slide-2.webp">
                            </picture>
                        </div>
                    </div>
                </div>
                <button class="thumbs-swiper__next"></button>
            </div>
        </div>
        <div class="main__details main-details" data-fls-dynamic=".main__overlay, 1199.98">
            <div data-fls-dynamic=".main__media, 1199.98, 0" class="main-details__status">
                <div class="main-details__status-item status-item--1 active">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-1.svg" alt="Image">
                    <span class="main-details__status-text">В наличии</span>
                </div>
                <div class="main-details__status-item status-item--2">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-2.svg" alt="Image">
                    <span class="main-details__status-text">Нет в наличии</span>
                </div>
                <div class="main-details__status-item status-item--3">
                    <img class="main-details__status-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/status-3.svg" alt="Image">
                    <span class="main-details__status-text">Под заказ</span>
                </div>
            </div>
            <div class="main-details__price">
                <div class="main-details__price-top">
                    <span class="main-details__price-action">-25%</span>
                    <span class="main-details__price-old">170 000 ₽%</span>
                </div>
                <span class="main-details__price-new">150 000 ₽</span>
            </div>
            <div class="main-details__feature">
                <div class="main-cataloge__info">
                    <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
                        <details class="spollers__item">
                            <summary class="main-details__feature-item main-cataloge__feature-item--big spollers__title">Двусоставная конструкция диска:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Да</div>
                                <div class="main-cataloge__sublist-item">Нкт</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Рисунок ротора:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ</div>
                                <div class="main-cataloge__sublist-item">НАСЕЧКИ</div>
                                <div class="main-cataloge__sublist-item">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Лого на суппорт:</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Стандартный</div>
                                <div class="main-cataloge__sublist-item">Особый логотип</div>
                            </div>
                        </details>
                        <details class="spollers__item">
                            <summary class="main-details__feature-item spollers__title">Электроручник</summary>
                            <div class="main-cataloge__sublist spollers__body">
                                <div class="main-cataloge__sublist-item">Пункт 1</div>
                                <div class="main-cataloge__sublist-item">Пункт 2</div>
                                <div class="main-cataloge__sublist-item">Пункт 3</div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
            <div class="main-details__shoping">
                <button data-fls-addtocart-button="" class="main-details__shoping-btn">
                    <span class="main-details__shoping-text">В корзину</span>
                </button>
                <button data-fls-like-image="" data-fls-like-button="" class="main-details__shoping-like"></button>
            </div>
            <button data-fls-popup-link="speedBuy" class="main-details__buy" href="#">Купить в один клик</button>
        </div>
    </div>
</div>
<div class="main__services">
    <div class="main__services-item main__services-item--1">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-1.svg" alt="Image">
        <span class="main__services-text">Доставка Яндекс</span>
    </div>
    <div class="main__services-item main__services-item--2">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-2.svg" alt="Image">
        <span class="main__services-text">Доставка CDEK</span>
    </div>
    <div class="main__services-item main__services-item--3 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-3.svg" alt="Image">
        <span class="main__services-text">Самовывоз</span>
    </div>
    <div class="main__services-item main__services-item--4 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-4.svg" alt="Image">
        <span class="main__services-text">Гарантия</span>
    </div>
    <div class="main__services-item main__services-item--5 inactive">
        <img class="main__services-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/main-services/icon-5.svg" alt="Image">
        <span class="main__services-text">Рассрочка</span>
    </div>
</div>
<div class="main__descr">
    <p class="main__descr-text">
        Высокоэффективная тормозная система
        <b>DICASE DR73</b>
        - основа безопасности для любого автомобиля. Тюнингованная тормозная система ICOOH BMW X6 II (F16) (2014-2019), X42 обеспечит безопасность водителя как в городском режиме, так и на гоночном треке. Наши тормозные системы разработаны с применением высококачественных материалов и технологий. Применение таких систем значительно повышает информативность тормозной педали, уменьшает усилие нажатия, убирает эффект пропавших тормозов при торможении с больших скоростей и в целом визуально автомобиль с большими тормозами выглядит очень эффектно.
    </p>
    <div class="main__descr-list">
        <h3 class="main__descr-title">Преимущества:</h3>
        <ul>
            <li>
                Эсклюзивный дистрибьютор на территории РФ (подтверждено заключенным договором с владельцем производства).
            </li>
            <li>
                Тюнинг тормоза сертифицированы на территории нашей страны и соответствует всем необходимым требованиям.
            </li>
            <li>Международные сертификаты ISO, ECE, DOT (см фото)</li>
            <li>
                Продаются в США, Австралии, Филиппинах, что подтверждает высокое качество товара.
            </li>
            <li>Заводское качество, передовые технологии</li>
            <li>Лучшие прочностные характеристики суппортов на рынке РФ</li>
        </ul>
    </div>
    <div class="main__descr-list">
        <h4 class="main__descr-subtitle">
            В состав тормозной системы
            <b>DICASE DR73</b>
            входят::
        </h4>
        <ul>
            <li>
                Суппорта
                <b>DICASE DR73</b>
                4 поршня для BMW X6 II (F16) (2014-2019) - 2 шт
            </li>
            <li>Прочные переходные кронштейны (скобы) с гальваническим покрытием - 2 шт</li>
            <li>Кованые болты крепления суппорта к скобе (класс прочности 12.9) - 4 шт</li>
            <li>
                Передние составные тормозные диски (роторы) перфорация/насечки с принудительной вентиляцией - 2 шт
            </li>
            <li>Центра тормозных роторов алюминиевые (ступичная часть) - 2 шт</li>
            <li>Крепеж центра к ротору (плавающий или глухой) - 2 комплекта</li>
            <li>Тормозные колодки (керамика) - 2 комплекта</li>
            <li>Армированные тормозные шланги - 2 комплекта</li>
            <li>Цвет суппортов - Желтый</li>
        </ul>
    </div>
    <p class="main__descr-text">
        Что Вы получите после установки тюнинг тормозной системы
        <b>DICASE DR73</b>
        : - Высокая эффективность торможения (увеличение тормозного усилия). Тормоза разработаны с увеличенным в несколько раз потенциалом по сравнению со штатной тормозной системой - Высокая стабильность системы благодаря повышенному теплоотводу, за счет направленной вентиляции, газоотводным каналам перфорации и насечек. - Быстрый монтаж. Особенно при установке в нашем сервисе - Уменьшение неподрессорных масс за счет сниженного веса каждого комплекта - Применимость под большинство размеров колесных дисков (до 22 дюймов)
    </p>
</div>
<div class="main__details details">
    <div class="details-row">
        <span class="details-label">Вид товара:</span>
        <span class="details-dots"></span>
        <span class="details-value">Тормозная система</span>
    </div>
    <div class="details-row">
        <span class="details-label">Марка:</span>
        <span class="details-dots"></span>
        <span class="details-value"><?=$arResult['PROPERTIES']['MARK']['VALUE']?></span>
    </div>
    <div class="details-row">
        <span class="details-label">Модель:</span>
        <span class="details-dots"></span>
        <span class="details-value"><?=$arResult['PROPERTIES']['MODEL']['VALUE']?></span>
    </div>
    <div class="details-row">
        <span class="details-label">Поколение</span>
        <span class="details-dots"></span>
        <span class="details-value">II (F16) (2014-2019)</span>
    </div>
    <div class="details-row">
        <span class="details-label">Армированные шланги:</span>
        <span class="details-dots"></span>
        <span class="details-value">1 пара</span>
    </div>
    <div class="details-row">
        <span class="details-label">Артикул:</span>
        <span class="details-dots"></span>
        <span class="details-value"><?=$arResult['PROPERTIES']['CML2_ARTICLE']['VALUE']?></span>
    </div>
    <div class="details-row">
        <span class="details-label">Внутренние и внешние пыльники:</span>
        <span class="details-dots"></span>
        <span class="details-value">В Комплекте</span>
    </div>
    <div class="details-row">
        <span class="details-label">Двусоставная конструкция диска:</span>
        <span class="details-dots"></span>
        <span class="details-value">Да</span>
    </div>
    <div class="details-row">
        <span class="details-label">Длина поршня:</span>
        <span class="details-dots"></span>
        <span class="details-value">до 41.3 мм</span>
    </div>
    <div class="details-row">
        <span class="details-label">Доставка до офиса:</span>
        <span class="details-dots"></span>
        <span class="details-value">Бесплатно</span>
    </div>
    <div class="details-row">
        <span class="details-label">Кол-во поршней:</span>
        <span class="details-dots"></span>
        <span class="details-value"><?=$arResult['PROPERTIES']['NUMBER_PISTONS']['VALUE']?></span>
    </div>
    <div class="details-row">
        <span class="details-label">Колодки:</span>
        <span class="details-dots"></span>
        <span class="details-value">4 шт</span>
    </div>
    <div class="details-row">
        <span class="details-label">Материал суппорта:</span>
        <span class="details-dots"></span>
        <span class="details-value">Авиационный алюминий Al6061</span>
    </div>
    <div class="details-row">
        <span class="details-label">Монтажный комплект:</span>
        <span class="details-dots"></span>
        <span class="details-value">В Комплекте</span>
    </div>
    <div class="details-row">
        <span class="details-label">Площадь соприкосновения:</span>
        <span class="details-dots"></span>
        <span class="details-value">49.56 кв см</span>
    </div>
    <div class="details-row">
        <span class="details-label">Применяемый диаметр колес:</span>
        <span class="details-dots"></span>
        <span class="details-value">от 18</span>
    </div>
    <div class="details-row">
        <span class="details-label">Производитель:</span>
        <span class="details-dots"></span>
        <span class="details-value">ICOOH</span>
    </div>
    <div class="details-row">
        <span class="details-label">Процесс изготовления суппорта:</span>
        <span class="details-dots"></span>
        <span class="details-value">Авиационный алюминий Al6061</span>
    </div>
    <div class="details-row">
        <span class="details-label">Монтажный комплект:</span>
        <span class="details-dots"></span>
        <span class="details-value">В Комплекте</span>
    </div>
    <div class="details-row">
        <span class="details-label">Площадь соприкосновения:</span>
        <span class="details-dots"></span>
        <span class="details-value">49.56 кв см</span>
    </div>
    <div class="details-row">
        <span class="details-label">Применяемый диаметр колес:</span>
        <span class="details-dots"></span>
        <span class="details-value">от 18</span>
    </div>
    <div class="details-row">
        <span class="details-label">Производитель:</span>
        <span class="details-dots"></span>
        <span class="details-value">ICOOH</span>
    </div>
    <div class="details-row">
        <span class="details-label">Процесс изготовления суппорта:</span>
        <span class="details-dots"></span>
        <span class="details-value">В Комплекте</span>
    </div>
    <div class="details-row">
        <span class="details-label">Разборные торм диски (роторы):</span>
        <span class="details-dots"></span>
        <span class="details-value">1 пара</span>
    </div>
    <div class="details-row">
        <span class="details-label">Размер ротора:</span>
        <span class="details-dots"></span>
        <span class="details-value">до 400 мм</span>
    </div>
    <div class="details-row">
        <span class="details-label">Скобы:</span>
        <span class="details-dots"></span>
        <span class="details-value">1 пара</span>
    </div>
    <div class="details-row">
        <span class="details-label">Страна производства:</span>
        <span class="details-dots"></span>
        <span class="details-value">Китай</span>
    </div>
    <div class="details-row">
        <span class="details-label">Суппорта:</span>
        <span class="details-dots"></span>
        <span class="details-value">1 пара</span>
    </div>
    <div class="details-row">
        <span class="details-label">Цвет:</span>
        <span class="details-dots"></span>
        <span class="details-value">Желтый</span>
    </div>
    <div class="details-row">
        <span class="details-label">Вид техники:</span>
        <span class="details-dots"></span>
        <span class="details-value">Легковые автомобили</span>
    </div>
    <div class="details-row">
        <span class="details-label">Ось установки:</span>
        <span class="details-dots"></span>
        <span class="details-value">Задняя</span>
    </div>
    <div class="details-row">
        <span class="details-label">Разболтовка:</span>
        <span class="details-dots"></span>
        <span class="details-value">Уточняйте</span>
    </div>
    <div class="details-row">
        <span class="details-label">Гарантия на суппорта:</span>
        <span class="details-dots"></span>
        <span class="details-value">6 мес</span>
    </div>
    <div class="details-row">
        <span class="details-label">Срок поставки на заказ:</span>
        <span class="details-dots"></span>
        <span class="details-value">30 раб дней</span>
    </div>
</div>
