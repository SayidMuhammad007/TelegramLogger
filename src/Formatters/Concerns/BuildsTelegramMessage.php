<?php

namespace SayidMuhammad\TelegramLogger\Formatters\Concerns;

trait BuildsTelegramMessage
{
    protected array $options = [];

    protected array $emojis = [];

    /**
     * Build a Telegram message string from normalized log data.
     *
     * @param array{
     *     level_name: string,
     *     datetime: \DateTimeInterface,
     *     message: string,
     *     context: array,
     *     extra: array,
     *     exception?: \Throwable|null
     * } $data
     */
    protected function buildMessage(array $data): string
    {
        $parts = [];
        $levelName = strtoupper($data['level_name'] ?? 'INFO');

        if (($this->options['use_emojis'] ?? true) && ($this->options['include_level'] ?? true)) {
            $emoji = $this->emojis[strtolower($levelName)] ?? '';
            $parts[] = trim($emoji . ' <b>' . $levelName . '</b>');
        } elseif ($this->options['include_level'] ?? true) {
            $parts[] = '<b>' . $levelName . '</b>';
        }

        if ($this->options['include_date'] ?? true) {
            $date = $data['datetime'] ?? new \DateTimeImmutable();
            if (!$date instanceof \DateTimeInterface) {
                $date = new \DateTimeImmutable();
            }

            $parts[] = '📅 ' . $date->format('Y-m-d H:i:s');
        }

        $parts[] = "\n" . $this->escapeHtml((string) ($data['message'] ?? ''));

        if (($this->options['include_context'] ?? true) && !empty($data['context'] ?? [])) {
            $parts[] = "\n\n<b>Context:</b>\n<pre>" . $this->escapeHtml(
                $this->formatContext($data['context'])
            ) . '</pre>';
        }

        if (!empty($data['extra'] ?? [])) {
            $parts[] = "\n\n<b>Extra:</b>\n<pre>" . $this->escapeHtml(
                $this->formatContext($data['extra'])
            ) . '</pre>';
        }

        if (($this->options['include_trace'] ?? true) && $this->levelRequiresTrace($levelName)) {
            $exception = $data['exception'] ?? ($data['context']['exception'] ?? null);
            $trace = $this->formatStackTrace($exception);
            if ($trace !== '') {
                $parts[] = "\n\n<b>Stack Trace:</b>\n<pre>" . $this->escapeHtml($trace) . '</pre>';
            }
        }

        $formatted = implode('', $parts);
        $maxLength = $this->options['max_message_length'] ?? 4096;

        if (mb_strlen($formatted) > $maxLength) {
            $formatted = $this->truncateSafely($formatted, $maxLength - 25);
            $formatted .= "\n\n... (truncated)";
        }

        return $this->ensureBalancedTags($formatted);
    }

    protected function formatContext(array $context): string
    {
        if (empty($context)) {
            return '';
        }

        return json_encode(
            $context,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ) ?: '';
    }

    protected function levelRequiresTrace(string $levelName): bool
    {
        return in_array(strtoupper($levelName), ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'], true);
    }

    protected function formatStackTrace(mixed $exception): string
    {
        if (!$exception instanceof \Throwable) {
            return '';
        }

        $trace = [];
        $trace[] = get_class($exception) . ': ' . $exception->getMessage();
        $trace[] = 'File: ' . $exception->getFile() . ':' . $exception->getLine();

        foreach ($exception->getTrace() as $index => $frame) {
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? 0;
            $function = $frame['function'] ?? 'unknown';
            $class = $frame['class'] ?? '';
            $type = $frame['type'] ?? '';

            $trace[] = sprintf('#%d %s%s%s() in %s:%d', $index, $class, $type, $function, $file, $line);
        }

        return implode("\n", array_slice($trace, 0, 20));
    }

    protected function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function truncateSafely(string $message, int $limit): string
    {
        $truncated = mb_substr($message, 0, $limit);
        $lastOpen = mb_strrpos($truncated, '<');
        $lastClose = mb_strrpos($truncated, '>');

        if ($lastOpen !== false && ($lastClose === false || $lastOpen > $lastClose)) {
            $truncated = mb_substr($truncated, 0, $lastOpen);
        }

        $lastAmp = mb_strrpos($truncated, '&');
        $lastSemi = mb_strrpos($truncated, ';');

        if ($lastAmp !== false && ($lastSemi === false || $lastAmp > $lastSemi)) {
            $truncated = mb_substr($truncated, 0, $lastAmp);
        }

        return rtrim($truncated);
    }

    private function ensureBalancedTags(string $message): string
    {
        $tags = ['pre', 'b'];

        foreach ($tags as $tag) {
            $openCount = substr_count($message, "<{$tag}>");
            $closeCount = substr_count($message, "</{$tag}>");

            if ($openCount > $closeCount) {
                $message .= str_repeat("</{$tag}>", $openCount - $closeCount);
            }
        }

        return $message;
    }
}

