<?php

namespace SayidMuhammad\TelegramLogger\Formatters;

use SayidMuhammad\TelegramLogger\Formatters\Concerns\BuildsTelegramMessage;

if (class_exists(\Monolog\LogRecord::class)) {
    /**
     * Telegram formatter for Monolog 3 (LogRecord API)
     */
    class TelegramFormatter implements \Monolog\Formatter\FormatterInterface
    {
        use BuildsTelegramMessage;

        public function __construct(array $options = [], array $emojis = [])
        {
            $this->options = array_merge($this->defaultOptions(), $options);
            $this->emojis = array_merge($this->defaultEmojis(), $emojis);
        }

        public function format(\Monolog\LogRecord $record): string
        {
            return $this->buildMessage([
                'level_name' => $record->level->getName(),
                'datetime' => $record->datetime,
                'message' => $record->message,
                'context' => $record->context,
                'extra' => $record->extra,
                'exception' => $record->context['exception'] ?? null,
            ]);
        }

        public function formatBatch(array $records): string
        {
            $formatted = [];
            foreach ($records as $record) {
                $formatted[] = $this->format($record);
            }

            return implode("\n\n---\n\n", $formatted);
        }

        private function defaultOptions(): array
        {
            return [
                'include_date' => true,
                'include_level' => true,
                'include_context' => true,
                'include_trace' => true,
                'max_message_length' => 4096,
                'use_emojis' => true,
            ];
        }

        private function defaultEmojis(): array
        {
            return [
                'debug' => '🐛',
                'info' => 'ℹ️',
                'notice' => '📢',
                'warning' => '⚠️',
                'error' => '❌',
                'critical' => '🔥',
                'alert' => '🚨',
                'emergency' => '💥',
            ];
        }
    }
} else {
    /**
     * Telegram formatter for Monolog 2 (array record API)
     */
    class TelegramFormatter implements \Monolog\Formatter\FormatterInterface
    {
        use BuildsTelegramMessage;

        public function __construct(array $options = [], array $emojis = [])
        {
            $this->options = array_merge($this->defaultOptions(), $options);
            $this->emojis = array_merge($this->defaultEmojis(), $emojis);
        }

        public function format(array $record): string
        {
            $levelName = $record['level_name'] ?? ($record['level'] ?? 'info');
            $datetime = $record['datetime'] ?? new \DateTimeImmutable();
            if (!$datetime instanceof \DateTimeInterface) {
                $datetime = new \DateTimeImmutable($datetime);
            }

            return $this->buildMessage([
                'level_name' => $levelName,
                'datetime' => $datetime,
                'message' => (string) ($record['message'] ?? ''),
                'context' => $record['context'] ?? [],
                'extra' => $record['extra'] ?? [],
                'exception' => $record['context']['exception'] ?? null,
            ]);
        }

        public function formatBatch(array $records): string
        {
            $formatted = [];
            foreach ($records as $record) {
                $formatted[] = $this->format($record);
            }

            return implode("\n\n---\n\n", $formatted);
        }

        private function defaultOptions(): array
        {
            return [
                'include_date' => true,
                'include_level' => true,
                'include_context' => true,
                'include_trace' => true,
                'max_message_length' => 4096,
                'use_emojis' => true,
            ];
        }

        private function defaultEmojis(): array
        {
            return [
                'debug' => '🐛',
                'info' => 'ℹ️',
                'notice' => '📢',
                'warning' => '⚠️',
                'error' => '❌',
                'critical' => '🔥',
                'alert' => '🚨',
                'emergency' => '💥',
            ];
        }
    }
}

