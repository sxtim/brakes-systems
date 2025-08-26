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
							<h1 class="contacts__main-title main__title">Контакты</h1>
							<div class="contacts__info">
								<div class="contacts__info-item">
									<div class="contacts__info-row">
										<span class="contacts__info-text--bold">Адрес:</span>
										<p class="contacts__info-text">г. Москва, 69 км МКАД, БП Гринвуд, корпус 35.</p>
									</div>
								</div>
								<div class="contacts__info-item">
									<div class="contacts__info-row">
										<span class="contacts__info-text--bold">E-mail:</span>
										<p class="contacts__info-text">sales@icooh.store</p>
									</div>
									<div class="contacts__info-row">
										<span class="contacts__info-text--bold">Для рекламаций:</span>
										<p class="contacts__info-text">for_clients@stancebazztards.ru</p>
									</div>
								</div>
								<div class="contacts__info-item">
									<div class="contacts__info-row">
										<p class="contacts__info-text">Пн. - Чт. с 09:00 до 20:00</p>
									</div>
									<div class="contacts__info-row">
										<p class="contacts__info-text">Пятница с 09:00 до 19:00</p>
									</div>
									<div class="contacts__info-row">
										<p class="contacts__info-text">Сб. - Вс. с 10:00 до 19:00</p>
									</div>
								</div>
								<div class="contacts__info-item">
									<div class="contacts__info-row">
										<span class="contacts__info-text--bold">Телефон:</span>
										<p class="contacts__info-text">+7 (495) 132-31-49</p>
									</div>
								</div>
								<div class="contacts__info-item">
									<div class="contacts__info-row">
										<span class="contacts__info-text--bold">Схема проезда в центральный офис:</span>
									</div>
								</div>
							</div>
							<div class="contacts__controls">
								<a class="contacts__controls-btn" href="#">На личном авто</a>
								<a class="contacts__controls-btn" href="#">От метро</a>
							</div>
						</div>
					</div>
				</div>
			</main>

<?require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');?>