<?php

namespace SayidMuhammad\TelegramLogger\Handlers;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use SayidMuhammad\TelegramLogger\Handlers\Concerns\SendsTelegramMessages;
use SayidMuhammad\TelegramLogger\Services\TelegramService;

if (class_exists(\Monolog\LogRecord::class)) {
    /**
     * Monolog 3 handler implementation (LogRecord API)
     */
    class TelegramHandler extends AbstractProcessingHandler
    {
        use SendsTelegramMessages;

        public function __construct(
            TelegramService $telegramService,
            string $chatId,
            ?int $topicId = null,
            mixed $level = Logger::ERROR,
            bool $bubble = true,
            array $retryConfig = []
        ) {
            parent::__construct($level, $bubble);

            $this->configureHandler($telegramService, $chatId, $topicId, $retryConfig);
        }

        protected function write(\Monolog\LogRecord $record): void
        {
            $message = $this->getFormatter()->format($record);
            $this->sendToTelegram($message);
        }
    }
} else {
    /**
     * Monolog 2 handler implementation (array record API)
     */
    class TelegramHandler extends AbstractProcessingHandler
    {
        use SendsTelegramMessages;

        public function __construct(
            TelegramService $telegramService,
            string $chatId,
            ?int $topicId = null,
            mixed $level = Logger::ERROR,
            bool $bubble = true,
            array $retryConfig = []
        ) {
            parent::__construct($level, $bubble);

            $this->configureHandler($telegramService, $chatId, $topicId, $retryConfig);
        }

        /**
         * @param array<string,mixed> $record
         */
        protected function write(array $record): void
        {
            $message = $this->getFormatter()->format($record);
            $this->sendToTelegram($message);
        }
    }
}
