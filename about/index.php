<?
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');
$APPLICATION->SetTitle('О компании');
?>
<main class="page">
				<div class="other__container">
					<div class="other__main page__main">
						<div class="main__inner">
							<div class="main__breadcrumbs">
								<a class="main__breadcrumbs-item" href="#">Главная</a>
								<a class="main__breadcrumbs-item" href="#">Страница</a>
								<a class="main__breadcrumbs-item active" href="#">Страница</a>
							</div>
							<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
								"AREA_FILE_SHOW"=>"page",
								"AREA_FILE_SUFFIX"=>"title",
								"EDIT_TEMPLATE"=>""
							),false);
							?>
							<div class="other__descr">
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"intro_big",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<div class="other__descr-list">
									<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
										"AREA_FILE_SHOW"=>"page",
										"AREA_FILE_SUFFIX"=>"benefits_title",
										"EDIT_TEMPLATE"=>""
									),false);
									?>
									<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
											"AREA_FILE_SHOW"=>"page",
											"AREA_FILE_SUFFIX"=>"benefits_list",
											"EDIT_TEMPLATE"=>""
										),false);
									?>
								</div>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"intro_middle",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
								<div class="other__descr-list">
									<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
										"AREA_FILE_SHOW"=>"page",
										"AREA_FILE_SUFFIX"=>"composition_title",
										"EDIT_TEMPLATE"=>""
									),false);
									?>
									<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
											"AREA_FILE_SHOW"=>"page",
											"AREA_FILE_SUFFIX"=>"composition_list",
											"EDIT_TEMPLATE"=>""
										),false);
									?>
								</div>
								<?$APPLICATION->IncludeComponent("bitrix:main.include","",Array(
									"AREA_FILE_SHOW"=>"page",
									"AREA_FILE_SUFFIX"=>"intro_small",
									"EDIT_TEMPLATE"=>""
								),false);
								?>
							</div>
						</div>
					</div>
				</div>
			</main>






<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>