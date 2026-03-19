<?php

namespace SayidMuhammad\TelegramLogger;

use Illuminate\Log\LogManager;
use Monolog\Logger;
use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;
use SayidMuhammad\TelegramLogger\Handlers\TelegramHandler;
use SayidMuhammad\TelegramLogger\Services\TelegramService;

/**
 * Factory class for creating Telegram logger channel
 */
class CreateTelegramLogger
{
    /**
     * Create a custom logger instance
     *
     * @param array $config
     * @return \Illuminate\Log\Logger
     */
    public function __invoke(array $config)
    {
        $config = array_replace_recursive(config('telegram-logger', []), $config);

        // Check if logging is enabled
        if (!($config['enabled'] ?? true)) {
            return app(LogManager::class)->channel('null');
        }

        // Validate required configuration
        $botToken = $config['bot_token'] ?? null;
        $chatId   = $config['chat_id']   ?? null;

        if (empty($botToken) || empty($chatId)) {
            throw new \InvalidArgumentException(
                'Telegram Logger: bot_token and chat_id must be configured'
            );
        }

        $httpTimeout     = (int) ($config['timeout']    ?? 10);
        $rateLimitConfig = $config['rate_limit']         ?? [];
        $queueConfig     = $config['queue']              ?? [];

        // Create Telegram service (also used for synchronous fallback path)
        $telegramService = new TelegramService(
            $botToken,
            $httpTimeout,
            $rateLimitConfig,
        );

        // Parse log level
        $level = $this->parseLevel($config['level'] ?? 'error');

        // Create handler — raw credentials forwarded so the queued job can
        // reconstruct TelegramService on the worker process (Guzzle\Client
        // is not serializable and cannot be passed directly to a job payload).
        $handler = new TelegramHandler(
            $telegramService,
            $chatId,
            $config['topic_id']  ?? null,
            $level,
            true,
            $config['retry']     ?? [],
            $botToken,
            $httpTimeout,
            $rateLimitConfig,
            $queueConfig,
        );

        // Create formatter with options
        $formatter = new TelegramFormatter(
            $config['format'] ?? [],
            $config['emojis'] ?? []
        );

        $handler->setFormatter($formatter);

        // Create fresh logger instead of reusing shared instance
        $monolog = new \Monolog\Logger('telegram');
        $monolog->pushHandler($handler);

        return new \Illuminate\Log\Logger($monolog, app('events'));
    }

    /**
     * Parse log level into a Monolog compatible value
     *
     * @param string|int $level
     * @return mixed
     */
    private function parseLevel(string|int $level): mixed
    {
        if (class_exists(\Monolog\Level::class)) {
            if (is_int($level)) {
                return \Monolog\Level::fromValue($level);
            }

            $name = strtoupper($level);

            try {
                return \Monolog\Level::fromName($name);
            } catch (\ValueError) {
                return \Monolog\Level::Error;
            }
        }

        // Monolog 2 fallback (returns int)
        if (is_int($level)) {
            return $level;
        }

        return Logger::toMonologLevel($level);
    }
}
