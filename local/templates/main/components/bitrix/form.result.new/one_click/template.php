<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<div class="popup__login-signin login__signin">
    <h1 class="main__title">Купить в один клик</h1>
    <?if ($arResult["isFormErrors"] == "Y"):?><?=$arResult["FORM_ERRORS_TEXT"];?><?endif;?>
    <?=$arResult["FORM_NOTE"]?>

    <?if ($arResult["isFormNote"] != "Y"):?>
        <?=$arResult["FORM_HEADER"]?>
        
        <!-- Поле Имя -->
        <div class="basket__form-item contacts-form__item">
            <label class="basket__label contacts-form__label" for="basketInput1">Имя</label>
            <div class="basket__input-wrapper contacts-form__input-wrapper">
                <img class="basket__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
                <input type="text" class="basket__input contacts-form__input contacts-form__input--user" name="form_text_<?=$arResult['QUESTIONS']['NAME']['STRUCTURE'][0]['ID']?>" value="" size="0">
            </div>
        </div>

        <!-- Поле Телефон -->
        <div class="basket__form-item contacts-form__item">
            <label class="basket__label contacts-form__label" for="basketInput2">Номер телефона</label>
            <div class="basket__input-wrapper contacts-form__input-wrapper">
                <img class="basket__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
                <input type="text" class="basket__input contacts-form__input contacts-form__input--tel" name="form_text_<?=$arResult['QUESTIONS']['PHONE']['STRUCTURE'][0]['ID']?>" value="" size="0">
            </div>
        </div>
        
        <button type="submit" name="web_form_submit" value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>" class="contacts-form-btn main-cataloge__shoping-btn">
            <span class="main-cataloge__shoping-text">Заказать</span>
            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
        </button>

    <?=$arResult["FORM_FOOTER"]?>
    <?endif;?>
</div>