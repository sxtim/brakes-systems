<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
global $USER;
if ($USER->IsAuthorized()) {
    LocalRedirect('/');
    exit;
}
$APPLICATION->SetTitle('Регистрация');
$APPLICATION->AddChainItem($APPLICATION->GetTitle());
?>
    <main class="page">
        <div class="login__container">
            <div class="other__main page__main">
                <div class="main__inner">
                    <div class="login__main-page">
                        <div class="login__picture-wrapper">
                            <picture>
                                <source media="(max-width: 600px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg-600.webp" type="image/webp">
                                <source media="(max-width: 1200px)" srcset="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg-1200.webp" type="image/webp">
                                <img class="login__img" alt="Image" src="<?=SITE_TEMPLATE_PATH?>/assets/img/login-bg.webp">
                            </picture>
                        </div>
                        <div class="login__forms">
                            <?php $APPLICATION->IncludeComponent("bitrix:breadcrumb",
                                "",Array(
                                    "START_FROM" => "0",
                                    "PATH" => "",
                                    "SITE_ID" => "s1"
                                )
                            );?>
                            <?$APPLICATION->IncludeComponent(
                                "brakes:auth.register",
                                ".default",
                                array(),
                                false
                            );?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');