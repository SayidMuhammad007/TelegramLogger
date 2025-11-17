<?php

namespace SayidMuhammad\TelegramLogger\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
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
     * Create a new TelegramService instance
     *
     * @param string $botToken Telegram bot token
     * @param int $timeout Request timeout in seconds
     */
    public function __construct(string $botToken, int $timeout = 5)
    {
        $this->botToken = $botToken;
        $this->timeout = $timeout;
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

            return isset($result['ok']) && $result['ok'] === true;
        } catch (GuzzleException $e) {
            // Log error but don't throw to prevent logging loop
            if (config('telegram-logger.enabled', true)) {
                // Temporarily disable to prevent infinite loop
                $originalEnabled = config('telegram-logger.enabled');
                config(['telegram-logger.enabled' => false]);
                
                Log::channel('single')->error('Telegram Logger: Failed to send message', [
                    'error' => $e->getMessage(),
                ]);
                
                config(['telegram-logger.enabled' => $originalEnabled]);
            }

            return false;
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
            if ($this->sendMessage($chatId, $message, $topicId)) {
                return true;
            }

            $attempt++;

            if ($attempt < $maxAttempts) {
                usleep($delay * 1000); // Convert milliseconds to microseconds
            }
        }

        return false;
    }
}

