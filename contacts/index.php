<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('Контакты');
?>

<main class="page">
				<div class="contacts__container">
					<div class="contacts__map" data-fls-dynamic=".contacts__inner,992,2">
						<iframe class="contacts__map-iframe" src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228020.4380506109!2d37.55036986495231!3d55.76974148304708!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sru!2sde!4v1747907340425!5m2!1sru!2sde" width="600" height="450" style="border:0;" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
					</div>
					<div class="contacts__main page__main">
						<div class="contacts__inner main__inner">
							<div class="main__breadcrumbs">
								<a class="main__breadcrumbs-item" href="#">Главная</a>
								<a class="main__breadcrumbs-item active" href="#">Контакты</a>
							</div>
							<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
								"AREA_FILE_SHOW"=>"page",
								"AREA_FILE_SUFFIX"=>"title",
								"EDIT_TEMPLATE"=>""
							),false);
							?>
							<div class="contacts__info">
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"info_address",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"info_emails",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"info_worktime",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"info_phone",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"info_route",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
							</div>
							<div class="contacts__controls">
								<a class="contacts__controls-btn" href="#"><?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"controls_auto",
									"EDIT_TEMPLATE"=>""
								),false);?></a>
								<a class="contacts__controls-btn" href="#"><?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"controls_metro",
									"EDIT_TEMPLATE"=>""
								),false);?></a>
							</div>
						</div>
					</div>
				</div>
			</main>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>