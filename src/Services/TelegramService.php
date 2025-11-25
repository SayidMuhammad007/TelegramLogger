<?php

namespace SayidMuhammad\TelegramLogger\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Service for sending messages to Telegram via Bot API
 */
class TelegramService
{
    /**
     * Telegram Bot API base URL
     */
    private const API_BASE_URL = 'https://api.telegram.org/bot';

    /**
     * HTTP client instance
     */
    private Client $client;

    /**
     * Bot token
     */
    private string $botToken;

    /**
     * Request timeout in seconds
     */
    private int $timeout;

    /**
     * Rate limiting configuration
     */
    private array $rateLimitConfig;

    /**
     * Create a new TelegramService instance
     *
     * @param string $botToken Telegram bot token
     * @param int $timeout Request timeout in seconds
     * @param array $rateLimitConfig Rate limiting configuration
     */
    public function __construct(string $botToken, int $timeout = 5, array $rateLimitConfig = [])
    {
        $this->botToken = $botToken;
        $this->timeout = $timeout;
        $this->rateLimitConfig = array_merge([
            'enabled' => true,
            'max_messages_per_second' => 20,
            'max_messages_per_minute' => 1000,
        ], $rateLimitConfig);
        
        $this->client = new Client([
            'base_uri' => self::API_BASE_URL . $botToken . '/',
            'timeout' => $timeout,
        ]);
    }

    /**
     * Send a message to Telegram chat
     *
     * @param string $chatId Chat ID or username
     * @param string $message Message text
     * @param int|null $topicId Optional topic ID for supergroups
     * @return bool Success status
     */
    public function sendMessage(string $chatId, string $message, ?int $topicId = null): bool
    {
        // Rate limiting check
        if ($this->rateLimitConfig['enabled'] && !$this->checkRateLimit()) {
            return false;
        }

        try {
            $payload = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ];

            if ($topicId !== null) {
                $payload['message_thread_id'] = $topicId;
            }

            $response = $this->client->post('sendMessage', [
                RequestOptions::JSON => $payload,
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['ok']) && $result['ok'] === true) {
                $this->recordMessageSent();
                return true;
            }

            return false;
        } catch (ClientException $e) {
            $statusCode = $e->getResponse()?->getStatusCode();
            $responseBody = $e->getResponse()?->getBody()?->getContents();
            $responseData = json_decode($responseBody, true);

            // Handle 429 Too Many Requests
            if ($statusCode === 429) {
                $retryAfter = $responseData['parameters']['retry_after'] ?? 3;
                $this->handleRateLimit($retryAfter);
                
                // Don't log 429 errors to prevent spam
                return false;
            }

            // Log other errors
            $this->logError($e);
            return false;
        } catch (GuzzleException $e) {
            // Check if it's a timeout error
            $errorMessage = $e->getMessage();
            if (str_contains($errorMessage, 'timed out') || str_contains($errorMessage, 'Operation timed out')) {
                // Store timeout error type for retry logic
                Cache::put('telegram_last_error_type', 'timeout', 10);
                // Timeout errors should be retried, don't log them immediately
                return false;
            }
            
            // Store other error types
            Cache::put('telegram_last_error_type', 'other', 10);
            $this->logError($e);
            return false;
        }
    }

    /**
     * Check rate limit before sending message
     *
     * @return bool
     */
    private function checkRateLimit(): bool
    {
        $now = time();
        $secondKey = "telegram_rate_limit_second_{$now}";
        $minuteStart = $now - ($now % 60);
        $minuteKey = "telegram_rate_limit_minute_{$minuteStart}";

        $messagesThisSecond = Cache::get($secondKey, 0);
        $messagesThisMinute = Cache::get($minuteKey, 0);

        if ($messagesThisSecond >= $this->rateLimitConfig['max_messages_per_second']) {
            return false;
        }

        if ($messagesThisMinute >= $this->rateLimitConfig['max_messages_per_minute']) {
            return false;
        }

        return true;
    }

    /**
     * Record that a message was sent (for rate limiting)
     *
     * @return void
     */
    private function recordMessageSent(): void
    {
        $now = time();
        $secondKey = "telegram_rate_limit_second_{$now}";
        $minuteStart = $now - ($now % 60);
        $minuteKey = "telegram_rate_limit_minute_{$minuteStart}";

        Cache::increment($secondKey, 1);
        Cache::put($secondKey, Cache::get($secondKey), 2); // Expire after 2 seconds

        Cache::increment($minuteKey, 1);
        Cache::put($minuteKey, Cache::get($minuteKey), 65); // Expire after 65 seconds
    }

    /**
     * Handle rate limit by setting a cooldown period
     *
     * @param int $retryAfter Seconds to wait
     * @return void
     */
    private function handleRateLimit(int $retryAfter): void
    {
        $cooldownKey = 'telegram_rate_limit_cooldown';
        Cache::put($cooldownKey, true, $retryAfter + 1);
    }

    /**
     * Log error without causing infinite loop
     *
     * @param GuzzleException $e
     * @return void
     */
    private function logError(GuzzleException $e): void
    {
        if (config('telegram-logger.enabled', true)) {
            // Temporarily disable to prevent infinite loop
            $originalEnabled = config('telegram-logger.enabled');
            config(['telegram-logger.enabled' => false]);
            
            Log::channel('single')->error('Telegram Logger: Failed to send message', [
                'error' => $e->getMessage(),
            ]);
            
            config(['telegram-logger.enabled' => $originalEnabled]);
        }
    }

    /**
     * Send a message with retry mechanism
     *
     * @param string $chatId Chat ID or username
     * @param string $message Message text
     * @param int|null $topicId Optional topic ID for supergroups
     * @param int $maxAttempts Maximum retry attempts
     * @param int $delay Delay between retries in milliseconds
     * @return bool Success status
     */
    public function sendMessageWithRetry(
        string $chatId,
        string $message,
        ?int $topicId = null,
        int $maxAttempts = 3,
        int $delay = 1000
    ): bool {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            // Check cooldown period
            $cooldownKey = 'telegram_rate_limit_cooldown';
            if (Cache::has($cooldownKey)) {
                $remaining = Cache::get($cooldownKey . '_ttl', 3);
                sleep($remaining);
            }

            $result = $this->sendMessage($chatId, $message, $topicId);
            
            if ($result) {
                return true;
            }

            $attempt++;

            if ($attempt < $maxAttempts) {
                // For timeout errors, wait longer before retry
                $isTimeout = $this->isLastErrorTimeout();
                $currentDelay = $isTimeout 
                    ? max($delay * 2, 2000) // At least 2 seconds for timeout
                    : $delay * pow(2, $attempt - 1); // Exponential backoff for other errors
                    
                usleep($currentDelay * 1000); // Convert milliseconds to microseconds
            }
        }

        return false;
    }

    /**
     * Check if the last error was a timeout
     *
     * @return bool
     */
    private function isLastErrorTimeout(): bool
    {
        $lastError = Cache::get('telegram_last_error_type', '');
        return $lastError === 'timeout';
    }
}

