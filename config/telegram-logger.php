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
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds
    |
    */
    'timeout' => 5,
];

