<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

if (!isset($card) || !is_array($card)) {
    return;
}

$id = isset($card['ID']) ? (int)$card['ID'] : 0;
if ($id <= 0) {
    return;
}

$name = (string)($card['NAME'] ?? '');
$detailUrl = (string)($card['DETAIL_PAGE_URL'] ?? '#');
$imageData = isset($card['IMAGE']) && is_array($card['IMAGE']) ? $card['IMAGE'] : [];
$imageSrc = (string)($imageData['src'] ?? $card['IMG'] ?? '');
$imageWidth = isset($imageData['width']) ? (int)$imageData['width'] : 0;
$imageHeight = isset($imageData['height']) ? (int)$imageData['height'] : 0;
$priceHtml = (string)($card['PRICE_HTML'] ?? '');
$optionsAttr = (string)($card['OPTIONS_ATTR'] ?? '{}');
$contextSectionId = isset($card['CONTEXT_SECTION_ID']) ? (int)$card['CONTEXT_SECTION_ID'] : 0;
$contextSectionPath = (string)($card['CONTEXT_SECTION_PATH'] ?? '');
$details = isset($card['DETAILS']) && is_array($card['DETAILS']) ? $card['DETAILS'] : [];
$colors = isset($card['COLORS']) && is_array($card['COLORS']) ? $card['COLORS'] : [];
$buy = isset($card['BUY']) && is_array($card['BUY']) ? $card['BUY'] : [];
$selectedOptions = isset($card['SELECTED']) && is_array($card['SELECTED']) ? $card['SELECTED'] : [];
$favoritesView = !empty($card['FAVORITES_VIEW']);
$expandFeatures = !empty($card['EXPAND_FEATURES']);
$contextLabel = isset($card['CONTEXT_LABEL']) ? (string)$card['CONTEXT_LABEL'] : '';
$favoriteKey = (string)($card['FAVORITE_KEY'] ?? $card['FAVORITES_KEY'] ?? '');
if ($favoriteKey === '' && class_exists(\App\Brakes\Helper\FavoritesManager::class)) {
    $favoriteKey = \App\Brakes\Helper\FavoritesManager::buildFavoriteKey($id, [
        'section_id' => $contextSectionId,
        'section_path' => $contextSectionPath,
    ]);
}

$buyName = (string)($buy['NAME'] ?? $name);
$buyUrl = (string)($buy['URL'] ?? $detailUrl);
$hideFeatures = !empty($card['HIDE_FEATURES']);

$normalizeCase = static function ($value) {
    if (!is_string($value) || $value === '') {
        return null;
    }

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value);
    }

    return strtolower($value);
};

if ($selectedOptions !== []) {
    $normalized = [];
    foreach ($selectedOptions as $key => $value) {
        $normalizedKey = $normalizeCase($key);
        if ($normalizedKey === null) {
            continue;
        }
        $normalized[$normalizedKey] = $normalizeCase($value);
    }
    $selectedOptions = $normalized;
}

$optionLabelMap = [
    'two_piece_disc_construction' => 'Двусоставная конструкция диска:',
    'rotor_pattern' => 'Тип ротора:',
    'caliper_logo' => 'Лого на суппорт:',
    'electric_handbrake' => 'Электроручник:',
];

$optionValueMap = [
    'two_piece_disc_construction' => [
        'yes' => 'Да',
        'no' => 'Нет',
    ],
    'rotor_pattern' => [
        'none' => 'Нет',
        'perforation' => 'Перфорация',
        'slots' => 'Насечки',
        'perforation_slots' => 'Перфорация + насечки',
        'perforation_and_notches' => 'Перфорация + насечки',
        'notches' => 'Насечки',
    ],
    'caliper_logo' => [
        'standard' => 'Стандарт',
        'special' => 'Особый логотип',
        'custom_logo' => 'Особый логотип',
        'custom' => 'Особый логотип',
    ],
    'electric_handbrake' => [
        'yes' => 'Да',
        'no' => 'Нет',
    ],
];

$formatOptionValue = static function (string $key, ?string $value) use ($optionValueMap): string {
    if ($value === null || $value === '') {
        return '—';
    }

    $normalizedKey = strtolower($key);
    $normalizedValue = strtolower($value);

    if (isset($optionValueMap[$normalizedKey][$normalizedValue])) {
        return $optionValueMap[$normalizedKey][$normalizedValue];
    }

    return $value;
};

$hasSelectedValues = $selectedOptions !== [];
$twoPieceSelected = $hasSelectedValues ? ($selectedOptions['two_piece_disc_construction'] ?? 'no') : 'no';
$rotorSelected = $hasSelectedValues ? ($selectedOptions['rotor_pattern'] ?? 'perforation') : 'perforation';
$allowedRotorValues = ['perforation', 'slots', 'perforation_slots', 'perforation_and_notches'];
if (!in_array($rotorSelected, $allowedRotorValues, true)) {
    $rotorSelected = 'perforation';
}
$caliperSelected = $hasSelectedValues ? ($selectedOptions['caliper_logo'] ?? 'standard') : null;
$handbrakeSelected = $hasSelectedValues ? ($selectedOptions['electric_handbrake'] ?? 'no') : 'no';

$twoPieceYesSelected = $twoPieceSelected === 'yes';
$twoPieceNoSelected = $twoPieceSelected !== null && $twoPieceSelected !== 'yes';
$rotorPerforationSelected = $rotorSelected === 'perforation';
$rotorSlotsSelected = $rotorSelected === 'slots';
$rotorComboSelected = in_array($rotorSelected, ['perforation_slots', 'perforation_and_notches'], true);
$caliperStandardSelected = $caliperSelected === 'standard';
$caliperSpecialSelected = in_array($caliperSelected, ['special', 'custom_logo'], true);
$handbrakeYesSelected = $handbrakeSelected === 'yes';
$handbrakeNoSelected = $handbrakeSelected !== null && $handbrakeSelected !== 'yes';
$detailsOpenAttr = $expandFeatures ? ' open' : '';

$fromParam = '';
if ($detailUrl !== '') {
    $parts = parse_url($detailUrl);
    if (is_array($parts) && !empty($parts['query'])) {
        parse_str((string)$parts['query'], $queryParams);
        if (is_array($queryParams) && isset($queryParams['from'])) {
            $fromParam = (string)$queryParams['from'];
        }
    }
}
$isFromSearch = in_array($fromParam, ['search', 'viewed'], true);
$canShowLike = !$isFromSearch;
$isPadsCard = ($contextSectionPath !== '' && strpos($contextSectionPath, 'tormoznye_kolodki') === 0)
    || ($detailUrl !== '' && strpos($detailUrl, '/tormoznye_kolodki/') !== false);
$isDiscsCard = ($contextSectionPath !== '' && strpos($contextSectionPath, 'tormoznye_diski') === 0)
    || ($detailUrl !== '' && strpos($detailUrl, '/tormoznye_diski/') !== false);
$isShortCardCategory = $isPadsCard || $isDiscsCard;

?>
<div class="main-cataloge__item" data-fls-like-product="<?=$id?>"<?php if ($favoriteKey !== '') { ?> data-favorite-key="<?=htmlspecialcharsbx($favoriteKey)?>"<?php } ?><?php if ($contextSectionId > 0) { ?> data-context-section-id="<?=$contextSectionId?>"<?php } ?><?php if ($contextSectionPath !== '') { ?> data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?><?php if ($contextLabel !== '') { ?> data-context-label="<?=htmlspecialcharsbx($contextLabel)?>"<?php } ?>>
    <a class="main-cataloge__picture" href="<?=htmlspecialcharsbx($detailUrl)?>">
        <picture>
            <source media="(max-width: 600px)" srcset="<?=htmlspecialcharsbx($imageSrc)?>" type="image/jpeg">
            <source media="(max-width: 1200px)" srcset="<?=htmlspecialcharsbx($imageSrc)?>" type="image/jpeg">
            <img class="main-cataloge__img"
                 alt="<?=htmlspecialcharsbx($name !== '' ? $name : 'Image')?>"
                 src="<?=htmlspecialcharsbx($imageSrc)?>"
                 <?php if ($imageWidth > 0) { ?>width="<?=$imageWidth?>"<?php } ?>
                 <?php if ($imageHeight > 0) { ?>height="<?=$imageHeight?>"<?php } ?>
                 loading="lazy">
        </picture>
    </a>
    <div class="main-cataloge__item-content">
	    <div class="main-cataloge__item-top">
	            <h3 class="main-cataloge__item-title">
                    <a href="<?=htmlspecialcharsbx($detailUrl)?>">
                        <?php if ($contextLabel !== '') { ?>
                            <span class="main-cataloge__item-context"><?=htmlspecialcharsbx($contextLabel)?></span><br>
                        <?php } ?>
                        <?=htmlspecialcharsbx($name)?>
                    </a>
                </h3>
                <?php if ($favoritesView || $canShowLike): ?>
                    <button
                        data-fls-like-image=""
                        data-fls-like-button=""
                        data-product-id="<?=$id?>"
                        <?php if ($favoriteKey !== '') { ?>data-favorite-key="<?=htmlspecialcharsbx($favoriteKey)?>"<?php } ?>
                        data-options="<?=$optionsAttr?>"
                        <?php if ($contextSectionId > 0) { ?>data-context-section-id="<?=$contextSectionId?>"<?php } ?>
                        <?php if ($contextSectionPath !== '') { ?>data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?>
                        class="main-cataloge__like main-details__shoping-like"></button>
                <?php endif; ?>
	        </div>
        <?php if ($details !== []) { ?>
            <div class="main-cataloge__details main__details details">
                <?php if ($favoritesView && $contextLabel !== '') { ?>
                    <div class="main-cataloge__details-row details-row">
                        <span class="main-cataloge__details-label details-label">Для:</span>
                        <span class="main-cataloge__details-dots details-dots"></span>
                        <span class="main-cataloge__details-value details-value"><?=htmlspecialcharsbx($contextLabel)?></span>
                    </div>
                <?php } ?>
                <?php foreach ($details as $detail) {
                    $label = (string)($detail['label'] ?? '');
                    $value = (string)($detail['value'] ?? '');
                    if ($label === '' && $value === '') {
                        continue;
                    }

                    if ($value === '') {
                        $value = '—';
                    }
                ?>
                    <div class="main-cataloge__details-row details-row">
                        <?php if ($label !== '') { ?>
                            <span class="main-cataloge__details-label details-label"><?=htmlspecialcharsbx($label)?></span>
                        <?php } ?>
                        <span class="main-cataloge__details-dots details-dots"></span>
                        <span class="main-cataloge__details-value details-value"><?=htmlspecialcharsbx($value)?></span>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
        <?php if ($colors !== []) { ?>
            <div class="main-cataloge__colors">
                <h4 class="main-cataloge__colors-title">Доступные цвета:</h4>
                <div class="main-cataloge__colors-box">
                    <?php foreach ($colors as $color) {
                        $modifier = (string)($color['xmlId'] ?? $color['modifier'] ?? '');
                        if ($modifier === '') {
                            continue;
                        }
                    ?>
                        <button class="main-cataloge__colors-item cataloge__color--<?=htmlspecialcharsbx($modifier)?>"></button>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
	    <div class="main-cataloge__info">
	            <?php if (!$hideFeatures && $favoritesView) { ?>
	                <div class="main-cataloge__feature main-cataloge__feature--favorite">
	                    <?php foreach ($optionLabelMap as $optionKey => $label) {
	                        $rawValue = $selectedOptions[$optionKey] ?? null;
	                        $displayValue = $formatOptionValue($optionKey, $rawValue);
                    ?>
                        <div class="main-cataloge__details-row details-row">
                            <span class="main-cataloge__details-label details-label"><?=htmlspecialcharsbx($label)?></span>
                            <span class="main-cataloge__details-dots details-dots"></span>
                            <span class="main-cataloge__details-value details-value"><?=htmlspecialcharsbx($displayValue)?></span>
                        </div>
	                    <?php } ?>
	                </div>
	            <?php } elseif (!$hideFeatures && !$isShortCardCategory) { ?>
	                <div data-fls-spollers="" data-fls-spollers-one="" class="main-cataloge__feature spollers">
	                    <details class="spollers__item main-cataloge__feature-item--big"<?=$detailsOpenAttr?>>
	                        <summary class="main-cataloge__feature-item спollers__title">Двусоставная конструкция диска:</summary>
	                        <div class="main-cataloge__sublist спollers__body">
                            <div class="main-cataloge__sublist-item<?=$twoPieceYesSelected ? ' selected' : ''?>">Да</div>
                            <div class="main-cataloge__sublist-item<?=$twoPieceNoSelected ? ' selected' : ''?>">Нет</div>
                        </div>
                    </details>
                    <details class="spollers__item main-cataloge__feature-item--big"<?=$detailsOpenAttr?>>
                        <summary class="main-cataloge__feature-item спollers__title">Тип ротора:</summary>
                        <div class="main-cataloge__sublist спollers__body">
                            <div class="main-cataloge__sublist-item<?=$rotorPerforationSelected ? ' selected' : ''?>">ПЕРФОРАЦИЯ</div>
                            <div class="main-cataloge__sublist-item<?=$rotorSlotsSelected ? ' selected' : ''?>">НАСЕЧКИ</div>
                            <div class="main-cataloge__sublist-item<?=$rotorComboSelected ? ' selected' : ''?>">ПЕРФОРАЦИЯ + НАСЕЧКИ</div>
                        </div>
                    </details>
                    <details class="spollers__item"<?=$detailsOpenAttr?>>
                        <summary class="main-cataloge__feature-item спollers__title">Лого на суппорт:</summary>
                        <div class="main-cataloge__sublist спollers__body">
                            <div class="main-cataloge__sublist-item<?=$caliperStandardSelected ? ' selected' : ''?>">Стандартный</div>
                            <div class="main-cataloge__sublist-item<?=$caliperSpecialSelected ? ' selected' : ''?>">Особый логотип</div>
                        </div>
                    </details>
                    <details class="spollers__item"<?=$detailsOpenAttr?>>
                        <summary class="main-cataloge__feature-item спollers__title">Электроручник</summary>
                        <div class="main-cataloge__sublist спollers__body">
                            <div class="main-cataloge__sublist-item<?=$handbrakeYesSelected ? ' selected' : ''?>">Да</div>
                            <div class="main-cataloge__sublist-item<?=$handbrakeNoSelected ? ' selected' : ''?>">Нет</div>
                        </div>
                    </details>
                </div>
            <?php } ?>
            <div class="main-cataloge__price"><?=$priceHtml?></div>
	            <div class="main-cataloge__bottom-controls">
                    <?php if ($isFromSearch): ?>
                        <a class="main-cataloge__shoping-btn" href="<?=htmlspecialcharsbx($detailUrl)?>">
                            <span class="main-cataloge__shoping-text">Открыть товар</span>
                        </a>
                    <?php else: ?>
                        <button
                            data-fls-addtocart-button=""
                            class="main-cataloge__shoping-btn"
                            data-add-basket
                            data-options="<?=$optionsAttr?>"
                            data-product-id="<?=$id?>"
                            <?php if ($contextSectionId > 0) { ?>data-context-section-id="<?=$contextSectionId?>"<?php } ?>
                            <?php if ($contextSectionPath !== '') { ?> data-context-path="<?=htmlspecialcharsbx($contextSectionPath)?>"<?php } ?>
                            <?php if ($contextLabel !== '') { ?> data-context-label="<?=htmlspecialcharsbx($contextLabel)?>"<?php } ?>>
                            <span class="main-cataloge__shoping-text">В корзину</span>
                            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
                        </button>
                    <?php endif; ?>
	            </div>
	        </div>
	</div>
