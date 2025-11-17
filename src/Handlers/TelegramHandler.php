<?php

namespace SayidMuhammad\TelegramLogger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;
use SayidMuhammad\TelegramLogger\Services\TelegramService;

/**
 * Monolog handler for sending logs to Telegram
 */
class TelegramHandler extends AbstractProcessingHandler
{
    /**
     * Telegram service instance
     */
    private TelegramService $telegramService;

    /**
     * Chat ID
     */
    private string $chatId;

    /**
     * Topic ID (optional)
     */
    private ?int $topicId;

    /**
     * Retry configuration
     */
    private array $retryConfig;

    /**
     * Create a new TelegramHandler instance
     *
     * @param TelegramService $telegramService Telegram service instance
     * @param string $chatId Chat ID
     * @param int|null $topicId Optional topic ID
     * @param int|string $level Minimum log level
     * @param bool $bubble Whether messages bubble up the stack
     * @param array $retryConfig Retry configuration
     */
    public function __construct(
        TelegramService $telegramService,
        string $chatId,
        ?int $topicId = null,
        int|string $level = \Monolog\Level::Error,
        bool $bubble = true,
        array $retryConfig = []
    ) {
        parent::__construct($level, $bubble);

        $this->telegramService = $telegramService;
        $this->chatId = $chatId;
        $this->topicId = $topicId;
        $this->retryConfig = array_merge([
            'enabled' => true,
            'max_attempts' => 3,
            'delay' => 1000,
        ], $retryConfig);

        // Set default formatter
        $this->setFormatter(new TelegramFormatter());
    }

    /**
     * Write the log record to Telegram
     *
     * @param LogRecord $record
     * @return void
     */
    protected function write(LogRecord $record): void
    {
        $message = $this->getFormatter()->format($record);

        if ($this->retryConfig['enabled']) {
            $this->telegramService->sendMessageWithRetry(
                $this->chatId,
                $message,
                $this->topicId,
                $this->retryConfig['max_attempts'],
                $this->retryConfig['delay']
            );
        } else {
            $this->telegramService->sendMessage($this->chatId, $message, $this->topicId);
        }
    }
}

