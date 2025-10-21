<?php

namespace App\Brakes\Helper;

use Bitrix\Main\FileTable;

class Image
{
    public const PRESET_CATALOG_TILE = 'catalog_tile';
    public const PRESET_PRODUCT_GALLERY_MAIN = 'product_gallery_main';
    public const PRESET_PRODUCT_GALLERY_THUMB = 'product_gallery_thumb';

    private const DEFAULT_FILTERS = [
        [
            'name' => 'sharpen',
            'precision' => 15,
        ],
    ];

    private const PRESETS = [
        self::PRESET_CATALOG_TILE => [
            'size' => [
                'width' => 400,
                'height' => 272,
            ],
            'resizeType' => BX_RESIZE_IMAGE_PROPORTIONAL,
            'initSizes' => true,
            'filters' => self::DEFAULT_FILTERS,
            'quality' => 80,
        ],
        self::PRESET_PRODUCT_GALLERY_MAIN => [
            'size' => [
                'width' => 1280,
                'height' => 960,
            ],
            'resizeType' => BX_RESIZE_IMAGE_PROPORTIONAL,
            'initSizes' => true,
            'filters' => self::DEFAULT_FILTERS,
            'quality' => 80,
        ],
        self::PRESET_PRODUCT_GALLERY_THUMB => [
            'size' => [
                'width' => 320,
                'height' => 240,
            ],
            'resizeType' => BX_RESIZE_IMAGE_PROPORTIONAL,
            'initSizes' => true,
            'filters' => self::DEFAULT_FILTERS,
            'quality' => 80,
        ],
    ];

    public static function resizeByPreset(mixed $file, string $preset = self::PRESET_CATALOG_TILE, array $options = []): ?array
    {
        $config = self::PRESETS[$preset] ?? null;
        if ($config === null) {
            $size = $options['size'] ?? null;
            if (!is_array($size)) {
                return null;
            }

            $resizeType = $options['resizeType'] ?? BX_RESIZE_IMAGE_PROPORTIONAL;

            return self::resize($file, $size, $resizeType, $options);
        }

        $size = $options['size'] ?? $config['size'];
        $resizeType = $options['resizeType'] ?? $config['resizeType'];
        $initSizes = $options['initSizes'] ?? $config['initSizes'];
        $filters = array_key_exists('filters', $options) ? $options['filters'] : $config['filters'];
        $immediate = $options['immediate'] ?? false;
        $quality = array_key_exists('quality', $options) ? $options['quality'] : $config['quality'];

        return self::resize(
            $file,
            $size,
            $resizeType,
            [
                'initSizes' => $initSizes,
                'filters' => $filters,
                'immediate' => $immediate,
                'quality' => $quality,
            ]
        );
    }

    public static function resize(mixed $file, array $size, string $resizeType = BX_RESIZE_IMAGE_PROPORTIONAL, array $options = []): ?array
    {
        if (!isset($size['width'], $size['height'])) {
            return null;
        }

        $fileArray = self::resolveFile($file);
        if ($fileArray === null) {
            return null;
        }

        $initSizes = $options['initSizes'] ?? true;
        $filters = $options['filters'] ?? self::DEFAULT_FILTERS;
        $immediate = $options['immediate'] ?? false;
        $quality = array_key_exists('quality', $options) ? $options['quality'] : false;

        $width = (int)$size['width'];
        $height = (int)$size['height'];

        if ($width <= 0 || $height <= 0) {
            return [
                'src' => $fileArray['SRC'] ?? null,
                'width' => (int)($fileArray['WIDTH'] ?? 0),
                'height' => (int)($fileArray['HEIGHT'] ?? 0),
                'original' => $fileArray['SRC'] ?? null,
                'cached' => false,
            ];
        }

        $result = \CFile::ResizeImageGet(
            $fileArray,
            [
                'width' => $width,
                'height' => $height,
            ],
            $resizeType,
            $initSizes,
            $filters,
            $immediate,
            $quality
        );

        $srcOriginal = $fileArray['SRC'] ?? null;
        $widthOriginal = (int)($fileArray['WIDTH'] ?? 0);
        $heightOriginal = (int)($fileArray['HEIGHT'] ?? 0);

        if (!is_array($result) || empty($result['src'])) {
            return [
                'src' => $srcOriginal,
                'width' => $widthOriginal,
                'height' => $heightOriginal,
                'original' => $srcOriginal,
                'cached' => false,
            ];
        }

        if ($initSizes && (int)($result['width'] ?? 0) === 0) {
            $result['width'] = $widthOriginal;
        }

        if ($initSizes && (int)($result['height'] ?? 0) === 0) {
            $result['height'] = $heightOriginal;
        }

        return [
            'src' => $result['src'],
            'width' => (int)($result['width'] ?? 0),
            'height' => (int)($result['height'] ?? 0),
            'original' => $srcOriginal,
            'cached' => $result['src'] !== $srcOriginal,
        ];
    }

    private static function resolveFile(mixed $file): ?array
    {
        if (is_array($file)) {
            if (isset($file['FILE_NAME'], $file['SUBDIR'])) {
                return $file;
            }

            if (isset($file['ID'])) {
                $resolved = \CFile::GetFileArray((int)$file['ID']);
                if (is_array($resolved)) {
                    return $resolved;
                }
            }

            if (isset($file['SRC'])) {
                return self::resolveFile($file['SRC']);
            }
        }

        if (is_numeric($file)) {
            $resolved = \CFile::GetFileArray((int)$file);
            if (is_array($resolved)) {
                return $resolved;
            }
        }

        if (is_string($file) && $file !== '') {
            $path = self::preparePath($file);
            if ($path === null) {
                return null;
            }

            $resolved = self::resolveByPath($path);
            if ($resolved !== null) {
                return $resolved;
            }
        }

        return null;
    }

    private static function resolveByPath(string $path): ?array
    {
        $relative = ltrim($path, '/');
        if (strncmp($relative, 'upload/', 7) !== 0) {
            return null;
        }

        $relative = substr($relative, strlen('upload/'));
        $separatorPosition = strrpos($relative, '/');
        if ($separatorPosition === false) {
            return null;
        }

        $subdir = substr($relative, 0, $separatorPosition);
        $fileName = substr($relative, $separatorPosition + 1);

        if ($fileName === '') {
            return null;
        }

        $row = FileTable::getList([
            'filter' => [
                '=SUBDIR' => $subdir,
                '=FILE_NAME' => $fileName,
            ],
            'limit' => 1,
        ])->fetch();

        if (!is_array($row)) {
            return null;
        }

        return \CFile::GetFileArray((int)$row['ID']);
    }

    private static function preparePath(string $path): ?string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return null;
        }

        $parsed = parse_url($trimmed, PHP_URL_PATH);
        if (is_string($parsed) && $parsed !== '') {
            $trimmed = $parsed;
        }

        if (strncmp($trimmed, '/', 1) !== 0) {
            $trimmed = '/' . $trimmed;
        }

        return $trimmed;
    }
}
