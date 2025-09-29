<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Application;
use Bitrix\Main\UserTable;
use App\Brakes\Auth\Sms;

class BrakesAuthLoginComponent extends CBitrixComponent implements Controllerable
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
        $phone = trim($request->getPost('phone'));
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalizedPhone) === 11 && $normalizedPhone[0] === '8') {
            $normalizedPhone[0] = '7';
        } elseif (strlen($normalizedPhone) === 10) {
            $normalizedPhone = '7' . $normalizedPhone;
        }

        if (empty($normalizedPhone)) {
            return ['status' => 'error', 'message' => 'Введите номер телефона.'];
        }

        $user = UserTable::getList([
            'filter' => ['=PERSONAL_PHONE' => $normalizedPhone, '=ACTIVE' => 'Y'],
            'select' => ['ID']
        ])->fetch();

        if (!$user) {
            return ['status' => 'error', 'message' => 'Пользователь с таким номером не найден или неактивен.'];
        }

        $code = 1234; // DEBUG: постоянный код для тестирования
        $smsSent = true; // DEBUG: эмуляция отправки SMS

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'sms_code_' . preg_replace('/[^0-9]/', '', $normalizedPhone);
        
        // Логируем создание кэша
        $logMessage = date('Y-m-d H:i:s') . " - Creating cache for login:\n"
            . "Normalized Phone: {$normalizedPhone}\n"
            . "Cache ID: {$cacheId}\n"
            . "Code: 1234\n"
            . "User ID: " . $user['ID'] . "\n"
            . "-------------------------\n";
        
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);
        $cacheTime = 300;

        $cacheData = [
            'CODE' => $code,
            'USER_ID' => $user['ID']
        ];

        if ($cache->initCache($cacheTime, $cacheId, '/sms_auth')) {
            $cache->clean($cacheId, '/sms_auth');
        }
        $cache->startDataCache($cacheTime, $cacheId, '/sms_auth');
        $cache->endDataCache($cacheData);

        if ($smsSent) {
            return ['status' => 'success', 'message' => 'Код для входа отправлен на ваш номер.'];
        } else {
            return ['status' => 'error', 'message' => 'Не удалось отправить SMS. Попробуйте позже.'];
        }
    }

    public function verifyCodeAction()
    {
        global $USER;
        $request = Application::getInstance()->getContext()->getRequest();
        $code = trim($request->getPost('code'));
        $phone = trim($request->getPost('phone'));
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($normalizedPhone) === 11 && $normalizedPhone[0] === '8') {
            $normalizedPhone[0] = '7';
        } elseif (strlen($normalizedPhone) === 10) {
            $normalizedPhone = '7' . $normalizedPhone;
        }

        // Логируем полученные значения для отладки
        $logMessage = date('Y-m-d H:i:s') . " - VerifyCodeAction Login:\n"
            . "Original Phone: {$phone}\n"
            . "Normalized Phone: {$normalizedPhone}\n"
            . "Code: {$code}\n"
            . "Cache ID: sms_code_" . preg_replace('/[^0-9]/', '', $normalizedPhone) . "\n"
            . "-------------------------\n";
        
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);

        if (empty($normalizedPhone) || empty($code)) {
            return ['status' => 'error', 'message' => 'Не передан телефон или код.'];
        }

        $cache = \Bitrix\Main\Data\Cache::createInstance();
        $cacheId = 'sms_code_' . preg_replace('/[^0-9]/', '', $normalizedPhone);

        $logMessage = date('Y-m-d H:i:s') . " - Attempting to init cache for ID: {$cacheId}\n"
            . "Normalized Phone from request: {$normalizedPhone}\n"
            . "Original phone from request: {$phone}\n"
            . "-------------------------\n";
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);

        $cacheInitResult = $cache->initCache(300, $cacheId, '/sms_auth');
        
        $logMessage = date('Y-m-d H:i:s') . " - Cache init result: " . ($cacheInitResult ? 'SUCCESS' : 'FAILED') . "\n"
            . "Cache ID: {$cacheId}\n"
            . "-------------------------\n";
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);

        if (!$cacheInitResult) {
            $logMessage = date('Y-m-d H:i:s') . " - Cache not found for ID: {$cacheId}\n"
                . "Normalized Phone from request: {$normalizedPhone}\n"
                . "Original phone from request: {$phone}\n"
                . "Cache path might be: " . $cache->getCachePath() . $cacheId . "\n"
                . "-------------------------\n";
            $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
            error_log($logMessage, 3, $logPath);
            
            return ['status' => 'error', 'message' => 'Время жизни кода истекло. Попробуйте снова.'];
        }
        
        $cacheData = $cache->getVars();
        
        $logMessage = date('Y-m-d H:i:s') . " - Cache data retrieved for ID: {$cacheId}\n"
            . "Cache data: " . print_r($cacheData, true) . "\n"
            . "-------------------------\n";
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);
        
        // Добавляем дополнительную проверку для отладки
        if (!isset($cacheData['CODE'])) {
            $logMessage = date('Y-m-d H:i:s') . " - Code not found in cache for ID: {$cacheId}\n"
                . "Cache data: " . print_r($cacheData, true) . "\n"
                . "-------------------------\n";
            $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
            error_log($logMessage, 3, $logPath);
            
            return ['status' => 'error', 'message' => 'Данные кода не найдены в кэше. Попробуйте снова.'];
        }
        
        if ((int)$code !== $cacheData['CODE']) {
            $logMessage = date('Y-m-d H:i:s') . " - Invalid code provided. Expected: {$cacheData['CODE']}, Got: {$code}\n"
                . "Cache ID: {$cacheId}\n"
                . "-------------------------\n";
            $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
            error_log($logMessage, 3, $logPath);
            
            return ['status' => 'error', 'message' => 'Неверный код подтверждения.'];
        }

        $USER->Authorize($cacheData['USER_ID']);
        $cache->clean($cacheId, '/sms_auth');

        if ($USER->IsAuthorized()) {
            return ['status' => 'success', 'message' => 'Вы успешно авторизованы!'];
        } else {
            return ['status' => 'error', 'message' => 'Ошибка авторизации.'];
        }
    }
}