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
            string          $chatId,
            ?int            $topicId         = null,
            mixed           $level           = Logger::ERROR,
            bool            $bubble          = true,
            array           $retryConfig     = [],
            string          $botToken        = '',
            int             $httpTimeout     = 10,
            array           $rateLimitConfig = [],
            array           $queueConfig     = [],
        ) {
            parent::__construct($level, $bubble);

            $this->configureHandler(
                $telegramService,
                $chatId,
                $topicId,
                $retryConfig,
                $botToken,
                $httpTimeout,
                $rateLimitConfig,
                $queueConfig,
            );
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
            string          $chatId,
            ?int            $topicId         = null,
            mixed           $level           = Logger::ERROR,
            bool            $bubble          = true,
            array           $retryConfig     = [],
            string          $botToken        = '',
            int             $httpTimeout     = 10,
            array           $rateLimitConfig = [],
            array           $queueConfig     = [],
        ) {
            parent::__construct($level, $bubble);

            $this->configureHandler(
                $telegramService,
                $chatId,
                $topicId,
                $retryConfig,
                $botToken,
                $httpTimeout,
                $rateLimitConfig,
                $queueConfig,
            );
        }

        /**
         * Monolog 2 uses array records. This signature is intentionally
         * incompatible with the Monolog 3 AbstractProcessingHandler because
         * this entire else-block is only ever loaded when Monolog 3 is absent.
         *
         * @param array<string,mixed> $record
         * @phpstan-ignore-next-line
         */
        protected function write($record): void  // @phpcs:ignore
        {
            $message = $this->getFormatter()->format($record);
            $this->sendToTelegram($message);
        }
    }
}
