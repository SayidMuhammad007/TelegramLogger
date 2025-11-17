<?php

namespace SayidMuhammad\TelegramLogger\Handlers\Concerns;

use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;
use SayidMuhammad\TelegramLogger\Services\TelegramService;

trait SendsTelegramMessages
{
    protected TelegramService $telegramService;

    protected string $chatId;

    protected ?int $topicId;

    protected array $retryConfig = [];

    protected function configureHandler(
        TelegramService $telegramService,
        string $chatId,
        ?int $topicId,
        array $retryConfig
    ): void {
        $this->telegramService = $telegramService;
        $this->chatId = $chatId;
        $this->topicId = $topicId;
        $this->retryConfig = array_merge([
            'enabled' => true,
            'max_attempts' => 3,
            'delay' => 1000,
        ], $retryConfig);

        $this->setFormatter(new TelegramFormatter());
    }

    protected function sendToTelegram(string $message): void
    {
        if ($this->retryConfig['enabled']) {
            $this->telegramService->sendMessageWithRetry(
                $this->chatId,
                $message,
                $this->topicId,
                $this->retryConfig['max_attempts'],
                $this->retryConfig['delay']
            );

            return;
        }

        $this->telegramService->sendMessage($this->chatId, $message, $this->topicId);
    }
}

