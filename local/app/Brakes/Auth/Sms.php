<?php

namespace App\Brakes\Auth;

use Bitrix\Main\Web\HttpClient;

class Sms
{
    /**
     * @const string API ID для доступа к sms.ru
     */
    private const API_ID = '35217E9E-961F-326C-84AC-E582EECB2E33'; // TODO: Вынести в настройки модуля

    /**
     * Отправка SMS-сообщения.
     *
     * @param string $phone Номер телефона получателя.
     * @param string $text Текст сообщения.
     * @return bool|string Возвращает ID сообщения в случае успеха, иначе false.
     */
    public static function send(string $phone, string $text)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        $url = 'https://sms.ru/sms/send';
        $params = [
            'api_id' => self::API_ID,
            'to' => $phone,
            'msg' => $text,
            'json' => 1
        ];

        $httpClient = new HttpClient();
        $httpClient->setTimeout(10);

        try {
            $response = $httpClient->post($url, $params);
            $error = $httpClient->getError();
        } catch (\Exception $e) {
            $response = false;
            $error = ['exception' => $e->getMessage()];
        }

        // Логируем запрос и ответ
        $logMessage = date('Y-m-d H:i:s') . " - SMS.RU Request (HttpClient):\n"
            . "URL: {$url}\n"
            . "Params: " . print_r($params, true) . "\n"
            . "HTTP Client Error: " . print_r($error, true) . "\n"
            . "Response: " . print_r($response, true) . "\n"
            . "-------------------------\n";
        
        $logPath = $_SERVER['DOCUMENT_ROOT'] . '/upload/sms_log.txt';
        error_log($logMessage, 3, $logPath);

        if ($response === false) {
            return false;
        }

        $json = json_decode($response, true);

        if ($json && $json['status'] === 'OK') {
            return $json['sms'][$phone]['sms_id'];
        }

        return false;
    }
}