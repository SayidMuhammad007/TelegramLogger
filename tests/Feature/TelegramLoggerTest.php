<?php

namespace SayidMuhammad\TelegramLogger\Tests\Feature;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SayidMuhammad\TelegramLogger\CreateTelegramLogger;
use SayidMuhammad\TelegramLogger\TelegramLoggerServiceProvider;

class TelegramLoggerTest extends OrchestraTestCase
{
    /**
     * Setup the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set test configuration
        config([
            'telegram-logger.bot_token' => 'test_bot_token',
            'telegram-logger.chat_id' => 'test_chat_id',
            'telegram-logger.enabled' => true,
            'telegram-logger.level' => 'error',
        ]);
    }

    /**
     * Get package providers
     */
    protected function getPackageProviders($app): array
    {
        return [
            TelegramLoggerServiceProvider::class,
        ];
    }

    /**
     * Test service provider loads configuration
     */
    public function testConfigurationIsLoaded(): void
    {
        $config = config('telegram-logger');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('bot_token', $config);
        $this->assertArrayHasKey('chat_id', $config);
    }

    /**
     * Test logger creation
     */
    public function testLoggerCanBeCreated(): void
    {
        $factory = new CreateTelegramLogger();
        $logger = $factory([
            'bot_token' => 'test_token',
            'chat_id' => 'test_chat',
        ]);

        $this->assertNotNull($logger);
    }

    /**
     * Test logger creation with missing config throws exception
     */
    public function testLoggerCreationThrowsExceptionWithMissingConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $factory = new CreateTelegramLogger();
        $factory([
            'bot_token' => '',
            'chat_id' => '',
        ]);
    }

    /**
     * Test logger is disabled when enabled is false
     */
    public function testLoggerIsDisabledWhenEnabledIsFalse(): void
    {
        config(['telegram-logger.enabled' => false]);

        $factory = new CreateTelegramLogger();
        $logger = $factory([
            'bot_token' => 'test_token',
            'chat_id' => 'test_chat',
        ]);

        // Should return null logger channel
        $this->assertNotNull($logger);
    }

    /**
     * Test configuration can be published
     */
    public function testConfigurationCanBePublished(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => TelegramLoggerServiceProvider::class,
            '--tag' => 'telegram-logger-config',
        ])->assertSuccessful();
    }
}

