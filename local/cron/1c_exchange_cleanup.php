<?php

/**
 * Cleanup old 1C exchange folders in /upload.
 *
 * Safe defaults:
 * - dry-run unless --apply is passed;
 * - removes only /upload/1c_catalog<N> directories;
 * - never touches /upload/1c_catalog without numeric suffix;
 * - protects latest exchange directories remembered in Bitrix options.
 *
 * Usage:
 *   php local/cron/1c_exchange_cleanup.php --dry-run
 *   php local/cron/1c_exchange_cleanup.php --apply
 *   php local/cron/1c_exchange_cleanup.php --apply --days=30
 */

use Bitrix\Main\Config\Option;

function brakes_1c_exchange_cleanup_run(array $options = []): array
{
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = realpath(__DIR__ . '/../../');
        if ($docRoot === false || $docRoot === '') {
            $docRoot = dirname(__DIR__, 2);
        }
        $_SERVER['DOCUMENT_ROOT'] = (string)$docRoot;
    }

    if (!defined('B_PROLOG_INCLUDED')) {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    }

    $apply = (bool)($options['apply'] ?? false);
    $days = max(1, (int)($options['days'] ?? 30));
    $limit = max(0, (int)($options['limit'] ?? 0));
    $verbose = (bool)($options['verbose'] ?? false);
    $moduleId = 'brakes';
    $now = time();
    $threshold = $now - ($days * 86400);

    $uploadDir = (string)($options['uploadDir'] ?? ($_SERVER['DOCUMENT_ROOT'] . '/upload'));
    $uploadReal = realpath($uploadDir);
    if ($uploadReal === false || !is_dir($uploadReal)) {
        throw new RuntimeException('1c_exchange_cleanup: upload directory not found: ' . $uploadDir);
    }
    $uploadReal = rtrim(str_replace('\\', '/', $uploadReal), '/');

    $logDir = $_SERVER['DOCUMENT_ROOT'] . '/local/cron';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0775, true);
    }
    $lockPath = (string)($options['lockPath'] ?? ($logDir . '/1c_exchange_cleanup.lock'));
    $lockHandle = @fopen($lockPath, 'c+');
    if ($lockHandle === false) {
        throw new RuntimeException('1c_exchange_cleanup: failed to open lock file ' . $lockPath);
    }
    if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
        return [
            'status' => 'skipped_lock',
            'apply' => $apply,
            'days' => $days,
            'deletedDirs' => 0,
            'deletedBytes' => 0,
            'errors' => ['Cleanup is already running'],
        ];
    }

    $formatBytes = static function (int $bytes): string {
        $units = ['B', 'K', 'M', 'G', 'T'];
        $value = (float)$bytes;
        $unit = 0;
        while ($value >= 1024 && $unit < count($units) - 1) {
            $value /= 1024;
            $unit++;
        }
        return ($unit === 0 ? (string)$bytes : number_format($value, 1, '.', '')) . $units[$unit];
    };

    $isPathInside = static function (string $path, string $parent): bool {
        $path = rtrim(str_replace('\\', '/', $path), '/');
        $parent = rtrim(str_replace('\\', '/', $parent), '/');
        return $path === $parent || str_starts_with($path, $parent . '/');
    };

    $dirStats = static function (string $dir) use ($isPathInside): array {
        $dirReal = realpath($dir);
        if ($dirReal === false || !is_dir($dirReal)) {
            return ['bytes' => 0, 'files' => 0, 'dirs' => 0];
        }
        $bytes = 0;
        $files = 0;
        $dirs = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirReal, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if (!$isPathInside($path, $dirReal)) {
                continue;
            }
            if ($item->isLink()) {
                continue;
            }
            if ($item->isFile()) {
                $bytes += (int)$item->getSize();
                $files++;
            } elseif ($item->isDir()) {
                $dirs++;
            }
        }
        return ['bytes' => $bytes, 'files' => $files, 'dirs' => $dirs];
    };

    $removeDir = static function (string $dir, string $uploadRoot) use ($isPathInside): array {
        $dirReal = realpath($dir);
        if ($dirReal === false || !is_dir($dirReal) || is_link($dirReal)) {
            return ['ok' => false, 'error' => 'Directory not found or not a regular directory'];
        }
        $dirReal = rtrim(str_replace('\\', '/', $dirReal), '/');
        if (!$isPathInside($dirReal, $uploadRoot) || !preg_match('~/' . '1c_catalog\\d+$~', $dirReal)) {
            return ['ok' => false, 'error' => 'Unsafe directory path: ' . $dirReal];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirReal, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $path = $item->getPathname();
            if (!$isPathInside($path, $dirReal)) {
                return ['ok' => false, 'error' => 'Unsafe nested path: ' . $path];
            }
            if ($item->isLink() || $item->isFile()) {
                if (!@unlink($path)) {
                    return ['ok' => false, 'error' => 'Failed to delete file: ' . $path];
                }
                continue;
            }
            if ($item->isDir() && !@rmdir($path)) {
                return ['ok' => false, 'error' => 'Failed to delete directory: ' . $path];
            }
        }

        if (!@rmdir($dirReal)) {
            return ['ok' => false, 'error' => 'Failed to delete root directory: ' . $dirReal];
        }

        return ['ok' => true, 'error' => null];
    };

    $protectedDirs = [];
    if (class_exists(Option::class)) {
        foreach (['1c_parse_last_file', '1c_last_complete_file', '1c_last_rests_file'] as $optionName) {
            $file = (string)Option::get($moduleId, $optionName, '');
            if ($file === '') {
                continue;
            }
            $dir = dirname($file);
            $dirReal = realpath($dir);
            if ($dirReal === false) {
                continue;
            }
            $dirReal = rtrim(str_replace('\\', '/', $dirReal), '/');
            if ($isPathInside($dirReal, $uploadReal) && preg_match('~/1c_catalog\\d+$~', $dirReal)) {
                $protectedDirs[$dirReal] = $optionName;
            }
        }
    }

    $foundDirs = 0;
    $oldDirs = 0;
    $protectedSkipped = 0;
    $deletedDirs = 0;
    $candidateBytes = 0;
    $deletedBytes = 0;
    $candidateFiles = 0;
    $candidateDirs = [];
    $errors = [];

    $items = new DirectoryIterator($uploadReal);
    foreach ($items as $item) {
        if ($item->isDot() || !$item->isDir() || $item->isLink()) {
            continue;
        }

        $name = $item->getFilename();
        if (!preg_match('/^1c_catalog\\d+$/', $name)) {
            continue;
        }

        $foundDirs++;
        $dir = rtrim(str_replace('\\', '/', $item->getPathname()), '/');
        $dirReal = realpath($dir);
        if ($dirReal === false) {
            continue;
        }
        $dirReal = rtrim(str_replace('\\', '/', $dirReal), '/');
        if (!$isPathInside($dirReal, $uploadReal)) {
            $errors[] = 'Unsafe path skipped: ' . $dirReal;
            continue;
        }

        $mtime = (int)$item->getMTime();
        if ($mtime >= $threshold) {
            continue;
        }

        $oldDirs++;
        if (isset($protectedDirs[$dirReal])) {
            $protectedSkipped++;
            continue;
        }

        if ($limit > 0 && count($candidateDirs) >= $limit) {
            continue;
        }

        $stats = $dirStats($dirReal);
        $candidateBytes += (int)$stats['bytes'];
        $candidateFiles += (int)$stats['files'];
        $candidateDirs[] = [
            'path' => $dirReal,
            'name' => $name,
            'mtime' => date('c', $mtime),
            'ageDays' => (int)floor(($now - $mtime) / 86400),
            'bytes' => (int)$stats['bytes'],
            'files' => (int)$stats['files'],
            'dirs' => (int)$stats['dirs'],
            'size' => $formatBytes((int)$stats['bytes']),
        ];
    }

    usort(
        $candidateDirs,
        static function (array $a, array $b): int {
            if ($a['ageDays'] !== $b['ageDays']) {
                return $b['ageDays'] <=> $a['ageDays'];
            }
            return strcmp($a['name'], $b['name']);
        }
    );

    if ($apply) {
        foreach ($candidateDirs as $candidate) {
            $result = $removeDir((string)$candidate['path'], $uploadReal);
            if ($result['ok']) {
                $deletedDirs++;
                $deletedBytes += (int)$candidate['bytes'];
                continue;
            }
            $errors[] = $candidate['path'] . ': ' . $result['error'];
        }
    }

    $summary = [
        'status' => $errors ? 'completed_with_errors' : 'completed',
        'apply' => $apply,
        'days' => $days,
        'uploadDir' => $uploadReal,
        'foundDirs' => $foundDirs,
        'oldDirs' => $oldDirs,
        'protectedSkipped' => $protectedSkipped,
        'candidateDirs' => count($candidateDirs),
        'candidateFiles' => $candidateFiles,
        'candidateBytes' => $candidateBytes,
        'candidateSize' => $formatBytes($candidateBytes),
        'deletedDirs' => $deletedDirs,
        'deletedBytes' => $deletedBytes,
        'deletedSize' => $formatBytes($deletedBytes),
        'errors' => $errors,
    ];

    if ($verbose) {
        $summary['dirs'] = $candidateDirs;
        $summary['protectedDirs'] = $protectedDirs;
    }

    if (class_exists('CEventLog')) {
        CEventLog::Add([
            'SEVERITY' => $errors ? 'WARNING' : 'INFO',
            'AUDIT_TYPE_ID' => 'BRKS_1C_EXCHANGE_CLEANUP',
            'MODULE_ID' => 'brakes',
            'ITEM_ID' => '1c_exchange_cleanup',
            'DESCRIPTION' => json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    return $summary;
}

if (PHP_SAPI === 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) {
    $opts = getopt('', [
        'apply',
        'dry-run',
        'days::',
        'limit::',
        'verbose',
        'json',
        'help',
    ]);

    if (isset($opts['help'])) {
        echo "Usage:\n";
        echo "  php local/cron/1c_exchange_cleanup.php --dry-run [--days=30] [--verbose]\n";
        echo "  php local/cron/1c_exchange_cleanup.php --apply [--days=30]\n";
        exit(0);
    }

    $apply = isset($opts['apply']);
    $days = isset($opts['days']) ? (int)$opts['days'] : 30;
    $limit = isset($opts['limit']) ? (int)$opts['limit'] : 0;
    $verbose = isset($opts['verbose']);
    $json = isset($opts['json']);

    $result = brakes_1c_exchange_cleanup_run([
        'apply' => $apply,
        'days' => $days,
        'limit' => $limit,
        'verbose' => $verbose,
    ]);

    if ($json) {
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
        exit($result['errors'] ? 1 : 0);
    }

    echo 'mode=' . ($apply ? 'apply' : 'dry-run') . PHP_EOL;
    echo 'days=' . (int)$result['days'] . PHP_EOL;
    echo 'found_dirs=' . (int)$result['foundDirs'] . PHP_EOL;
    echo 'old_dirs=' . (int)$result['oldDirs'] . PHP_EOL;
    echo 'protected_skipped=' . (int)$result['protectedSkipped'] . PHP_EOL;
    echo 'candidate_dirs=' . (int)$result['candidateDirs'] . PHP_EOL;
    echo 'candidate_files=' . (int)$result['candidateFiles'] . PHP_EOL;
    echo 'candidate_size=' . $result['candidateSize'] . PHP_EOL;
    echo 'deleted_dirs=' . (int)$result['deletedDirs'] . PHP_EOL;
    echo 'deleted_size=' . $result['deletedSize'] . PHP_EOL;

    if ($verbose && !empty($result['dirs'])) {
        echo 'dirs:' . PHP_EOL;
        foreach ($result['dirs'] as $dir) {
            echo '  ' . $dir['size'] . ' age=' . $dir['ageDays'] . 'd ' . $dir['path'] . PHP_EOL;
        }
    }

    if (!empty($result['errors'])) {
        echo 'errors:' . PHP_EOL;
        foreach ($result['errors'] as $error) {
            echo '  ' . $error . PHP_EOL;
        }
        exit(1);
    }

    exit(0);
}
