<?php

namespace SayidMuhammad\TelegramLogger\Handlers\Concerns;

use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;
use SayidMuhammad\TelegramLogger\Jobs\SendTelegramLogJob;
use SayidMuhammad\TelegramLogger\Services\TelegramService;
use Throwable;

trait SendsTelegramMessages
{
    protected TelegramService $telegramService;

    protected string $chatId;

    protected ?int $topicId;

    protected array $retryConfig = [];

    // --- Raw credentials needed to construct TelegramService inside the queued job.
    //     Guzzle\Client is not serializable, so we cannot pass the service instance
    //     itself to a job; instead we store scalars and reconstruct on the worker. ---

    protected string $botToken = '';

    protected int $httpTimeout = 10;

    protected array $rateLimitConfig = [];

    protected array $queueConfig = [];

    protected function configureHandler(
        TelegramService $telegramService,
        string          $chatId,
        ?int            $topicId,
        array           $retryConfig,
        string          $botToken        = '',
        int             $httpTimeout     = 10,
        array           $rateLimitConfig = [],
        array           $queueConfig     = [],
    ): void {
        $this->telegramService = $telegramService;
        $this->chatId          = $chatId;
        $this->topicId         = $topicId;
        $this->retryConfig     = array_merge([
            'enabled'      => true,
            'max_attempts' => 3,
            'delay'        => 1000,
        ], $retryConfig);

        $this->botToken        = $botToken;
        $this->httpTimeout     = $httpTimeout;
        $this->rateLimitConfig = $rateLimitConfig;
        $this->queueConfig     = array_merge([
            'enabled'    => false,
            'connection' => null,    // null = use application default connection
            'queue'      => 'default',
        ], $queueConfig);

        $this->setFormatter(new TelegramFormatter());
    }

    protected function sendToTelegram(string $message): void
    {
        if ($this->shouldUseQueue()) {
            $this->dispatchToQueue($message);

            return;
        }

        $this->sendSynchronously($message);
    }

    /**
     * Returns true only when a real async queue driver is configured.
     *
     * When the resolved driver is "sync", dispatching a job just executes it
     * inline on the current process — identical to calling sendSynchronously()
     * but with overhead. We detect this and skip the queue path entirely.
     */
    private function shouldUseQueue(): bool
    {
        if (! ($this->queueConfig['enabled'] ?? false)) {
            return false;
        }

        $connection = $this->queueConfig['connection'] ?? config('queue.default');
        $driver     = config("queue.connections.{$connection}.driver", 'sync');

        return $driver !== 'sync';
    }

    /**
     * Dispatch the formatted message to the queue.
     *
     * Wrapped in try/catch: if the queue broker is down at dispatch time,
     * we fall back to a synchronous send so the log message is never silently lost.
     */
    private function dispatchToQueue(string $message): void
    {
        try {
            $job = new SendTelegramLogJob(
                formattedMessage: $message,
                botToken:         $this->botToken,
                chatId:           $this->chatId,
                topicId:          $this->topicId,
                httpTimeout:      $this->httpTimeout,
                rateLimitConfig:  $this->rateLimitConfig,
            );

            dispatch(
                $job->onConnection($this->queueConfig['connection'])
                    ->onQueue($this->queueConfig['queue'])
            );
        } catch (Throwable) {
            // Queue infrastructure failure — degrade gracefully to synchronous
            // delivery so the log entry is not dropped.
            $this->sendSynchronously($message);
        }
    }

    /**
     * Original synchronous path — preserved as-is for fallback and when queue is disabled.
     */
    private function sendSynchronously(string $message): void
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
