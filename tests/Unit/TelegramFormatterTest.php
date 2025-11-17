<?php

namespace SayidMuhammad\TelegramLogger\Tests\Unit;

use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use SayidMuhammad\TelegramLogger\Formatters\TelegramFormatter;

class TelegramFormatterTest extends TestCase
{
    /**
     * Test formatter creation
     */
    public function testFormatterCanBeCreated(): void
    {
        $formatter = new TelegramFormatter();
        $this->assertInstanceOf(TelegramFormatter::class, $formatter);
    }

    /**
     * Test message formatting
     */
    public function testMessageFormatting(): void
    {
        $formatter = new TelegramFormatter();
        $record = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            'Test message',
            ['key' => 'value']
        );

        $formatted = $formatter->format($record);
        $this->assertIsString($formatted);
        $this->assertStringContainsString('Test message', $formatted);
    }

    /**
     * Test emoji inclusion
     */
    public function testEmojiInclusion(): void
    {
        $formatter = new TelegramFormatter(['use_emojis' => true]);
        $record = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            'Test message'
        );

        $formatted = $formatter->format($record);
        $this->assertStringContainsString('❌', $formatted);
    }

    /**
     * Test context formatting
     */
    public function testContextFormatting(): void
    {
        $formatter = new TelegramFormatter(['include_context' => true]);
        $record = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            'Test message',
            ['user_id' => 123, 'action' => 'test']
        );

        $formatted = $formatter->format($record);
        $this->assertStringContainsString('Context:', $formatted);
        $this->assertStringContainsString('user_id', $formatted);
    }

    /**
     * Test message truncation
     */
    public function testMessageTruncation(): void
    {
        $formatter = new TelegramFormatter(['max_message_length' => 100]);
        $longMessage = str_repeat('A', 200);
        $record = new LogRecord(
            new \DateTimeImmutable(),
            'test',
            Level::Error,
            $longMessage
        );

        $formatted = $formatter->format($record);
        $this->assertLessThanOrEqual(100, mb_strlen($formatted));
        $this->assertStringContainsString('truncated', $formatted);
    }
}

