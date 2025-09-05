<?php

namespace App\Brakes\Helper;

class Storage
{
    private static array $data = [];

    public static function set(string $key, mixed $data): void
    {
        self::$data[$key] = $data;
    }

    public static function get(string $key): mixed
    {
        return self::$data[$key];
    }
}
