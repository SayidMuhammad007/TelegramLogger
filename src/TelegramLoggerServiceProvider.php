<?php

namespace SayidMuhammad\TelegramLogger;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for Telegram Logger package
 */
class TelegramLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/telegram-logger.php',
            'telegram-logger'
        );
    }

    /**
     * Bootstrap services
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish configuration file
        $this->publishes([
            __DIR__ . '/../config/telegram-logger.php' => config_path('telegram-logger.php'),
        ], 'telegram-logger-config');
    }
}

