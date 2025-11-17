<?php

namespace SayidMuhammad\TelegramLogger;

use Illuminate\Log\LogManager;
use Monolog\Level;
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
        $config = array_merge(config('telegram-logger', []), $config);

        // Check if logging is enabled
        if (!($config['enabled'] ?? true)) {
            return app(LogManager::class)->channel('null');
        }

        // Validate required configuration
        $botToken = $config['bot_token'] ?? null;
        $chatId = $config['chat_id'] ?? null;

        if (empty($botToken) || empty($chatId)) {
            throw new \InvalidArgumentException(
                'Telegram Logger: bot_token and chat_id must be configured'
            );
        }

        // Create Telegram service
        $telegramService = new TelegramService(
            $botToken,
            $config['timeout'] ?? 5
        );

        // Parse log level
        $level = $this->parseLevel($config['level'] ?? 'error');

        // Create handler
        $handler = new TelegramHandler(
            $telegramService,
            $chatId,
            $config['topic_id'] ?? null,
            $level,
            true,
            $config['retry'] ?? []
        );

        // Create formatter with options
        $formatter = new TelegramFormatter(
            $config['format'] ?? [],
            $config['emojis'] ?? []
        );

        $handler->setFormatter($formatter);

        // Create logger
        $logger = app(LogManager::class)->channel('single');
        $logger->pushHandler($handler);

        return $logger;
    }

    /**
     * Parse log level string to Monolog Level
     *
     * @param string|int $level
     * @return Level
     */
    private function parseLevel(string|int $level): Level
    {
        if (is_int($level)) {
            return Level::fromValue($level);
        }

        return match (strtolower($level)) {
            'debug' => Level::Debug,
            'info' => Level::Info,
            'notice' => Level::Notice,
            'warning' => Level::Warning,
            'error' => Level::Error,
            'critical' => Level::Critical,
            'alert' => Level::Alert,
            'emergency' => Level::Emergency,
            default => Level::Error,
        };
    }
}

