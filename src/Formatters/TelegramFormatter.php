<?php

namespace SayidMuhammad\TelegramLogger\Formatters;

use Monolog\LogRecord;
use Monolog\Formatter\FormatterInterface;

/**
 * Formatter for Telegram messages
 */
class TelegramFormatter implements FormatterInterface
{
    /**
     * Formatting options
     */
    private array $options;

    /**
     * Level emojis mapping
     */
    private array $emojis;

    /**
     * Create a new TelegramFormatter instance
     *
     * @param array $options Formatting options
     * @param array $emojis Level emojis mapping
     */
    public function __construct(array $options = [], array $emojis = [])
    {
        $this->options = array_merge([
            'include_date' => true,
            'include_level' => true,
            'include_context' => true,
            'include_trace' => true,
            'max_message_length' => 4096,
            'use_emojis' => true,
        ], $options);

        $this->emojis = array_merge([
            'debug' => '🐛',
            'info' => 'ℹ️',
            'notice' => '📢',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🔥',
            'alert' => '🚨',
            'emergency' => '💥',
        ], $emojis);
    }

    /**
     * Formats a log record
     *
     * @param LogRecord $record
     * @return string
     */
    public function format(LogRecord $record): string
    {
        $parts = [];

        // Add emoji and level
        if ($this->options['use_emojis'] && $this->options['include_level']) {
            $levelName = strtolower($record->level->getName());
            $emoji = $this->emojis[$levelName] ?? '';
            $parts[] = $emoji . ' <b>' . strtoupper($levelName) . '</b>';
        } elseif ($this->options['include_level']) {
            $parts[] = '<b>' . strtoupper($record->level->getName()) . '</b>';
        }

        // Add date
        if ($this->options['include_date']) {
            $parts[] = '📅 ' . $record->datetime->format('Y-m-d H:i:s');
        }

        // Add message
        $message = $record->message;
        $parts[] = "\n" . $this->escapeHtml($message);

        // Add context
        if ($this->options['include_context'] && !empty($record->context)) {
            $context = $this->formatContext($record->context);
            if ($context) {
                $parts[] = "\n\n<b>Context:</b>\n<pre>" . $this->escapeHtml($context) . '</pre>';
            }
        }

        // Add extra data
        if (!empty($record->extra)) {
            $extra = $this->formatContext($record->extra);
            if ($extra) {
                $parts[] = "\n\n<b>Extra:</b>\n<pre>" . $this->escapeHtml($extra) . '</pre>';
            }
        }

        // Add stack trace for errors
        if ($this->options['include_trace'] && in_array($record->level->getName(), ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])) {
            $trace = $this->formatStackTrace($record);
            if ($trace) {
                $parts[] = "\n\n<b>Stack Trace:</b>\n<pre>" . $this->escapeHtml($trace) . '</pre>';
            }
        }

        $formatted = implode('', $parts);

        // Truncate if too long
        if (mb_strlen($formatted) > $this->options['max_message_length']) {
            $formatted = mb_substr($formatted, 0, $this->options['max_message_length'] - 100) . "\n\n... (truncated)";
        }

        return $formatted;
    }

    /**
     * Formats a batch of log records
     *
     * @param array $records
     * @return string
     */
    public function formatBatch(array $records): string
    {
        $formatted = [];
        foreach ($records as $record) {
            $formatted[] = $this->format($record);
        }

        return implode("\n\n---\n\n", $formatted);
    }

    /**
     * Format context data
     *
     * @param array $context
     * @return string
     */
    private function formatContext(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        return json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Format stack trace from exception
     *
     * @param LogRecord $record
     * @return string
     */
    private function formatStackTrace(LogRecord $record): string
    {
        $exception = $record->context['exception'] ?? null;

        if ($exception instanceof \Throwable) {
            $trace = [];
            $trace[] = get_class($exception) . ': ' . $exception->getMessage();
            $trace[] = 'File: ' . $exception->getFile() . ':' . $exception->getLine();

            foreach ($exception->getTrace() as $index => $frame) {
                $file = $frame['file'] ?? 'unknown';
                $line = $frame['line'] ?? 'unknown';
                $function = $frame['function'] ?? 'unknown';
                $class = $frame['class'] ?? '';

                $trace[] = sprintf(
                    "#%d %s%s%s() in %s:%d",
                    $index,
                    $class,
                    $frame['type'] ?? '',
                    $function,
                    $file,
                    $line
                );
            }

            return implode("\n", array_slice($trace, 0, 20)); // Limit to 20 frames
        }

        return '';
    }

    /**
     * Escape HTML special characters
     *
     * @param string $text
     * @return string
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}

