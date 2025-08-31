<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
?>
<div class="breadcrumbs-nav">
    <?php

    if ($arResult['NavPageNomer'] != 1) {
    ?>
            <a class="breadcrumbs-nav__link" href="?PAGEN_1=<?=$arResult['NavPageNomer'] - 1?>"><</a>
    <?php

    }
    ?>
    <?php for ($i = $arResult['NavPageNomer']; $i <= $arResult['NavPageCount']; $i++): ?>
        <a class="breadcrumbs-nav__link<?php if ($i == $arResult['NavPageNomer']) {echo ' active';}?>" href="?PAGEN_1=<?=$i?>"><?=$i?></a>
    <?php endfor; ?>
    <?php

    if ($arResult['NavPageNomer'] != $arResult['NavPageCount']) {
    ?>
        <a class="breadcrumbs-nav__link" href="?PAGEN_1=<?=$arResult['NavPageNomer'] + 1?>"> > </a>
    <?php

    }
    ?>
</div>
