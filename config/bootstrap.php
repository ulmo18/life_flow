<?php

$rootPath = dirname(__DIR__);

$logDirectory = $rootPath . '/storage/logs';

if (!is_dir($logDirectory)) {
    @mkdir($logDirectory, 0755, true);
}

ini_set('error_log', $logDirectory . '/php-error.log');

// 화면에 출력되지 않는 Fatal Error도 기록
register_shutdown_function(static function () use ($logDirectory): void {
    $error = error_get_last();

    if ($error !== null) {
        $message = sprintf(
            "[%s] %s in %s:%d\n",
            date('Y-m-d H:i:s'),
            $error['message'],
            $error['file'],
            $error['line']
        );

        @file_put_contents(
            $logDirectory . '/fatal-error.log',
            $message,
            FILE_APPEND
        );
    }
});

$envFile = $rootPath . '/.env';

if (!is_file($envFile)) {
    throw new RuntimeException('.env 파일을 찾을 수 없습니다: ' . $envFile);
}

if (!is_readable($envFile)) {
    throw new RuntimeException('.env 파일을 읽을 수 없습니다: ' . $envFile);
}

$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($lines === false) {
    throw new RuntimeException('.env 파일 읽기에 실패했습니다.');
}

foreach ($lines as $line) {
    $line = trim($line);

    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }

    if (str_starts_with($line, 'export ')) {
        $line = substr($line, 7);
    }

    if (!str_contains($line, '=')) {
        continue;
    }

    [$key, $value] = explode('=', $line, 2);

    $key = trim($key);
    $value = trim($value);

    // 따옴표 제거
    if (
        strlen($value) >= 2 &&
        (
            ($value[0] === '"' && $value[-1] === '"') ||
            ($value[0] === "'" && $value[-1] === "'")
        )
    ) {
        $value = substr($value, 1, -1);
    }

    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;

    // Cafe24에서 putenv가 차단되어 있을 수도 있으므로 보조적으로만 사용
    if (function_exists('putenv')) {
        @putenv($key . '=' . $value);
    }
}