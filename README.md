# Laravel Telegram Logger

[![Latest Version](https://img.shields.io/packagist/v/sayidmuhammad/laravel-telegram-logger.svg?style=flat-square)](https://packagist.org/packages/sayidmuhammad/laravel-telegram-logger)
[![License](https://img.shields.io/packagist/l/sayidmuhammad/laravel-telegram-logger.svg?style=flat-square)](https://packagist.org/packages/sayidmuhammad/laravel-telegram-logger)

Send Laravel logs to Telegram chat or topic via bot.. This package integrates seamlessly with Laravel's logging system and allows you to receive log notifications directly in your Telegram chats or group topics.

## Features

- ✅ **Multiple log levels support** - Filter logs by level (debug, info, warning, error, etc.)
- ✅ **Topic support** - Send logs to specific topics in supergroups
- ✅ **Emoji indicators** - Visual indicators for different log levels
- ✅ **Context data formatting** - Beautiful formatting for context and extra data
- ✅ **Stack trace for errors** - Automatic stack trace inclusion for errors
- ✅ **Message length handling** - Automatic truncation for long messages
- ✅ **Retry mechanism** - Automatic retry on failed requests
- ✅ **Easy configuration** - Simple setup via config file or environment variables
- ✅ **Error handling** - Prevents infinite loops when Telegram API fails

## Installation

Install the package via Composer:

```bash
composer require sayidmuhammad/laravel-telegram-logger
```

## Configuration

### 1. Create Telegram Bot

1. Open Telegram and search for [@BotFather](https://t.me/BotFather)
2. Send `/newbot` command and follow the instructions
3. Copy the bot token you receive

### 2. Get Chat ID

#### For Personal Chat:
1. Start a conversation with your bot
2. Send a message to your bot
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Find the `chat.id` value in the response

#### For Group/Supergroup:
1. Add your bot to the group
2. Make the bot an administrator (optional, but recommended)
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Find the `chat.id` value (it will be negative for groups)

#### For Topics (Supergroups with Topics):
1. Create a topic in your supergroup
2. Send a message to the topic
3. Visit: `https://api.telegram.org/bot<YOUR_BOT_TOKEN>/getUpdates`
4. Find the `message_thread_id` value in the response

### 3. Environment Variables

Add the following to your `.env` file:

```env
TELEGRAM_LOG_BOT_TOKEN=1234567890:ABCdefGHIjklMNOpqrsTUVwxyz
TELEGRAM_LOG_CHAT_ID=-1001234567890
TELEGRAM_LOG_TOPIC_ID=123
TELEGRAM_LOG_LEVEL=error
TELEGRAM_LOG_ENABLED=true
```

### 4. Publish Configuration (Optional)

If you want to customize the configuration, publish the config file:

```bash
php artisan vendor:publish --provider="SayidMuhammad\TelegramLogger\TelegramLoggerServiceProvider" --tag="telegram-logger-config"
```

## Usage

### Basic Setup

Add the Telegram channel to your `config/logging.php`:

```php
'channels' => [
    'telegram' => [
        'driver' => 'custom',
        'via' => SayidMuhammad\TelegramLogger\CreateTelegramLogger::class,
        'level' => env('TELEGRAM_LOG_LEVEL', 'error'),
    ],
    
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'telegram'],
    ],
],
```

### Using the Logger

#### Basic Usage

```php
use Illuminate\Support\Facades\Log;

// Send error to Telegram
Log::error('Something went wrong!');

// Send critical error with context
Log::channel('telegram')->critical('Critical error!', [
    'user_id' => 123,
    'action' => 'payment',
    'amount' => 100.50,
]);
```

#### Using Specific Channel

```php
// Send only to Telegram
Log::channel('telegram')->error('Error message');

// Send to both file and Telegram
Log::stack(['single', 'telegram'])->warning('Warning message');
```

#### With Exception

```php
try {
    // Your code
} catch (\Exception $e) {
    Log::channel('telegram')->error('Exception occurred', [
        'exception' => $e,
    ]);
}
```

## Configuration Options

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `TELEGRAM_LOG_BOT_TOKEN` | Telegram bot token | Required |
| `TELEGRAM_LOG_CHAT_ID` | Chat ID where logs will be sent | Required |
| `TELEGRAM_LOG_TOPIC_ID` | Topic ID (optional, for supergroups) | `null` |
| `TELEGRAM_LOG_LEVEL` | Minimum log level | `error` |
| `TELEGRAM_LOG_ENABLED` | Enable/disable logging | `true` |

### Config File Options

```php
return [
    // Bot configuration
    'bot_token' => env('TELEGRAM_LOG_BOT_TOKEN'),
    'chat_id' => env('TELEGRAM_LOG_CHAT_ID'),
    'topic_id' => env('TELEGRAM_LOG_TOPIC_ID'),
    
    // Logging configuration
    'level' => env('TELEGRAM_LOG_LEVEL', 'error'),
    'enabled' => env('TELEGRAM_LOG_ENABLED', true),
    
    // Formatting options
    'format' => [
        'include_date' => true,
        'include_level' => true,
        'include_context' => true,
        'include_trace' => true,
        'max_message_length' => 4096,
        'use_emojis' => true,
    ],
    
    // Emoji mapping
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
    
    // Retry configuration
    'retry' => [
        'enabled' => true,
        'max_attempts' => 3,
        'delay' => 1000, // milliseconds
    ],
    
    // Request timeout
    'timeout' => 5, // seconds
];
```

## Log Levels

The package supports all standard Monolog log levels:

- `debug` - Detailed debug information
- `info` - Informational messages
- `notice` - Normal but significant events
- `warning` - Warning messages
- `error` - Error messages
- `critical` - Critical conditions
- `alert` - Action must be taken immediately
- `emergency` - System is unusable

## Message Format

Messages are formatted with HTML and include:

- **Emoji indicator** - Visual indicator for log level
- **Level name** - Uppercase log level name
- **Date and time** - When the log was created
- **Message** - The actual log message
- **Context** - Additional context data (if provided)
- **Stack trace** - For errors, critical, alert, and emergency levels

Example output:

```
❌ ERROR
📅 2024-01-15 10:30:45

Something went wrong!

Context:
{
    "user_id": 123,
    "action": "payment"
}

Stack Trace:
#0 /path/to/file.php:123
#1 /path/to/file.php:456
```

## Advanced Usage

### Custom Configuration per Channel

You can create multiple Telegram channels with different configurations:

```php
'channels' => [
    'telegram-errors' => [
        'driver' => 'custom',
        'via' => SayidMuhammad\TelegramLogger\CreateTelegramLogger::class,
        'level' => 'error',
        'bot_token' => env('TELEGRAM_ERROR_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_ERROR_CHAT_ID'),
    ],
    
    'telegram-warnings' => [
        'driver' => 'custom',
        'via' => SayidMuhammad\TelegramLogger\CreateTelegramLogger::class,
        'level' => 'warning',
        'bot_token' => env('TELEGRAM_WARNING_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_WARNING_CHAT_ID'),
    ],
],
```

### Disable Logging Temporarily

```php
// In your code
config(['telegram-logger.enabled' => false]);
Log::error('This won\'t be sent to Telegram');
config(['telegram-logger.enabled' => true]);
```

## Testing

Run the test suite:

```bash
composer test
```

## Troubleshooting

### Logs are not being sent

1. **Check configuration**: Ensure `TELEGRAM_LOG_BOT_TOKEN` and `TELEGRAM_LOG_CHAT_ID` are set correctly
2. **Check bot permissions**: Make sure the bot is added to the chat/group
3. **Check log level**: Verify the log level matches your configuration
4. **Check enabled status**: Ensure `TELEGRAM_LOG_ENABLED` is set to `true`
5. **Check Laravel logs**: Look for errors in `storage/logs/laravel.log`

### Bot token is invalid

- Verify the token with BotFather
- Ensure there are no extra spaces in the token
- Check if the bot is still active

### Chat ID not working

- For groups, ensure the chat ID is negative (starts with `-`)
- Make sure the bot is a member of the group
- For topics, verify the `message_thread_id` is correct

### Message too long

- The package automatically truncates messages longer than 4096 characters
- Adjust `max_message_length` in config if needed (but Telegram limit is 4096)

## Security Considerations

- **Never commit your bot token** to version control
- Use environment variables for sensitive data
- Consider using different bots for different environments
- Regularly rotate your bot tokens

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/sayidmuhammad/laravel-telegram-logger).

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

