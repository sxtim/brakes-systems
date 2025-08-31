<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$result = '<div class="main__breadcrumbs">';

$last = array_key_last($arResult);

foreach ($arResult as $i => $item) {
    $result .= '<a class="main__breadcrumbs-item' . ($i == $last ? ' active' : '') . '" href="' . $item['LINK'] .'">' .  $item['TITLE'] . '</a>';
}

$result .= '</div>';

return $result;
