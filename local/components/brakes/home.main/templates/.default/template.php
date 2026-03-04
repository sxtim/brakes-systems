<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$this->addExternalCss($templateFolder . '/style.css');
$this->addExternalCss('https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css');
$this->addExternalJs('https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js');
$this->addExternalCss('https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css');
$this->addExternalJs('https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js');
$this->addExternalJs($templateFolder . '/script.js');

$settings = is_array($arResult['SETTINGS'] ?? null) ? $arResult['SETTINGS'] : [];
$products = is_array($arResult['PRODUCTS'] ?? null) ? $arResult['PRODUCTS'] : [];

$logos = is_array($settings['LOGOS'] ?? null) ? $settings['LOGOS'] : [];
$projects = is_array($settings['PROJECTS'] ?? null) ? $settings['PROJECTS'] : [];
$certificates = is_array($settings['CERTIFICATES'] ?? null) ? $settings['CERTIFICATES'] : [];

$assetsBase = SITE_TEMPLATE_PATH . '/assets/home-main-dist/img';
$partnersTrack = !empty($logos) ? array_merge($logos, $logos) : [];

$decodeHtml = static function ($value): string {
    $decoded = (string)$value;

    for ($i = 0; $i < 2; $i++) {
        if (
            strpos($decoded, '&lt;') === false
            && strpos($decoded, '&gt;') === false
            && strpos($decoded, '&amp;lt;') === false
            && strpos($decoded, '&amp;gt;') === false
            && strpos($decoded, '&#0') === false
            && strpos($decoded, '&#x') === false
        ) {
            break;
        }

        $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if ($next === $decoded) {
            break;
        }

        $decoded = $next;
    }

    return $decoded;
};

$product1 = is_array($products[0] ?? null) ? $products[0] : [];
$product2 = is_array($products[1] ?? null) ? $products[1] : [];

$product1Photo = trim((string)($product1['PHOTO'] ?? ''));
$product2Photo = trim((string)($product2['PHOTO'] ?? ''));

if ($product1Photo === '') {
    $product1Photo = $assetsBase . '/product.png';
}

if ($product2Photo === '') {
    $product2Photo = $assetsBase . '/product1.png';
}

$heroTitle1 = trim((string)($settings['HERO_TITLE_1'] ?? ''));
$heroTitle2 = trim((string)($settings['HERO_TITLE_2'] ?? ''));
$aboutHtml = $decodeHtml((string)($settings['ABOUT_TEXT'] ?? ''));
?>
<main class="page home-main-dist">
    <section class="home_block">
        <div class="container">
            <div class="about_block">
                <div class="title_text">
                    <img src="<?= htmlspecialcharsbx($assetsBase . '/title_img.png') ?>" alt="" class="img_title">
                    <h1>
                        <?= htmlspecialcharsbx($heroTitle1) ?>
                        <?php if ($heroTitle2 !== ''): ?><br><?= htmlspecialcharsbx($heroTitle2) ?><?php endif; ?>
                    </h1>
                    <img src="<?= htmlspecialcharsbx($assetsBase . '/title_img1.png') ?>" alt="" class="img_title_second">
                </div>
                <p>Профессиональные решения для увеличения производительности вашей тормозной системы</p>
                <a href="/catalog/">КАТАЛОГ</a>
            </div>
        </div>
        <?php if (!empty($settings['VIDEO_SRC'])): ?>
            <video autoplay loop muted playsinline class="car_video">
                <source src="<?= htmlspecialcharsbx((string)$settings['VIDEO_SRC']) ?>"
                        type="<?= htmlspecialcharsbx((string)($settings['VIDEO_MIME'] ?? 'video/mp4')) ?>">
            </video>
        <?php endif; ?>
    </section>

    <section class="advantages_brakes_systems">
        <div class="container">
            <div class="about_block">
                <h2>ПРЕИМУЩЕСТВА <span>BRAKES SYSTEM</span></h2>
                <div class="items_systems">
                    <div class="item">
                        <img src="<?= htmlspecialcharsbx($assetsBase . '/img_item.png') ?>" alt="" class="img_item">
                        <h3>Эффективность торможения</h3>
                        <p>Премиальная тормозная система BRAKE SYSTEM обеспечивает более быструю и эффективную остановку автомобиля, что делает вождение более безопасным</p>
                    </div>
                    <div class="item">
                        <img src="<?= htmlspecialcharsbx($assetsBase . '/img_item1.png') ?>" alt="" class="img_item">
                        <h3>Долговечность и надежность</h3>
                        <p>Благодаря использованию высококачественных материалов и передовых технологий, премиальная тормозная система BRAKE SYSTEM имеет длительный срок службы и отличную надежность</p>
                    </div>
                    <div class="item">
                        <img src="<?= htmlspecialcharsbx($assetsBase . '/img_item2.png') ?>" alt="" class="img_item">
                        <h3>Меньший износ</h3>
                        <p>Премиальная тормозная система BRAKE SYSTEM обеспечивает более быструю и эффективную остановку автомобиля, что делает вождение более безопасным</p>
                    </div>
                    <div class="item">
                        <img src="<?= htmlspecialcharsbx($assetsBase . '/img_item3.png') ?>" alt="" class="img_item">
                        <h3>Улучшенная термостойкость</h3>
                        <p>Премиальная тормозная система BRAKE SYSTEM способна эффективно распределять тепло, что предотвращает перегрев и обеспечивает стабильную работу тормозов в любой ситуации.</p>
                    </div>
                    <div class="item">
                        <img src="<?= htmlspecialcharsbx($assetsBase . '/img_item4.png') ?>" alt="" class="img_item">
                        <h3>Улучшенный внешний вид</h3>
                        <p>Диски и колодки премиальной тормозной системы BRAKE SYSTEM имеют эстетичный и современный дизайн, который придает автомобилю оригинальный и привлекательный внешний вид</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php if (!empty($partnersTrack)): ?>
        <section class="partners_block">
            <div class="partners_group">
                <?php foreach ($partnersTrack as $logoPath): ?>
                    <a href="#">
                        <img src="<?= htmlspecialcharsbx((string)$logoPath) ?>" alt="">
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="about_products">
        <div class="container">
            <div class="about_block">
                <?php if (!empty($product1)): ?>
                    <div class="product">
                        <div class="text_product">
                            <h3><?= htmlspecialcharsbx((string)($product1['TITLE'] ?? '')) ?></h3>
                            <div class="img_group">
                                <img src="<?= htmlspecialcharsbx($product1Photo) ?>" alt="" class="img_product">
                                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img1.png') ?>" alt="" class="bg_img_color">
                            </div>
                            <h4><?= htmlspecialcharsbx((string)($product1['SUBTITLE'] ?? '')) ?></h4>
                            <div class="product_text_html"><?= $decodeHtml((string)($product1['TEXT_HTML'] ?? '')) ?></div>
                            <div class="descriptions_group">
                                <?= $decodeHtml((string)($product1['FEATURES_HTML'] ?? '')) ?>
                            </div>
                            <a href="/catalog/">ПОДОБРАТЬ</a>
                        </div>
                        <img src="<?= htmlspecialcharsbx($product1Photo) ?>"
                             alt="<?= htmlspecialcharsbx((string)($product1['TITLE'] ?? 'Товар')) ?>"
                             class="product_img">
                    </div>
                <?php endif; ?>

                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img.png') ?>" alt="" class="bg_img">

                <?php if (!empty($product2)): ?>
                    <div class="product second_product">
                        <img src="<?= htmlspecialcharsbx($product2Photo) ?>"
                             alt="<?= htmlspecialcharsbx((string)($product2['TITLE'] ?? 'Товар')) ?>"
                             class="product_img">
                        <div class="text_product text_second_group">
                            <h3><?= htmlspecialcharsbx((string)($product2['TITLE'] ?? '')) ?></h3>
                            <div class="img_group">
                                <img src="<?= htmlspecialcharsbx($product2Photo) ?>" alt="" class="img_product">
                                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img1.png') ?>" alt="" class="bg_img_color">
                            </div>
                            <h4><?= htmlspecialcharsbx((string)($product2['SUBTITLE'] ?? '')) ?></h4>
                            <div class="product_text_html"><?= $decodeHtml((string)($product2['TEXT_HTML'] ?? '')) ?></div>
                            <div class="descriptions_group">
                                <?= $decodeHtml((string)($product2['FEATURES_HTML'] ?? '')) ?>
                            </div>
                            <a href="/catalog/">ПОДОБРАТЬ</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="about_projects">
        <div class="container">
            <div class="about_block">
                <h2>НАШИ ПРОЕКТЫ</h2>
                <div class="slides_projects">
                    <div class="swiper home-main-projects-swiper">
                        <div class="swiper-wrapper">
                            <?php foreach ($projects as $projectPath): ?>
                                <div class="swiper-slide">
                                    <a class="img_project"
                                       href="<?= htmlspecialcharsbx((string)$projectPath) ?>"
                                       data-fancybox="gallery-2">
                                        <img src="<?= htmlspecialcharsbx((string)$projectPath) ?>" alt="">
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="swiper-button-next home-main-projects-next"></div>
                    <div class="swiper-button-prev home-main-projects-prev"></div>
                </div>
            </div>
        </div>
    </section>

    <section class="about_us">
        <img src="<?= htmlspecialcharsbx($assetsBase . '/car_bg.png') ?>" alt="" class="car_bg">
        <div class="container">
            <div class="about_block">
                <h2><?= htmlspecialcharsbx((string)($settings['ABOUT_TITLE'] ?? 'О НАС')) ?></h2>
                <div class="group_texts">
                    <div class="left_text">
                        <?= $aboutHtml ?>
                    </div>
                    <?php if (!empty($settings['ABOUT_PHOTO'])): ?>
                        <img src="<?= htmlspecialcharsbx((string)$settings['ABOUT_PHOTO']) ?>" alt="" class="img_cars">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <img src="<?= htmlspecialcharsbx($assetsBase . '/car_bg1.png') ?>" alt="" class="car_bg_second">
    </section>

    <?php if (!empty($partnersTrack)): ?>
        <section class="partners_block second_partners_group">
            <div class="partners_group">
                <?php foreach ($partnersTrack as $logoPath): ?>
                    <a href="#">
                        <img src="<?= htmlspecialcharsbx((string)$logoPath) ?>" alt="">
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="sertificates_block">
        <div class="container">
            <div class="about_block">
                <h2>СЕРТИФИКАТЫ</h2>
                <div class="sertificates_group">
                    <?php foreach ($certificates as $certificatePath): ?>
                        <a class="sertificate"
                           href="<?= htmlspecialcharsbx((string)$certificatePath) ?>"
                           data-fancybox="gallery-1">
                            <img src="<?= htmlspecialcharsbx((string)$certificatePath) ?>" alt="">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>
