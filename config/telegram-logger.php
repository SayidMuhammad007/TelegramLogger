<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Token
    |--------------------------------------------------------------------------
    |
    | Your Telegram Bot API token from @BotFather
    |
    */
    'bot_token' => env('TELEGRAM_LOG_BOT_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Telegram Chat ID
    |--------------------------------------------------------------------------
    |
    | The chat ID where logs will be sent
    |
    */
    'chat_id' => env('TELEGRAM_LOG_CHAT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Telegram Topic ID (Optional)
    |--------------------------------------------------------------------------
    |
    | The topic ID within the chat (for supergroups with topics)
    |
    */
    'topic_id' => env('TELEGRAM_LOG_TOPIC_ID'),

    /*
    |--------------------------------------------------------------------------
    | Log Level
    |--------------------------------------------------------------------------
    |
    | Minimum log level to send to Telegram
    | Available: debug, info, notice, warning, error, critical, alert, emergency
    |
    */
    'level' => env('TELEGRAM_LOG_LEVEL', 'error'),

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable
    |--------------------------------------------------------------------------
    |
    | Enable or disable Telegram logging
    |
    */
    'enabled' => env('TELEGRAM_LOG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Formatting Options
    |--------------------------------------------------------------------------
    */
    'format' => [
        'include_date' => true,
        'include_level' => true,
        'include_context' => true,
        'include_extra' => false,
        'include_trace' => true, // For errors
        'max_message_length' => 4096, // Telegram limit
        'use_emojis' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Level Emojis
    |--------------------------------------------------------------------------
    */
    'emojis' => [
        'debug' => '🐛',
        'info' => 'ℹ️',
        'notice' => '📢',
        'warning' => '⚠️',
        'error' => '❌',
        'critical' => '🔥',
        'alert' => '🚨',
        'emergency' => '💥',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    */
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay' => 1000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Telegram API allows ~30 messages per second per bot.
    | This setting limits messages to prevent 429 errors.
    |
    */
    'rate_limit' => [
        'enabled' => true,
        'max_messages_per_second' => 20, // Conservative limit (Telegram allows 30)
        'max_messages_per_minute' => 1000, // Additional safety limit
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds
    | Increase this if you experience timeout errors
    |
    */
    'timeout' => env('TELEGRAM_LOG_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | When enabled, log messages are dispatched to a queue worker instead of
    | blocking the HTTP worker that generated the log entry. The php-fpm worker
    | returns in <1ms and the Telegram HTTP call happens on a separate process.
    |
    | connection: null = use the application default queue connection.
    | queue:      dedicated queue name (isolates telegram retries from app jobs).
    |
    | IMPORTANT: If the resolved queue driver is "sync", this setting is ignored
    | and the package falls back to synchronous delivery automatically — a sync
    | driver executes jobs inline on the current process, which is identical to
    | the original blocking behaviour.
    |
    | If queue dispatch itself fails (e.g. Redis is down), the package falls back
    | to a synchronous send so the log message is never silently dropped.
    |
    */
    'queue' => [
        'enabled'    => env('TELEGRAM_LOG_QUEUE_ENABLED', false),
        'connection' => env('TELEGRAM_LOG_QUEUE_CONNECTION', null),
        'queue'      => env('TELEGRAM_LOG_QUEUE_NAME', 'default'),
    ],
];

