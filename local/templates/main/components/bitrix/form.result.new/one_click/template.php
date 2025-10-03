<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<style>
    .contacts-form__input-wrapper.error {
        border: 1px solid red !important;
    }
    .form-error-message {
        color: red;
        font-size: 12px;
        margin-top: 5px;
        width: 100%;
    }
    .contacts-form-btn:disabled {
        opacity: 0.5;
        pointer-events: none;
    }
</style>

<div class="popup__login-signin login__signin">
    <h1 class="main__title">Купить в один клик</h1>
    
    <?if ($arResult["isFormNote"] == "Y"):?>
        <p>Спасибо!</p>
        <p>Наш менеджер свяжется с Вами в ближайшее время</p>
    <?else:?>
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
                <input type="tel" class="basket__input contacts-form__input contacts-form__input--tel" name="form_text_<?=$arResult['QUESTIONS']['PHONE']['STRUCTURE'][0]['ID']?>" value="" size="0" oninput="this.value = this.value.replace(/[^0-9+()-\s]/g, '');">
            </div>
        </div>
        
        <!-- Скрытые поля -->
        <div style="display: none;">
            <input type="text" data-product-input="name" name="form_text_<?=$arResult['QUESTIONS']['PRODUCT_NAME']['STRUCTURE'][0]['ID']?>" value="">
            <input type="text" data-product-input="url" name="form_text_<?=$arResult['QUESTIONS']['PRODUCT_URL']['STRUCTURE'][0]['ID']?>" value="">
            <input type="text" data-product-input="price" name="form_text_<?=$arResult['QUESTIONS']['PRODUCT_PRICE']['STRUCTURE'][0]['ID']?>" value="">
            <input type="text" data-product-input="options" name="form_text_<?=$arResult['QUESTIONS']['PRODUCT_OPTIONS']['STRUCTURE'][0]['ID']?>" value="">
        </div>
        
        <button type="submit" name="web_form_submit" value="<?=htmlspecialcharsbx(trim($arResult["arForm"]["BUTTON"]) == '' ? GetMessage("FORM_ADD") : $arResult["arForm"]["BUTTON"]);?>" class="contacts-form-btn main-cataloge__shoping-btn">
            <span class="main-cataloge__shoping-text">Заказать</span>
            <img class="main-cataloge__shoping-img" src="<?=SITE_TEMPLATE_PATH?>/assets/img/shopping-icon.svg" alt="Img">
        </button>

    <?=$arResult["FORM_FOOTER"]?>
    <?endif;?>
</div>

<script>
    document.addEventListener('focusin', function(e) {
       
        const form = e.target.closest('.popup__login-signin form');
        if (form && !form.dataset.validationAttached) {
            form.dataset.validationAttached = 'true';
            
            const nameInput = form.querySelector('.contacts-form__input--user');
            const phoneInput = form.querySelector('.contacts-form__input--tel');
            const submitButton = form.querySelector('button[name="web_form_submit"]');

            function validate() {
                let isValid = true;

              
                if (nameInput && !nameInput.value.trim()) {
                    isValid = false;
                    const wrapper = nameInput.closest('.contacts-form__input-wrapper');
                    const formItem = nameInput.closest('.basket__form-item');
                    if (wrapper && formItem && !formItem.querySelector('.form-error-message')) {
                        wrapper.classList.add('error');
                        wrapper.insertAdjacentHTML('afterend', '<div class="form-error-message">Заполните поле</div>');
                    }
                } else if (nameInput) {
                    const wrapper = nameInput.closest('.contacts-form__input-wrapper');
                    const errorMsg = nameInput.closest('.basket__form-item').querySelector('.form-error-message');
                    if(wrapper) wrapper.classList.remove('error');
                    if(errorMsg) errorMsg.remove();
                }

       
                if (phoneInput && !phoneInput.value.trim()) {
                    isValid = false;
                    const wrapper = phoneInput.closest('.contacts-form__input-wrapper');
                    const formItem = phoneInput.closest('.basket__form-item');
                    if (wrapper && formItem && !formItem.querySelector('.form-error-message')) {
                        wrapper.classList.add('error');
                        wrapper.insertAdjacentHTML('afterend', '<div class="form-error-message">Заполните поле</div>');
                    }
                } else if (phoneInput) {
                    const wrapper = phoneInput.closest('.contacts-form__input-wrapper');
                    const errorMsg = phoneInput.closest('.basket__form-item').querySelector('.form-error-message');
                    if(wrapper) wrapper.classList.remove('error');
                    if(errorMsg) errorMsg.remove();
                }

        
                if (submitButton) {
                    submitButton.disabled = !isValid;
                }
            }


            if (submitButton) {
                submitButton.disabled = true;
            }


            form.addEventListener('input', validate);
            form.addEventListener('focusout', validate); // focusout всплывает, в отличие от blur
        }
    });
</script>