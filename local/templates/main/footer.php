<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}

$siteAddressPath = function_exists('brakes_contact_include_path')
    ? brakes_contact_include_path('address')
    : SITE_DIR . 'include/contacts/address.php';
$sitePhonePath = function_exists('brakes_contact_include_path')
    ? brakes_contact_include_path('phone')
    : SITE_DIR . 'include/contacts/phone.php';
$siteEmailPath = function_exists('brakes_contact_include_path')
    ? brakes_contact_include_path('email')
    : SITE_DIR . 'include/contacts/email.php';
$sitePhoneText = function_exists('brakes_contact_include_text')
    ? brakes_contact_include_text($sitePhonePath, '+7 903 765-76-38')
    : '+7 903 765-76-38';
$siteEmailText = function_exists('brakes_contact_include_text')
    ? brakes_contact_include_text($siteEmailPath, 'sales@shinylight.ru')
    : 'sales@shinylight.ru';
$sitePhoneHref = function_exists('brakes_contact_phone_href')
    ? brakes_contact_phone_href($sitePhoneText)
    : 'tel:+79037657638';
$siteEmailHref = function_exists('brakes_contact_email_href')
    ? brakes_contact_email_href($siteEmailText)
    : 'mailto:sales@shinylight.ru';
?>
<footer data-fls-footer="" class="footer footer--unified">
    <div class="footer__container">
        <div class="footer__inner">
            <div class="footer__content">
                <div class="footer__content-item">
                    <h4 class="footer__content-title">Контакты</h4>
                    <ul class="footer__content-list">
                        <li class="footer__content-li">
                            <a class="footer__content-link footer__content-link--address" href="#"><?php
                                if (function_exists('brakes_contact_include_area')) {
                                    brakes_contact_include_area($siteAddressPath, 'г. Химки, микрорайон Подрезково, квартал Кирилловка, 7');
                                } else {
                                    echo 'г. Химки, микрорайон Подрезково, квартал Кирилловка, 7';
                                }
                            ?></a>
                        </li>
                        <li class="footer__content-li">
                            <a class="footer__content-link footer__content-link--tel" href="<?= htmlspecialcharsbx($sitePhoneHref) ?>"><?php
                                if (function_exists('brakes_contact_include_area')) {
                                    brakes_contact_include_area($sitePhonePath, $sitePhoneText);
                                } else {
                                    echo htmlspecialcharsbx($sitePhoneText);
                                }
                            ?></a>
                        </li>
                        <li class="footer__content-li">
                            <a class="footer__content-link footer__content-link--mail" href="<?= htmlspecialcharsbx($siteEmailHref) ?>"><?php
                                if (function_exists('brakes_contact_include_area')) {
                                    brakes_contact_include_area($siteEmailPath, $siteEmailText);
                                } else {
                                    echo htmlspecialcharsbx($siteEmailText);
                                }
                            ?></a>
                        </li>
                    </ul>
                </div>
                <div class="footer__content-item">
                    <h4 class="footer__content-title">Информация</h4>
                    <?php
                    $APPLICATION->IncludeComponent(
                        'brakes:menu',
                        'footer_links',
                        [
                            'ROOT_MENU_TYPE' => 'footer_info',
                        ]
                    );
                    ?>
                </div>
                <div class="footer__content-item">
                    <h4 class="footer__content-title">Заказ</h4>
                    <?php
                    $APPLICATION->IncludeComponent(
                        'brakes:menu',
                        'footer_links',
                        [
                            'ROOT_MENU_TYPE' => 'footer_order',
                        ]
                    );
                    ?>
                </div>
            </div>
            <div class="footer__social">
                <div class="footer__contacts header__contacts">
                    <p class="header__contacts-text">Присоединяйтесь к нам:</p>
                    <ul class="header__contacts-list">
                        <li class="header__contacts-li">
                            <a class="header__contacts-link" href="#">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/1.svg" alt="Image" loading="lazy" decoding="async">
                            </a>
                        </li>
                        <li class="header__contacts-li">
                            <a class="header__contacts-link" href="#">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/2.svg" alt="Image" loading="lazy" decoding="async">
                            </a>
                        </li>
                        <li class="header__contacts-li">
                            <a class="header__contacts-link" href="#">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/3.svg" alt="Image" loading="lazy" decoding="async">
                            </a>
                        </li>
                        <li class="header__contacts-li">
                            <a class="header__contacts-link" href="#">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/4.svg" alt="Image" loading="lazy" decoding="async">
                            </a>
                        </li>
                        <li class="header__contacts-li">
                            <a class="header__contacts-link" href="#">
                                <img src="<?= SITE_TEMPLATE_PATH ?>/assets/img/header-contacts/5.svg" alt="Image" loading="lazy" decoding="async">
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <img class="footer__bg-image footer__bg-image--desktop"
             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/bg_footer.png"
             alt=""
             aria-hidden="true"
             loading="lazy"
             decoding="async"
             fetchpriority="low">
        <img class="footer__bg-image footer__bg-image--mobile"
             src="<?= SITE_TEMPLATE_PATH ?>/assets/img/bg_footer1.png"
             alt=""
             aria-hidden="true"
             loading="lazy"
             decoding="async"
             fetchpriority="low">
    </div>
</footer>
</div>

		<div id="popup1" data-fls-popup="popup1" aria-hidden="true" class="popup">
			<div data-fls-popup-wrapper="" class="popup__wrapper">
				<div data-fls-popup-body="" class="popup__body">
					<button data-fls-popup-close="" type="button" class="popup__close">
						<svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
							<path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
						</svg>
					</button>
					<div data-fls-popup-content="" class="popup__text">
						<div class="popup__login-signin login__signin">
							<h1 class="main__title">Регистрация</h1>
							<p class="popup__login-message login__message">Введите код из СМС сообщение</p>
							<div class="login__body">
								<form class="login__form contacts-form" action="#">
									<div class="login__form-item contacts-form__item">
										<label class="login__label contacts-form__label" for="code1">Код</label>
										<div class="login__input-wrapper contacts-form__input-wrapper">
											<input class="login__input contacts-form__input contacts-form__input--user" name="code" placeholder="" type="number">
										                                  <input type="hidden" name="phone" value="">
										</div>
									</div>
									<div class="popup__bottom-row">
										<button type="button" class="popup__bottom-btn contacts-form-btn main-cataloge__shoping-btn js-verify-code-btn">
											<span class="main-cataloge__shoping-text">Ввести</span>
										</button>
										<button type="button" class="popup__bottom-btn popup__bottom-btn--gray contacts-form-btn main-cataloge__shoping-btn js-resend-code-btn">
											<span class="main-cataloge__shoping-text">Отправить снова</span>
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="popup2" data-fls-popup="popup2" aria-hidden="true" class="popup">
			<div data-fls-popup-wrapper="" class="popup__wrapper">
				<div data-fls-popup-body="" class="popup__body">
					<button data-fls-popup-close="" type="button" class="popup__close">
						<svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
							<path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
						</svg>
					</button>
					<div data-fls-popup-content="" class="popup__text">
						<div class="popup__login-signin login__signin">
							<h1 class="popup__main-title">Спасибо за регистрацию</h1>
							<p class="popup__login-message">
								Войдите с помощью вашего
								<br>
								номера телефона.
							</p>
							<div class="popup__login-success-actions">
								<a href="/login/" class="popup__login-success-btn contacts-form-btn main-cataloge__shoping-btn">
									<span class="main-cataloge__shoping-text">Войти</span>
								</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div id="popup3" data-fls-popup="popup3" aria-hidden="true" class="popup">
			<div data-fls-popup-wrapper="" class="popup__wrapper">
				<div data-fls-popup-body="" class="popup__body">
					<button data-fls-popup-close="" type="button" class="popup__close">
						<svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
							<path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
						</svg>
					</button>
					<div data-fls-popup-content="" class="popup__text">
						<div class="popup__login-signin login__signin">
							<h1 class="main__title">Вход</h1>
							<p class="popup__login-message login__message">Введите код из СМС сообщение</p>
							<div class="login__body">
								<form class="login__form contacts-form" action="#">
									<div class="login__form-item contacts-form__item">
										<label class="login__label contacts-form__label" for="code1">Код</label>
										<div class="login__input-wrapper contacts-form__input-wrapper">
											<input class="login__input contacts-form__input contacts-form__input--user" name="code" placeholder="" type="number">
										                                  <input type="hidden" name="phone" value="">
										</div>
									</div>
									<div class="popup__bottom-row">
										<button type="button" class="popup__bottom-btn contacts-form-btn main-cataloge__shoping-btn js-verify-code-btn">
											<span class="main-cataloge__shoping-text">Ввести</span>
										</button>
										<button type="button" class="popup__bottom-btn popup__bottom-btn--gray contacts-form-btn main-cataloge__shoping-btn js-resend-code-btn">
											<span class="main-cataloge__shoping-text">Отправить снова</span>
										</button>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- speed buy ----------------------------------------- -->
		<div data-fls-popup="speedBuy" aria-hidden="true" class="popup">
			<div data-fls-popup-wrapper="" class="popup__wrapper">
				<div data-fls-popup-body="" class="popup__body">
					<button data-fls-popup-close="" type="button" class="popup__close">
						<svg width="23" height="23" viewbox="0 0 23 23" fill="none" xmlns="http://www.w3.org/2000/svg">
							<path opacity="0.850056" d="M1.73333 1.48883L21.5469 21.0288" stroke="#979797" stroke-linecap="square"></path>
							<path opacity="0.850056" d="M21.2667 1.48883L1.45309 21.0288" stroke="#979797" stroke-linecap="square"></path>
						</svg>
					</button>
					<div data-fls-popup-content="" class="popup__text">
						<div class="popup__login-signin login__signin">
							<h1 class="main__title">Купить в один клик</h1>
							<form class="basket__form contacts-form" action="#">
								<div class="basket__form-item contacts-form__item">
									<label class="basket__label contacts-form__label" for="basketInput1">Имя</label>
									<div class="basket__input-wrapper contacts-form__input-wrapper">
										<img class="basket__input-icon contacts-form__input-icon" src="<?= SITE_TEMPLATE_PATH ?>/assets/img/basket/user.svg" alt="Image">
										<input class="basket__input contacts-form__input contacts-form__input--user" id="basketInput1" placeholder="" type="text">
									</div>
								</div>
								<div class="basket__form-item contacts-form__item">
									<label class="basket__label contacts-form__label" for="basketInput2">Номер телефона</label>
									<div class="basket__input-wrapper contacts-form__input-wrapper">
										<img class="basket__input-icon contacts-form__input-icon" src="<?= SITE_TEMPLATE_PATH ?>/assets/img/basket/tel.svg" alt="Image">
										<input class="basket__input contacts-form__input contacts-form__input--tel" id="basketInput2" placeholder="" type="number">
									</div>
								</div>
								<button class="contacts-form-btn main-cataloge__shoping-btn">
									<span class="main-cataloge__shoping-text">Заказать</span>
									<img class="main-cataloge__shoping-img" src="<?= SITE_TEMPLATE_PATH ?>/assets/img/shopping-icon.svg" alt="Img">
								</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

</body>
</html>
