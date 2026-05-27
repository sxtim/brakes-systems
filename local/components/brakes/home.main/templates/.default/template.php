<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$this->addExternalCss($templateFolder . '/style.css');
$this->addExternalJs($templateFolder . '/script.js');

$settings = is_array($arResult['SETTINGS'] ?? null) ? $arResult['SETTINGS'] : [];
$products = is_array($arResult['PRODUCTS'] ?? null) ? $arResult['PRODUCTS'] : [];

$assetsBase = SITE_TEMPLATE_PATH . '/assets/home-main-dist/img';

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

$resolveFilePath = static function ($value) use (&$resolveFilePath): string {
    if (is_numeric($value)) {
        $fileId = (int)$value;
        if ($fileId > 0 && class_exists('CFile')) {
            return (string)CFile::GetPath($fileId);
        }
    }

    if (is_string($value)) {
        $value = trim($value);
        return $value !== 'Array' ? $value : '';
    }

    if (!is_array($value)) {
        return '';
    }

    foreach (['SRC', 'src', 'PATH', 'path', 'URL', 'url', 'FILE_SRC', 'file_src'] as $key) {
        if (!empty($value[$key])) {
            $path = $resolveFilePath($value[$key]);
            if ($path !== '') {
                return $path;
            }
        }
    }

    foreach (['ID', 'id', 'VALUE', '~VALUE'] as $key) {
        if (isset($value[$key])) {
            $path = $resolveFilePath($value[$key]);
            if ($path !== '') {
                return $path;
            }
        }
    }

    foreach ($value as $item) {
        $path = $resolveFilePath($item);
        if ($path !== '') {
            return $path;
        }
    }

    return '';
};

$resolveFilePaths = static function ($value) use (&$resolveFilePaths, $resolveFilePath): array {
    if (!is_array($value)) {
        $path = $resolveFilePath($value);
        return $path !== '' ? [$path] : [];
    }

    foreach (['SRC', 'src', 'PATH', 'path', 'URL', 'url', 'FILE_SRC', 'file_src', 'ID', 'id'] as $key) {
        if (!empty($value[$key])) {
            $path = $resolveFilePath($value);
            return $path !== '' ? [$path] : [];
        }
    }

    if (isset($value['VALUE']) || isset($value['~VALUE'])) {
        return $resolveFilePaths($value['VALUE'] ?? $value['~VALUE']);
    }

    $paths = [];
    foreach ($value as $item) {
        $paths = array_merge($paths, $resolveFilePaths($item));
    }

    return array_values(array_unique($paths));
};

$resizeImage = static function ($fileId, int $width, int $height): string {
    $fileId = (int)$fileId;
    if ($fileId <= 0 || !class_exists('CFile')) {
        return '';
    }

    $image = CFile::ResizeImageGet(
        $fileId,
        ['width' => $width, 'height' => $height],
        BX_RESIZE_IMAGE_PROPORTIONAL,
        true
    );

    return is_array($image) ? (string)($image['src'] ?? '') : '';
};

$logos = $resolveFilePaths($settings['LOGOS'] ?? []);
$projects = $resolveFilePaths($settings['PROJECTS'] ?? []);
$certificates = $resolveFilePaths($settings['CERTIFICATES'] ?? []);
$partnersTrack = !empty($logos) ? array_merge($logos, $logos) : [];

$product1 = is_array($products[0] ?? null) ? $products[0] : [];
$product2 = is_array($products[1] ?? null) ? $products[1] : [];

$product1PhotoId = (int)($product1['PHOTO_ID'] ?? 0);
$product2PhotoId = (int)($product2['PHOTO_ID'] ?? 0);
$aboutPhotoId = (int)($settings['ABOUT_PHOTO_ID'] ?? 0);
$posterDesktopId = (int)($settings['POSTER_DESKTOP_ID'] ?? 0);
$posterMobileId = (int)($settings['POSTER_MOBILE_ID'] ?? 0);

$product1Photo = $resolveFilePath($product1['PHOTO'] ?? '');
$product2Photo = $resolveFilePath($product2['PHOTO'] ?? '');
$aboutPhoto = $resolveFilePath($settings['ABOUT_PHOTO'] ?? '');
$product1PhotoLarge = $resizeImage($product1PhotoId, 1400, 1100) ?: $product1Photo;
$product1PhotoPreview = $resizeImage($product1PhotoId, 900, 760) ?: $product1Photo;
$product2PhotoLarge = $resizeImage($product2PhotoId, 1400, 1100) ?: $product2Photo;
$product2PhotoPreview = $resizeImage($product2PhotoId, 900, 760) ?: $product2Photo;
$aboutPhoto = $resizeImage($aboutPhotoId, 900, 700) ?: $aboutPhoto;
$heroDesktopPoster = $resizeImage($posterDesktopId, 1920, 900) ?: $resolveFilePath($settings['POSTER_DESKTOP'] ?? '');
$heroMobilePoster = $resizeImage($posterMobileId, 900, 1200) ?: $resolveFilePath($settings['POSTER_MOBILE'] ?? '');

if ($product1Photo === '') {
    $product1Photo = $assetsBase . '/product.png';
    $product1PhotoLarge = $product1Photo;
    $product1PhotoPreview = $product1Photo;
}

if ($product2Photo === '') {
    $product2Photo = $assetsBase . '/product1.png';
    $product2PhotoLarge = $product2Photo;
    $product2PhotoPreview = $product2Photo;
}

$heroTitle1 = trim((string)($settings['HERO_TITLE_1'] ?? ''));
$heroTitle2 = trim((string)($settings['HERO_TITLE_2'] ?? ''));
$heroSubtitle = trim((string)($settings['HERO_SUBTITLE'] ?? ''));
$aboutHtml = $decodeHtml((string)($settings['ABOUT_TEXT'] ?? ''));
$heroDesktopVideo = $resolveFilePath($settings['VIDEO_DESKTOP'] ?? '');
$heroMobileVideo = $resolveFilePath($settings['VIDEO_MOBILE'] ?? '');
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
                <?php if ($heroSubtitle !== ''): ?>
                    <p><?= nl2br(htmlspecialcharsbx($heroSubtitle), false) ?></p>
                <?php endif; ?>
                <a href="/catalog/">КАТАЛОГ</a>
            </div>
        </div>
        <?php if ($heroDesktopPoster !== '' || $heroMobilePoster !== ''): ?>
            <picture class="home_block_poster">
                <?php if ($heroMobilePoster !== ''): ?>
                    <source media="(max-width: 767.98px)" srcset="<?= htmlspecialcharsbx($heroMobilePoster) ?>">
                <?php endif; ?>
                <img src="<?= htmlspecialcharsbx($heroDesktopPoster !== '' ? $heroDesktopPoster : $heroMobilePoster) ?>"
                     alt=""
                     fetchpriority="high">
            </picture>
        <?php endif; ?>
        <?php if ($heroDesktopVideo !== '' || $heroMobileVideo !== ''): ?>
            <video autoplay
                   loop
                   muted
                   playsinline
                   preload="none"
                   class="car_video"
                   data-home-hero-video
                   data-src-desktop="<?= htmlspecialcharsbx($heroDesktopVideo) ?>"
                   data-src-mobile="<?= htmlspecialcharsbx($heroMobileVideo) ?>"></video>
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
                        <img src="<?= htmlspecialcharsbx($logoPath) ?>" alt="" loading="lazy" decoding="async">
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
                                <img src="<?= htmlspecialcharsbx($product1PhotoPreview) ?>" alt="" class="img_product" loading="lazy" decoding="async">
                                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img1.png') ?>" alt="" class="bg_img_color" loading="lazy" decoding="async">
                            </div>
                            <h4><?= htmlspecialcharsbx((string)($product1['SUBTITLE'] ?? '')) ?></h4>
                            <div class="product_text_html"><?= $decodeHtml((string)($product1['TEXT_HTML'] ?? '')) ?></div>
                            <div class="descriptions_group">
                                <?= $decodeHtml((string)($product1['FEATURES_HTML'] ?? '')) ?>
                            </div>
                            <a href="/catalog/">ПОДОБРАТЬ</a>
                        </div>
                        <img src="<?= htmlspecialcharsbx($product1PhotoLarge) ?>"
                             alt="<?= htmlspecialcharsbx((string)($product1['TITLE'] ?? 'Товар')) ?>"
                             class="product_img"
                             loading="lazy"
                             decoding="async">
                    </div>
                <?php endif; ?>

                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img.png') ?>" alt="" class="bg_img" loading="lazy" decoding="async">

                <?php if (!empty($product2)): ?>
                    <div class="product second_product">
                        <img src="<?= htmlspecialcharsbx($product2PhotoLarge) ?>"
                             alt="<?= htmlspecialcharsbx((string)($product2['TITLE'] ?? 'Товар')) ?>"
                             class="product_img"
                             loading="lazy"
                             decoding="async">
                        <div class="text_product text_second_group">
                            <h3><?= htmlspecialcharsbx((string)($product2['TITLE'] ?? '')) ?></h3>
                            <div class="img_group">
                                <img src="<?= htmlspecialcharsbx($product2PhotoPreview) ?>" alt="" class="img_product" loading="lazy" decoding="async">
                                <img src="<?= htmlspecialcharsbx($assetsBase . '/bg_img1.png') ?>" alt="" class="bg_img_color" loading="lazy" decoding="async">
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
                                       href="<?= htmlspecialcharsbx($projectPath) ?>"
                                       data-fancybox="gallery-2">
                                        <img src="<?= htmlspecialcharsbx($projectPath) ?>" alt="" loading="lazy" decoding="async">
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
        <img src="<?= htmlspecialcharsbx($assetsBase . '/car_bg.png') ?>" alt="" class="car_bg" loading="lazy" decoding="async">
        <div class="container">
            <div class="about_block">
                <h2><?= htmlspecialcharsbx((string)($settings['ABOUT_TITLE'] ?? 'О НАС')) ?></h2>
                <div class="group_texts">
                    <div class="left_text">
                        <?= $aboutHtml ?>
                    </div>
                    <?php if ($aboutPhoto !== ''): ?>
                        <img src="<?= htmlspecialcharsbx($aboutPhoto) ?>" alt="" class="img_cars" loading="lazy" decoding="async">
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <img src="<?= htmlspecialcharsbx($assetsBase . '/car_bg1.png') ?>" alt="" class="car_bg_second" loading="lazy" decoding="async">
    </section>

    <?php if (!empty($partnersTrack)): ?>
        <section class="partners_block second_partners_group">
            <div class="partners_group">
                <?php foreach ($partnersTrack as $logoPath): ?>
                    <a href="#">
                        <img src="<?= htmlspecialcharsbx($logoPath) ?>" alt="" loading="lazy" decoding="async">
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
                           href="<?= htmlspecialcharsbx($certificatePath) ?>"
                           data-fancybox="gallery-1">
                            <img src="<?= htmlspecialcharsbx($certificatePath) ?>" alt="" loading="lazy" decoding="async">
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
</main>
