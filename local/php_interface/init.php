<?php

require_once 'include/func.php';
require_once 'include/events.php';

Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'App\Brakes\Helper\Storage' => '/local/app/Brakes/Helper/Storage.php',
    'App\Brakes\Helper\Highload' => '/local/app/Brakes/Helper/Highload.php',
    'App\Brakes\Auth\Sms' => '/local/app/Brakes/Auth/Sms.php',
]);

