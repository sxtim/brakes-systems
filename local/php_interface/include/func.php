<?php

function getPreviewImgCatalog(?string $photos): ?string
{
    if ($photos === null || $photos === '') {
        return null;
    }

    $valArr = explode(';', $photos);

    if (!empty($valArr[0])) {
        return $valArr[0];
    }

    return null;
}

if (!function_exists('brakes_contact_include_path')) {
    function brakes_contact_include_path(string $name): string
    {
        $name = preg_replace('/[^a-z0-9_-]/i', '', $name) ?: '';
        return SITE_DIR . 'include/contacts/' . $name . '.php';
    }
}

if (!function_exists('brakes_contact_include_text')) {
    function brakes_contact_include_text(string $path, string $fallback = ''): string
    {
        $fullPath = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/' . ltrim($path, '/');
        if (!is_file($fullPath)) {
            return $fallback;
        }

        $content = (string)file_get_contents($fullPath);
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\s+/u', ' ', $content) ?? $content;
        $content = trim($content);

        return $content !== '' ? $content : $fallback;
    }
}

if (!function_exists('brakes_contact_phone_href')) {
    function brakes_contact_phone_href(string $phone): string
    {
        $phone = trim($phone);
        $href = preg_replace('/[^\d+]/', '', $phone) ?? '';

        return $href !== '' ? 'tel:' . $href : '#';
    }
}

if (!function_exists('brakes_contact_email_href')) {
    function brakes_contact_email_href(string $email): string
    {
        $email = trim($email);

        return $email !== '' ? 'mailto:' . $email : '#';
    }
}

if (!function_exists('brakes_contact_include_area')) {
    function brakes_contact_include_area(string $path, string $fallback = ''): void
    {
        global $APPLICATION;

        if (is_object($APPLICATION)) {
            $APPLICATION->IncludeComponent(
                'bitrix:main.include',
                '',
                [
                    'AREA_FILE_SHOW' => 'file',
                    'PATH' => $path,
                    'EDIT_TEMPLATE' => '',
                ],
                false
            );
            return;
        }

        echo htmlspecialcharsbx($fallback);
    }
}
