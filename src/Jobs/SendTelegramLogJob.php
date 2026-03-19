<?php

namespace SayidMuhammad\TelegramLogger\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use SayidMuhammad\TelegramLogger\Services\TelegramService;
use Throwable;

class SendTelegramLogJob implements ShouldQueue, ShouldBeEncrypted
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * One attempt only — fire and forget.
     * If Telegram is down or times out, the job is simply discarded.
     */
    public int $tries = 1;

    /**
     * Must be greater than $httpTimeout so the worker never kills
     * a legitimate in-flight Guzzle request.
     */
    public int $timeout = 30;

    public function __construct(
        private readonly string $formattedMessage,
        private readonly string $botToken,
        private readonly string $chatId,
        private readonly ?int   $topicId,
        private readonly int    $httpTimeout,
        private readonly array  $rateLimitConfig,
    ) {}

    public function handle(): void
    {
        $service = new TelegramService(
            $this->botToken,
            $this->httpTimeout,
            $this->rateLimitConfig,
        );

        // Result is intentionally ignored — fire and forget.
        // If sendMessage() returns false or throws, the job simply ends.
        try {
            $service->sendMessage($this->chatId, $this->formattedMessage, $this->topicId);
        } catch (Throwable) {
            // Swallow silently. Telegram failures must never surface to the worker
            // as an error — no failed_jobs entry, no retry, no noise.
        }
    }
}
