<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (empty($_SERVER['DOCUMENT_ROOT'])) {
    $root = realpath(__DIR__ . '/../../');
    if ($root === false) {
        $root = realpath(getcwd());
    }
    if ($root === false) {
        $root = dirname(__DIR__, 2);
    }
    $_SERVER['DOCUMENT_ROOT'] = (string)$root;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

function brakes_1c_parse_runner_log(string $severity, string $message, array $context = []): void
{
    if (function_exists('brakes_1c_parse_log_event')) {
        brakes_1c_parse_log_event($severity, $message, $context);
        return;
    }

    if (!class_exists('CEventLog')) {
        return;
    }

    $description = $message;
    if ($context) {
        $description .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    CEventLog::Add([
        'SEVERITY' => $severity,
        'AUDIT_TYPE_ID' => 'BRKS_1C_PARSE',
        'MODULE_ID' => 'brakes',
        'ITEM_ID' => '1c_catalog_parse',
        'DESCRIPTION' => $description,
    ]);
}

function brakes_1c_parse_runner_lock_is_free(string $lockName): ?bool
{
    try {
        $connection = Application::getConnection();
        $dbType = method_exists($connection, 'getType') ? (string)$connection->getType() : '';
        if (!in_array($dbType, ['mysql', 'mysqli'], true)) {
            return null;
        }

        $helper = $connection->getSqlHelper();
        $safeLock = $helper->forSql($lockName);
        $gotLock = ((int)$connection->queryScalar("SELECT GET_LOCK('{$safeLock}', 0)") === 1);
        if ($gotLock) {
            $connection->queryExecute("SELECT RELEASE_LOCK('{$safeLock}')");
            return true;
        }

        return false;
    } catch (\Throwable $exception) {
        brakes_1c_parse_runner_log('WARNING', '1c catalog parse runner could not check db lock', [
            'error' => $exception->getMessage(),
        ]);
        return null;
    }
}

function brakes_1c_parse_runner_option(string $name, string $default = ''): string
{
    return (string)Option::get('brakes', $name, $default);
}

function brakes_1c_parse_runner_state(): array
{
    return [
        'pending' => brakes_1c_parse_runner_option('1c_parse_pending', 'N'),
        'running' => brakes_1c_parse_runner_option('1c_parse_running', 'N'),
        'lastTs' => (int)brakes_1c_parse_runner_option('1c_parse_last_ts', '0'),
        'lastSource' => brakes_1c_parse_runner_option('1c_parse_last_source', ''),
        'lastFile' => brakes_1c_parse_runner_option('1c_parse_last_file', ''),
        'lastCompleteTs' => (int)brakes_1c_parse_runner_option('1c_last_complete_ts', '0'),
        'lastCompleteFile' => brakes_1c_parse_runner_option('1c_last_complete_file', ''),
        'lastRunFile' => brakes_1c_parse_runner_option('1c_parse_last_run_file', ''),
        'lastRunFinishTs' => (int)brakes_1c_parse_runner_option('1c_parse_last_run_finish_ts', '0'),
        'lastFinishTs' => (int)brakes_1c_parse_runner_option('1c_parse_last_finish', '0'),
        'runningTs' => (int)brakes_1c_parse_runner_option('1c_parse_running_ts', '0'),
        'lastRunStartedTs' => (int)brakes_1c_parse_runner_option('1c_parse_last_run_started_ts', '0'),
    ];
}

function brakes_1c_parse_runner_print(array $result, int $exitCode = 0): void
{
    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
    exit($exitCode);
}

$cliOptions = getopt('', [
    'dry-run',
    'force',
    'stale-seconds:',
    'fallback-seconds:',
]);

$dryRun = array_key_exists('dry-run', $cliOptions);
$force = array_key_exists('force', $cliOptions);
$staleSeconds = max(300, (int)($cliOptions['stale-seconds'] ?? 3600));
$fallbackSeconds = max(600, (int)($cliOptions['fallback-seconds'] ?? 3600));
$now = time();

$lockDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/tmp';
if (!is_dir($lockDir)) {
    @mkdir($lockDir, 0775, true);
}
$runnerLockPath = $lockDir . '/brakes_1c_catalog_parse_runner.lock';
$runnerLock = @fopen($runnerLockPath, 'c');
if (!$runnerLock || !flock($runnerLock, LOCK_EX | LOCK_NB)) {
    brakes_1c_parse_runner_print([
        'status' => 'skipped_runner_lock',
        'message' => 'Another runner is already active',
    ]);
}

$state = brakes_1c_parse_runner_state();

if (!$force && $state['pending'] !== 'Y') {
    brakes_1c_parse_runner_print([
        'status' => 'skipped_no_pending',
        'pending' => $state['pending'],
    ]);
}

$readyReason = '';
if ($force) {
    $readyReason = 'force';
} elseif ($state['lastSource'] === 'OnCompleteCatalogImport1C') {
    $readyReason = 'complete';
} elseif (
    $state['lastFile'] !== ''
    && $state['lastCompleteFile'] === $state['lastFile']
    && $state['lastCompleteTs'] >= $state['lastTs']
) {
    $readyReason = 'complete_file';
} elseif ($state['lastTs'] > 0 && ($now - $state['lastTs']) >= $fallbackSeconds) {
    $readyReason = 'fallback_timeout';
}

if ($readyReason === '') {
    brakes_1c_parse_runner_print([
        'status' => 'waiting_complete',
        'pending' => $state['pending'],
        'lastSource' => $state['lastSource'],
        'lastFile' => $state['lastFile'],
        'age' => $state['lastTs'] > 0 ? $now - $state['lastTs'] : null,
        'fallbackSeconds' => $fallbackSeconds,
    ]);
}

if (!$force
    && $state['lastFile'] !== ''
    && $state['lastRunFile'] === $state['lastFile']
    && $state['lastRunFinishTs'] > 0
    && ($now - $state['lastRunFinishTs']) < 900
) {
    if (!$dryRun) {
        Option::set('brakes', '1c_parse_pending', 'N');
    }
    brakes_1c_parse_runner_print([
        'status' => 'skipped_duplicate',
        'file' => $state['lastFile'],
        'lastRunFinishTs' => $state['lastRunFinishTs'],
    ]);
}

if ($state['running'] === 'Y') {
    $runningStartedTs = $state['runningTs'] ?: $state['lastRunStartedTs'] ?: $state['lastFinishTs'];
    $runningAge = $runningStartedTs > 0 ? $now - $runningStartedTs : null;
    if ($runningAge !== null && $runningAge < $staleSeconds) {
        brakes_1c_parse_runner_print([
            'status' => 'skipped_running',
            'runningAge' => $runningAge,
            'staleSeconds' => $staleSeconds,
        ]);
    }

    $lockFree = brakes_1c_parse_runner_lock_is_free('brakes_1c_catalog_parse');
    if ($lockFree === false) {
        brakes_1c_parse_runner_print([
            'status' => 'skipped_running_db_lock_busy',
            'runningAge' => $runningAge,
            'staleSeconds' => $staleSeconds,
        ]);
    }

    if ($dryRun) {
        brakes_1c_parse_runner_print([
            'status' => 'would_reset_stale_running',
            'runningAge' => $runningAge,
            'staleSeconds' => $staleSeconds,
            'lockFree' => $lockFree,
            'readyReason' => $readyReason,
        ]);
    }

    brakes_1c_parse_runner_log('WARNING', '1c catalog parse runner reset stale running flag', [
        'runningAge' => $runningAge,
        'staleSeconds' => $staleSeconds,
        'lockFree' => $lockFree,
        'lastFile' => $state['lastFile'],
    ]);
    Option::set('brakes', '1c_parse_running', 'N');
    Option::set('brakes', '1c_parse_stale_reset_ts', (string)$now);
    $state['running'] = 'N';
}

if ($dryRun) {
    brakes_1c_parse_runner_print([
        'status' => 'would_run',
        'readyReason' => $readyReason,
        'state' => $state,
    ]);
}

$runFile = $state['lastFile'];
$startedTs = time();
$parserOptions = [
    'iblockId' => 1,
    'reactivateSections' => true,
    'reactivateElements' => false,
    'logPath' => $_SERVER['DOCUMENT_ROOT'] . '/local/cron/parse.log',
    'logPrefix' => $readyReason === 'fallback_timeout' ? 'CronRunnerFallback' : 'CronRunner',
];

Option::set('brakes', '1c_parse_running', 'Y');
Option::set('brakes', '1c_parse_running_ts', (string)$startedTs);
Option::set('brakes', '1c_parse_running_pid', (string)getmypid());
Option::set('brakes', '1c_parse_last_run_started_ts', (string)$startedTs);
Option::set('brakes', '1c_parse_last_run_source', $readyReason);
Option::set('brakes', '1c_parse_last_error', '');

brakes_1c_parse_runner_log('INFO', '1c catalog parse runner started', [
    'readyReason' => $readyReason,
    'file' => $runFile,
    'options' => $parserOptions,
]);

try {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/local/cron/1c_catalog_parse.php';
    if (!function_exists('brakes_1c_catalog_parse_run')) {
        throw new \RuntimeException('brakes_1c_catalog_parse_run not found');
    }

    $result = brakes_1c_catalog_parse_run($parserOptions);
    $finishTs = time();
    $status = (string)($result['status'] ?? 'finished');

    Option::set('brakes', '1c_parse_running', 'N');
    Option::set('brakes', '1c_parse_last_finish', (string)$finishTs);
    if ($runFile !== '') {
        Option::set('brakes', '1c_parse_last_run_file', $runFile);
        Option::set('brakes', '1c_parse_last_run_finish_ts', (string)$finishTs);
    }

    if ($status === 'skipped_lock') {
        Option::set('brakes', '1c_parse_pending', 'Y');
    } else {
        $currentLastTs = (int)Option::get('brakes', '1c_parse_last_ts', '0');
        Option::set('brakes', '1c_parse_pending', $currentLastTs > $state['lastTs'] ? 'Y' : 'N');
    }

    brakes_1c_parse_runner_log('INFO', '1c catalog parse runner finished', [
        'readyReason' => $readyReason,
        'file' => $runFile,
        'duration' => $finishTs - $startedTs,
        'result' => $result,
    ]);

    brakes_1c_parse_runner_print([
        'status' => $status,
        'readyReason' => $readyReason,
        'duration' => $finishTs - $startedTs,
        'result' => $result,
    ]);
} catch (\Throwable $exception) {
    $finishTs = time();
    Option::set('brakes', '1c_parse_running', 'N');
    Option::set('brakes', '1c_parse_last_finish', (string)$finishTs);
    Option::set('brakes', '1c_parse_pending', 'Y');
    Option::set('brakes', '1c_parse_last_error', $exception->getMessage());

    brakes_1c_parse_runner_log('ERROR', '1c catalog parse runner failed', [
        'readyReason' => $readyReason,
        'file' => $runFile,
        'duration' => $finishTs - $startedTs,
        'error' => $exception->getMessage(),
    ]);

    brakes_1c_parse_runner_print([
        'status' => 'error',
        'readyReason' => $readyReason,
        'duration' => $finishTs - $startedTs,
        'error' => $exception->getMessage(),
    ], 1);
}
