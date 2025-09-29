<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use App\Brakes\Auth\Sms;

class BrakesAuthRegisterComponent extends CBitrixComponent implements Controllerable
{
    public function configureActions()
    {
        return [
            'sendCode' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'verifyCode' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
            'loginUserById' => [
                'prefilters' => [
                    new ActionFilter\Csrf(),
                    new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
                ],
            ],
        ];
    }

    private function generateCode(): int
    {
        return rand(1000, 9999);
    }

    public function executeComponent()
    {
        $this->includeComponentTemplate();
    }

    public function sendCodeAction()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $name = trim($request->getPost('name'));
        $phone = trim($request->getPost('phone'));
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalizedPhone) === 11 && $normalizedPhone[0] === '8') {
            $normalizedPhone[0] = '7';
        } elseif (strlen($normalizedPhone) === 10) {
            $normalizedPhone = '7' . $normalizedPhone;
        }
        $email = trim($request->getPost('email'));

        if (empty($name) || empty($normalizedPhone) || empty($email)) {
            return ['status' => 'error', 'message' => 'Все поля обязательны для заполнения.'];
        }

        $existingUser = UserTable::getList([
            'filter' => ['LOGIC' => 'OR', '=PERSONAL_PHONE' => $normalizedPhone, '=EMAIL' => $email],
            'select' => ['ID']
        ])->fetch();

        if ($existingUser) {
            return ['status' => 'error', 'message' => 'Пользователь с таким телефоном или email уже существует.'];
        }

        $code = 1234; // DEBUG: постоянный код для тестирования
        $smsSent = true; // DEBUG: эмуляция отправки SMS
        
        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'sms_code_' . $normalizedPhone;
        $cacheTime = 300; // 5 минут

        $cacheData = [
            'CODE' => $code,
            'DATA' => ['NAME' => $name, 'EMAIL' => $email, 'PHONE' => $normalizedPhone]
        ];

        if ($cache->initCache($cacheTime, $cacheId, '/sms_auth')) {
            $cache->clean($cacheId, '/sms_auth');
        }
        $cache->startDataCache($cacheTime, $cacheId, '/sms_auth');
        $cache->endDataCache($cacheData);

        if ($smsSent) {
            return ['status' => 'success', 'message' => 'Код подтверждения отправлен на ваш номер.'];
        } else {
            return ['status' => 'error', 'message' => 'Не удалось отправить SMS. Попробуйте позже.'];
        }
    }

    public function verifyCodeAction()
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $code = trim($request->getPost('code'));
        $phone = trim($request->getPost('phone'));
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalizedPhone) === 11 && $normalizedPhone[0] === '8') {
            $normalizedPhone[0] = '7';
        } elseif (strlen($normalizedPhone) === 10) {
            $normalizedPhone = '7' . $normalizedPhone;
        }

        if (empty($normalizedPhone) || empty($code)) {
            return ['status' => 'error', 'message' => 'Не передан телефон или код.'];
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'sms_code_' . $normalizedPhone;

        if (!$cache->initCache(300, $cacheId, '/sms_auth')) {
            return ['status' => 'error', 'message' => 'Время жизни кода истекло. Попробуйте снова.'];
        }
        
        $cacheData = $cache->getVars();
        
        if (!isset($cacheData['CODE']) || (int)$code !== $cacheData['CODE']) {
            return ['status' => 'error', 'message' => 'Неверный код подтверждения.'];
        }
        
        if (!isset($cacheData['DATA']['PHONE'])) {
            return ['status' => 'error', 'message' => 'Необходимые данные для регистрации не найдены. Попробуйте снова.'];
        }

        $user = new \CUser;
        $password = randString(10);
        $userData = $cacheData['DATA'];

        $arFields = [
            "NAME"              => $userData['NAME'],
            "EMAIL"             => $userData['EMAIL'],
            "LOGIN"             => $userData['EMAIL'],
            "LID"               => SITE_ID,
            "ACTIVE"            => "Y",
            "GROUP_ID"          => [2],
            "PASSWORD"          => $password,
            "CONFIRM_PASSWORD"  => $password,
            "PERSONAL_PHONE"    => $userData['PHONE'],
            "PERSONAL_MOBILE"   => $userData['PHONE'],
        ];
        
        $ID = $user->Add($arFields);
        
        $cache->clean($cacheId, '/sms_auth');

        if (intval($ID) > 0) {
            return ['status' => 'success', 'message' => 'Вы успешно зарегистрированы!', 'userId' => $ID];
        } else {
            return ['status' => 'error', 'message' => 'Ошибка регистрации: ' . $user->LAST_ERROR];
        }
    }

    public function loginUserByIdAction($userId)
    {
        global $USER;

        if (!is_numeric($userId) || intval($userId) <= 0) {
            return ['status' => 'error', 'message' => 'Некорректный ID пользователя.'];
        }

        if ($USER->Authorize(intval($userId))) {
            return ['status' => 'success', 'message' => 'Авторизация успешна.'];
        } else {
            return ['status' => 'error', 'message' => 'Ошибка авторизации.'];
        }
    }
}