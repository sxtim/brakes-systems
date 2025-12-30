<?php

use App\Brakes\Helper\Storage;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;

if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

Asset::getInstance()->addString('<script type="module" crossorigin="" src="' . SITE_TEMPLATE_PATH . '/assets/js/product-page.min.js"></script>');
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH.'/assets/css/product-page.min.css');

$request = Application::getInstance()->getContext()->getRequest();
$fromParam = (string)$request->getQuery('from');
$favkeyParam = (string)$request->getQuery('favkey');
$isFromSearch = in_array($fromParam, ['search', 'viewed'], true);

// Canonicalize neutral transitions (from=search/viewed) to category+product URL:
// /catalog/{category_code}/{element_code}/?from=...
if ($isFromSearch) {
    $sectionCodePath = (string)($arResult['VARIABLES']['SECTION_CODE_PATH'] ?? '');
    $parts = array_values(array_filter(explode('/', trim($sectionCodePath, '/')), 'strlen'));
    $hasContext = !empty($favkeyParam) || count($parts) > 1;

    if ($hasContext) {
        // Preserve context-specific URLs when provided by search/viewed/favorites.
    } else {
        $elementCode = (string)($arResult['VARIABLES']['ELEMENT_CODE'] ?? '');
        if ($elementCode === '') {
            $elementId = (int)($arResult['VARIABLES']['ELEMENT_ID'] ?? 0);
            if ($elementId > 0 && Loader::includeModule('iblock')) {
                $row = \CIBlockElement::GetList([], ['IBLOCK_ID' => $arParams['IBLOCK_ID'], 'ID' => $elementId], false, ['nTopCount' => 1], ['ID', 'CODE'])->Fetch();
                if (is_array($row) && !empty($row['CODE'])) {
                    $elementCode = (string)$row['CODE'];
                }
            }
        }

        $categoryCode = '';
        if ($sectionCodePath !== '') {
            $categoryCode = (string)($parts[0] ?? '');
        } elseif (!empty($arResult['VARIABLES']['SECTION_CODE'])) {
            $categoryCode = (string)$arResult['VARIABLES']['SECTION_CODE'];
        }

        if ($categoryCode !== '' && $elementCode !== '') {
            $canonicalPath = '/catalog/' . $categoryCode . '/' . $elementCode . '/';
            $currentPath = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            if ($currentPath !== $canonicalPath) {
                $queryString = (string)($request->getServer()->get('QUERY_STRING') ?? '');
                $targetUrl = $canonicalPath . ($queryString !== '' ? ('?' . $queryString) : '');
                LocalRedirect($targetUrl, true, '302 Found');
            }
        }
    }
}

$path = $request->getRequestedPageDirectory();
$path = explode('/', $path);
unset($path[array_key_last($path)]);
unset($path[array_key_last($path)]);
$path = implode('/', $path) . '/';

$categoryCodeForFilter = '';
$sectionCodePathForFilter = (string)($arResult['VARIABLES']['SECTION_CODE_PATH'] ?? '');
if ($sectionCodePathForFilter !== '') {
    $parts = array_values(array_filter(explode('/', trim($sectionCodePathForFilter, '/')), 'strlen'));
    $categoryCodeForFilter = (string)($parts[0] ?? '');
}
?>
	    <main class="page">
	        <div class="page__container">
	            <?
	            $APPLICATION->IncludeComponent(
	                "bitrix:catalog.smart.filter",
	                "sidebar",
	                array(
	                    "CUSTOM_FOLDER" => $path,
	                    "IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
	                    "IBLOCK_ID" => $arParams["IBLOCK_ID"],
	                    "SECTION_ID" => (int)($arResult['VARIABLES']['SECTION_ID'] ?? 0),
	                    "SECTION_CODE" => $arResult['VARIABLES']['SECTION_CODE'],
	                    "SECTION_CODE_PATH" => $sectionCodePathForFilter,
	                    "CATEGORY_CODE" => $categoryCodeForFilter,
	                    "FILTER_NAME" => $arParams["FILTER_NAME"],
	                    "PRICE_CODE" => "",
	                    "CACHE_TYPE" => $arParams["CACHE_TYPE"],
                    "CACHE_TIME" => $arParams["CACHE_TIME"],
                    "CACHE_GROUPS" => $arParams["CACHE_GROUPS"],
                    "SAVE_IN_SESSION" => "N",
                    "FILTER_VIEW_MODE" => $arParams["FILTER_VIEW_MODE"],
                    "XML_EXPORT" => "N",
                    "SECTION_TITLE" => "NAME",
                    "SECTION_DESCRIPTION" => "DESCRIPTION",
                    'HIDE_NOT_AVAILABLE' => $arParams["HIDE_NOT_AVAILABLE"],
                    "TEMPLATE_THEME" => $arParams["TEMPLATE_THEME"],
                    'CONVERT_CURRENCY' => $arParams['CONVERT_CURRENCY'],
                    'CURRENCY_ID' => $arParams['CURRENCY_ID'],
                    "SEF_MODE" => 'N',
                    "SEF_RULE" => $arResult["FOLDER"].$arResult["URL_TEMPLATES"]["smart_filter"],
                    "SMART_FILTER_PATH" => $arResult["VARIABLES"]["SMART_FILTER_PATH"],
                    "PAGER_PARAMS_NAME" => $arParams["PAGER_PARAMS_NAME"],
                    "INSTANT_RELOAD" => $arParams["INSTANT_RELOAD"],
                ),
                $component,
                array('HIDE_ICONS' => 'Y')
            );
            ?>
            <div class="page__main">
                <div class="main__inner">
<?php
$baseTitle = '';
$sectionChain = [];
$sectionNames = [];
$sectionCodes = [];
$sectionId = 0;
$sectionCodePath = (string)($arResult['VARIABLES']['SECTION_CODE_PATH'] ?? '');

if (Loader::includeModule('iblock')) {
    // Получаем название товара (если есть ID или CODE)
    $elementId = (int)($arResult['VARIABLES']['ELEMENT_ID'] ?? 0);
    $elementCode = (string)($arResult['VARIABLES']['ELEMENT_CODE'] ?? '');
    if ($elementId > 0 || $elementCode !== '') {
        $filter = ['IBLOCK_ID' => $arParams['IBLOCK_ID']];
        if ($elementId > 0) {
            $filter['ID'] = $elementId;
        } else {
            $filter['=CODE'] = $elementCode;
        }
        $row = \CIBlockElement::GetList([], $filter, false, ['nTopCount' => 1], ['ID', 'NAME'])->Fetch();
        if (is_array($row) && !empty($row['NAME'])) {
            $baseTitle = (string)$row['NAME'];
        }
    }

    // Определяем контекст по SECTION_CODE_PATH / favkey
    $sectionId = (int)($arResult['VARIABLES']['SECTION_ID'] ?? 0);
    if ($sectionId <= 0 && $sectionCodePath !== '') {
        $sectionId = (int)\CIBlockFindTools::GetSectionIDByCodePath($arParams['IBLOCK_ID'], $sectionCodePath);
    }

    if ($sectionId <= 0 && $favkeyParam !== '') {
        if (preg_match('/^(\\d+):(s|p|n)(.*)$/', $favkeyParam, $matches)) {
            $favType = $matches[2];
            $favTail = $matches[3] ?? '';
            if ($favType === 's') {
                $favSectionId = (int)$favTail;
                if ($favSectionId > 0) {
                    $sectionId = $favSectionId;
                }
            } elseif ($favType === 'p') {
                $favSectionPath = trim((string)$favTail, " \t\n\r\0\x0B/");
                if ($favSectionPath !== '') {
                    $sectionCodePath = $favSectionPath;
                }
            }
        }
    }

    if ($sectionId <= 0 && $sectionCodePath !== '') {
        $sectionId = (int)\CIBlockFindTools::GetSectionIDByCodePath($arParams['IBLOCK_ID'], $sectionCodePath);
    }

    if ($sectionId > 0) {
        $navChain = \CIBlockSection::GetNavChain($arParams['IBLOCK_ID'], $sectionId, ['ID', 'NAME', 'CODE']);
        while ($row = $navChain->Fetch()) {
            $name = isset($row['NAME']) ? (string)$row['NAME'] : '';
            $code = isset($row['CODE']) ? (string)$row['CODE'] : '';
            if ($name !== '') {
                $sectionNames[] = $name;
            }
            if ($code !== '') {
                $sectionCodes[] = $code;
            }
            $sectionChain[] = [
                'NAME' => $name,
                'CODE' => $code,
            ];
        }
    }
}

if ($baseTitle === '') {
    $baseTitle = (string)$APPLICATION->GetTitle(false);
}

$contextNames = [];
if ($sectionNames !== []) {
    $contextNames = array_slice($sectionNames, 1);
    if ($contextNames !== []) {
        $contextNames = array_slice($contextNames, -3);
    }
}

$newTitle = trim($baseTitle);
if ($contextNames !== []) {
    $suffix = implode(' ', $contextNames);
    if ($suffix !== '') {
        $newTitle = trim(($newTitle !== '' ? $newTitle : $baseTitle) . ' — ' . $suffix);
    }
}
if ($newTitle !== '') {
    $APPLICATION->SetTitle($newTitle);
    $APPLICATION->SetPageProperty('title', $newTitle);
}

$breadcrumbs = [];
$breadcrumbs[] = ['TITLE' => 'Главная', 'URL' => '/'];
$breadcrumbs[] = ['TITLE' => 'Каталог', 'URL' => '/catalog/'];
$pathParts = [];
foreach ($sectionChain as $section) {
    $name = (string)($section['NAME'] ?? '');
    $code = (string)($section['CODE'] ?? '');
    if ($name === '') {
        continue;
    }
    $url = '';
    if ($code !== '') {
        $pathParts[] = $code;
        $url = '/catalog/' . implode('/', $pathParts) . '/';
    }
    $breadcrumbs[] = ['TITLE' => $name, 'URL' => $url];
}
$currentUrl = (string)($_SERVER['REQUEST_URI'] ?? '');
$currentUrl = $currentUrl !== '' ? $currentUrl : '#';
$breadcrumbs[] = ['TITLE' => $baseTitle, 'URL' => $currentUrl, 'ACTIVE' => true];
?>
<div class="main__breadcrumbs">
    <?php foreach ($breadcrumbs as $item): ?>
        <?php
        $title = (string)($item['TITLE'] ?? '');
        $url = (string)($item['URL'] ?? '');
        $active = !empty($item['ACTIVE']);
        ?>
        <?php if ($url !== ''): ?>
            <a class="main__breadcrumbs-item<?= $active ? ' active' : '' ?>" href="<?= htmlspecialcharsbx($url) ?>">
                <?= htmlspecialcharsbx($title) ?>
            </a>
        <?php else: ?>
            <span class="main__breadcrumbs-item<?= $active ? ' active' : '' ?>">
                <?= htmlspecialcharsbx($title) ?>
            </span>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
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
                        // отключаем установку тайтла внутри компонента, чтобы не перетирать наш суффикс
                        'SET_TITLE'                  => 'N',
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
            <?php

            $recommended = Storage::get('RECOMMENDED');

            if (is_array($recommended)) {
            ?>
                <div class="products__block slider-block">
                    <h2 class="products__title">Рекомендуемые товары</h2>
                    <div class="products__body">
                        <button class="products-slider__prev"></button>
                        <div data-fls-slider="" class="products-slider__slider swiper">
                            <div class="products-slider__wrapper swiper-wrapper">
                                <?php

                                foreach ($recommended as $item) {
                                ?>
                                    <div class="products-slider__slide swiper-slide">
                                        <a class="products-slider__card" href="<?=$item['DETAIL_PAGE_URL']?>">
                                            <div class="products-slider__picture">
                                                <picture>
                                                    <source media="(max-width: 600px)" srcset="<?=$item['IMG']?>" type="image/webp">
                                                    <source media="(max-width: 1200px)" srcset="<?=$item['IMG']?>" type="image/webp">
                                                    <img class="products-slider__img" alt="Img" src="<?=$item['IMG']?>">
                                                </picture>
                                            </div>
                                            <div class="products-slider__descr">
                                                <h3 class="products-slider__title"><?=$item['NAME']?></h3>
                                            </div>
                                        </a>
                                    </div>
                                <?php

                                }
                                ?>
                            </div>
                        </div>
                        <button class="products-slider__next"></button>
                    </div>
                </div>
            <?php

            }
            ?>
            <?php

            $APPLICATION->includeComponent('brakes:catalog.viewed', '')
            ?>
                </div>
            </div>
        </div>
    </main>
