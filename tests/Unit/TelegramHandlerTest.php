<?php

namespace SayidMuhammad\TelegramLogger\Tests\Unit;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;
use SayidMuhammad\TelegramLogger\Handlers\TelegramHandler;
use SayidMuhammad\TelegramLogger\Services\TelegramService;

class TelegramHandlerTest extends TestCase
{
    /**
     * Test handler creation
     */
    public function testHandlerCanBeCreated(): void
    {
        $telegramService = $this->createMock(TelegramService::class);
        $handler = new TelegramHandler(
            $telegramService,
            '123456789',
            null,
            Level::Error
        );

        $this->assertInstanceOf(TelegramHandler::class, $handler);
    }

    /**
     * Test handler with topic ID
     */
    public function testHandlerWithTopicId(): void
    {
        $telegramService = $this->createMock(TelegramService::class);
        $handler = new TelegramHandler(
            $telegramService,
            '123456789',
            123,
            Level::Error
        );

        $this->assertInstanceOf(TelegramHandler::class, $handler);
    }

    /**
     * Test handler with retry config
     */
    public function testHandlerWithRetryConfig(): void
    {
        $telegramService = $this->createMock(TelegramService::class);
        $handler = new TelegramHandler(
            $telegramService,
            '123456789',
            null,
            Level::Error,
            true,
            [
                'enabled' => true,
                'max_attempts' => 5,
                'delay' => 2000,
            ]
        );

        $this->assertInstanceOf(TelegramHandler::class, $handler);
    }

    /**
     * Test formatter is set by default
     */
    public function testFormatterIsSetByDefault(): void
    {
        $telegramService = $this->createMock(TelegramService::class);
        $handler = new TelegramHandler(
            $telegramService,
            '123456789'
        );

        $formatter = $handler->getFormatter();
        $this->assertInstanceOf(TelegramFormatter::class, $formatter);
    }

    /**
     * Test level filtering
     */
    public function testLevelFiltering(): void
    {
        $telegramService = $this->createMock(TelegramService::class);
        $handler = new TelegramHandler(
            $telegramService,
            '123456789',
            null,
            Level::Warning
        );

        $warningRecord = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Warning,
            'Warning message'
        );

        $errorRecord = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            'Error message'
        );

        $infoRecord = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Info,
            'Info message'
        );

        $this->assertTrue($handler->isHandling($warningRecord));
        $this->assertTrue($handler->isHandling($errorRecord));
        $this->assertFalse($handler->isHandling($infoRecord));
    }
}

