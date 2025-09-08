<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if($USER->IsAuthorized()):?>

<div class="login__success">
	<p><?echo GetMessage("MAIN_REGISTER_AUTH")?></p>
</div>

<?else:?>

<?if (count($arResult["ERRORS"]) > 0):?>
	<div class="login__errors">
		<?foreach ($arResult["ERRORS"] as $key => $error):?>
			<div class="login__error"><?=htmlspecialcharsEx($error)?></div>
		<?endforeach?>
	</div>
<?endif?>

<?if($arResult["USE_EMAIL_CONFIRMATION"] === "Y"):?>
	<div class="login__success">
		<p><?=GetMessage("AUTH_EMAIL_SENT")?></p>
	</div>
<?endif?>

<form method="post" action="<?=POST_FORM_ACTION_URI?>" name="regform" class="login__form contacts-form">
	<?if($arResult["BACKURL"] <> ''):?>
		<input type="hidden" name="backurl" value="<?=$arResult["BACKURL"]?>" />
	<?endif?>
	
	<?if(in_array("NAME", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_name"><?=GetMessage("AUTH_NAME")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
			<input class="login__input contacts-form__input contacts-form__input--user" 
				type="text" 
				name="REGISTER[NAME]" 
				id="register_name"
				value="<?=$arResult["VALUES"]["NAME"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["NAME"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if(in_array("LAST_NAME", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_last_name"><?=GetMessage("AUTH_LAST_NAME")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/user.svg" alt="Image">
			<input class="login__input contacts-form__input contacts-form__input--user" 
				type="text" 
				name="REGISTER[LAST_NAME]" 
				id="register_last_name"
				value="<?=$arResult["VALUES"]["LAST_NAME"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["LAST_NAME"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if(in_array("PERSONAL_PHONE", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_phone"><?=GetMessage("AUTH_PHONE")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/tel.svg" alt="Image">
			<input class="login__input contacts-form__input contacts-form__input--tel" 
				type="tel" 
				name="REGISTER[PERSONAL_PHONE]" 
				id="register_phone"
				value="<?=$arResult["VALUES"]["PERSONAL_PHONE"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["PERSONAL_PHONE"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if(in_array("EMAIL", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_email"><?=GetMessage("AUTH_EMAIL")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/mail.svg" alt="Image">
			<input class="login__input contacts-form__input contacts-form__input--mail" 
				type="email" 
				name="REGISTER[EMAIL]" 
				id="register_email"
				value="<?=$arResult["VALUES"]["EMAIL"]?>" />
			<!-- Скрытое поле для автоматического копирования email в логин -->
			<input type="hidden" name="REGISTER[LOGIN]" id="register_login" value="<?=$arResult["VALUES"]["LOGIN"] ? $arResult["VALUES"]["LOGIN"] : $arResult["VALUES"]["EMAIL"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["EMAIL"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if(in_array("PASSWORD", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_password"><?=GetMessage("AUTH_PASSWORD")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/lock.svg" alt="Image">
			<input class="login__input contacts-form__input" 
				type="password" 
				name="REGISTER[PASSWORD]" 
				id="register_password"
				value="<?=$arResult["VALUES"]["PASSWORD"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["PASSWORD"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if(in_array("CONFIRM_PASSWORD", $arParams["SHOW_FIELDS"])):?>
	<div class="login__form-item contacts-form__item">
		<label class="login__label contacts-form__label" for="register_confirm_password"><?=GetMessage("AUTH_CONFIRM")?></label>
		<div class="login__input-wrapper contacts-form__input-wrapper">
			<img class="login__input-icon contacts-form__input-icon" src="<?=SITE_TEMPLATE_PATH?>/assets/img/basket/lock.svg" alt="Image">
			<input class="login__input contacts-form__input" 
				type="password" 
				name="REGISTER[CONFIRM_PASSWORD]" 
				id="register_confirm_password"
				value="<?=$arResult["VALUES"]["CONFIRM_PASSWORD"]?>" />
		</div>
		<?if($arResult["REQUIRED_FIELDS_FLAGS"]["CONFIRM_PASSWORD"]):?>
			<span class="required">*</span>
		<?endif?>
	</div>
	<?endif?>

	<?if($arResult["USE_CAPTCHA"] == "Y"):?>
		<div class="login__form-item contacts-form__item">
			<label class="login__label contacts-form__label"><?=GetMessage("CAPTCHA_REGF_TITLE")?></label>
			<div class="login__captcha">
				<input type="hidden" name="captcha_sid" value="<?=$arResult["CAPTCHA_CODE"]?>" />
				<img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["CAPTCHA_CODE"]?>" width="180" height="40" alt="CAPTCHA" />
				<input class="login__input contacts-form__input" 
					type="text" 
					name="captcha_word" 
					maxlength="50" 
					value="" 
					placeholder="<?=GetMessage("CAPTCHA_REGF_PROMT")?>" />
			</div>
		</div>
	<?endif?>

	<div class="login__form-submit">
		<button type="submit" class="contacts-form-btn main-cataloge__shoping-btn" name="register_submit_button" value="Y">
			<span class="main-cataloge__shoping-text"><?=GetMessage("AUTH_REGISTER")?></span>
		</button>
	</div>
</form>

<script>
document.bxform = document.forms.regform;

// Дополнительная валидация и автоматическое копирование email в логин
if (document.bxform) {
	const emailField = document.getElementById('register_email');
	const loginField = document.getElementById('register_login');
	
	// Автоматическое копирование email в логин при вводе
	if (emailField && loginField) {
		// Копируем при загрузке страницы если есть значение
		if (emailField.value) {
			loginField.value = emailField.value;
		}
		
		// Копируем при каждом изменении
		emailField.addEventListener('input', function() {
			loginField.value = this.value;
		});
		
		// Дополнительная проверка при потере фокуса
		emailField.addEventListener('blur', function() {
			loginField.value = this.value;
		});
	}
	
	document.bxform.addEventListener('submit', function(e) {
		const email = document.getElementById('register_email');
		const name = document.getElementById('register_name');
		const password = document.getElementById('register_password');
		const confirmPassword = document.getElementById('register_confirm_password');
		
		// Проверка заполнения имени
		if (name && !name.value.trim()) {
			alert('Пожалуйста, укажите ваше имя');
			e.preventDefault();
			return false;
		}
		
		// Проверка формата email
		if (email && email.value) {
			const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			if (!emailRegex.test(email.value)) {
				alert('Некорректный формат email');
				e.preventDefault();
				return false;
			}
			
			// Принудительно копируем email в логин перед отправкой
			if (loginField) {
				loginField.value = email.value;
			}
		}
		
		// Проверка длины пароля
		if (password && password.value.length < 6) {
			alert('Пароль должен содержать минимум 6 символов');
			e.preventDefault();
			return false;
		}
		
		// Проверка совпадения паролей
		if (password && confirmPassword && password.value !== confirmPassword.value) {
			alert('Пароли не совпадают');
			e.preventDefault();
			return false;
		}
		
		// Финальная проверка что логин заполнен
		if (loginField && !loginField.value) {
			alert('Ошибка: логин не заполнен');
			e.preventDefault();
			return false;
		}
	});
}
</script>

<?endif?>