<?php

class Logger
{
    private static string $logDir = __DIR__ . '/../logs/';

    public static function log(string $level, string $message, array $context = []): void
    {
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }

        $now = new DateTime();
        $date = $now->format('Y-m-d H:i:s.v');

        $contextStr = '';
        if (!empty($context)) {
            $contextStr = ' | ' . json_encode($context);
        }

        $line = "[$date] [$level] $message$contextStr" . PHP_EOL;

        file_put_contents(self::$logDir . 'app.log', $line, FILE_APPEND);
    }

    public static function debug(string $message, array $context = []): void
    {
        if (defined('DEBUG') && DEBUG) {
            self::log('DEBUG', $message, $context);
        }
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }
}
