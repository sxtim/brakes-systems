<?php

namespace App\Brakes\Auth;

use Bitrix\Main\Loader;
use Bitrix\MessageService\Sender\SmsManager;

class Sms
{
    private static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if ($phone === null) {
            return '';
        }

        if (strlen($phone) === 11 && $phone[0] === '8') {
            $phone[0] = '7';
        } elseif (strlen($phone) === 10) {
            $phone = '7' . $phone;
        }

        return $phone;
    }

    private static function log(string $message, array $context = []): void
    {
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        $line = date('Y-m-d H:i:s') . ' - ' . $message;
        if ($context !== []) {
            $line .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $line .= PHP_EOL;
        @error_log($line, 3, $logPath);
    }

    /**
     * Отправка SMS-сообщения.
     *
     * @param string $phone Номер телефона получателя.
     * @param string $text Текст сообщения.
     * @return bool|string Возвращает ID сообщения в случае успеха, иначе false.
     */
    public static function send(string $phone, string $text)
    {
        $phone = self::normalizePhone($phone);
        if ($phone === '' || trim($text) === '') {
            self::log('sms_send_error', ['reason' => 'empty_phone_or_text']);
            return false;
        }

        if (!Loader::includeModule('messageservice')) {
            self::log('sms_send_error', ['reason' => 'messageservice_module_not_loaded']);
            return false;
        }

        $result = SmsManager::sendMessage([
            'MESSAGE_TO' => '+' . $phone,
            'MESSAGE_BODY' => $text,
        ]);

        $isSuccess = is_object($result) && method_exists($result, 'isSuccess') && $result->isSuccess();
        if (!$isSuccess) {
            $errors = [];
            if (is_object($result) && method_exists($result, 'getErrorMessages')) {
                $errors = $result->getErrorMessages();
            }
            self::log('sms_send_error', [
                'phone' => '+' . $phone,
                'errors' => $errors,
            ]);
            return false;
        }

        $messageId = (is_object($result) && method_exists($result, 'getId')) ? (string)$result->getId() : '';
        self::log('sms_send_ok', [
            'phone' => '+' . $phone,
            'messageId' => $messageId,
        ]);

        return $messageId !== '' ? $messageId : true;
    }
}
