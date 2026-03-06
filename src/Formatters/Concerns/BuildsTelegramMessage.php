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
        $maxLength = $this->options['max_message_length'] ?? 4096;

        // Level header with emoji
        if ($this->options['include_level'] ?? true) {
            $emoji = '';
            if ($this->options['use_emojis'] ?? true) {
                $emoji = $this->emojis[strtolower($levelName)] ?? '';
            }
            $parts[] = trim($emoji . ' ' . $levelName);
        }

        // Divider
        $parts[] = '━━━━━━━━━━━━━━━━━━';

        // Date
        if ($this->options['include_date'] ?? true) {
            $date = $data['datetime'] ?? new \DateTimeImmutable();
            if (!$date instanceof \DateTimeInterface) {
                $date = new \DateTimeImmutable();
            }
            $parts[] = '📅 ' . $date->format('Y-m-d H:i:s');
        }

        // Environment info (only if Laravel container is available)
        try {
            $appEnv = config('app.env', 'production');
            $appName = config('app.name', '');
            if ($appName) {
                $parts[] = '🌍 ' . $appEnv . ' (' . $appName . ')';
            } else {
                $parts[] = '🌍 ' . $appEnv;
            }
        } catch (\Exception) {
            // Config not available (e.g., in unit tests without Laravel container)
        }

        // Message
        $message = $this->escapeHtml((string) ($data['message'] ?? ''));
        $parts[] = "\n💬 <b>" . $message . '</b>';

        // Context (excluding 'exception' key which is shown in stack trace)
        if (($this->options['include_context'] ?? true) && !empty($data['context'] ?? [])) {
            $context = $data['context'];
            unset($context['exception']);

            if (!empty($context)) {
                $parts[] = "\n📋 <b>Context</b>";
                $parts[] = $this->escapeHtml($this->formatContext($context));
            }
        }

        // Extra
        if (($this->options['include_extra'] ?? false) && !empty($data['extra'] ?? [])) {
            $parts[] = "\n📦 <b>Extra</b>";
            $parts[] = $this->escapeHtml($this->formatContext($data['extra']));
        }

        // Stack Trace with chained exceptions support
        if (($this->options['include_trace'] ?? true) && $this->levelRequiresTrace($levelName)) {
            $exception = $data['exception'] ?? ($data['context']['exception'] ?? null);
            $trace = $this->formatStackTraceWithChaining($exception);
            if ($trace !== '') {
                $parts[] = "\n🔍 <b>Stack Trace</b>";
                $parts[] = '<pre>' . $this->escapeHtml($trace) . '</pre>';
            }
        }

        // Join with newlines
        $formatted = implode("\n", $parts);

        // Truncate safely, accounting for closing tags
        $suffix = "\n\n... (truncated)";
        if (mb_strlen($formatted) > $maxLength) {
            $truncateLength = $maxLength - mb_strlen($suffix) - 30; // Reserve space for closing tags
            $formatted = $this->truncateSafely($formatted, $truncateLength);
            $formatted = $this->ensureBalancedTags($formatted);
            $formatted .= $suffix;
        } else {
            $formatted = $this->ensureBalancedTags($formatted);
        }

        return $formatted;
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

    protected function formatStackTraceWithChaining(mixed $exception): string
    {
        if (!$exception instanceof \Throwable) {
            return '';
        }

        $output = [];
        $current = $exception;
        $depth = 0;
        $maxDepth = 5; // Limit chained exception depth

        while ($current !== null && $depth < $maxDepth) {
            if ($depth > 0) {
                $output[] = "\nCaused by:";
            }

            $output[] = get_class($current) . ': ' . $current->getMessage();
            $output[] = 'File: ' . $current->getFile() . ':' . $current->getLine();

            $traceLines = [];
            foreach ($current->getTrace() as $index => $frame) {
                if (count($traceLines) >= 10) break; // Limit trace per exception

                $file = $frame['file'] ?? 'unknown';
                $line = $frame['line'] ?? 0;
                $function = $frame['function'] ?? 'unknown';
                $class = $frame['class'] ?? '';
                $type = $frame['type'] ?? '';

                $traceLines[] = sprintf('#%d %s%s%s() in %s:%d', $index, $class, $type, $function, $file, $line);
            }

            $output = array_merge($output, $traceLines);

            // Get previous exception (Throwable::getPrevious)
            $current = method_exists($current, 'getPrevious') ? $current->getPrevious() : null;
            $depth++;
        }

        return implode("\n", $output);
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
        $tags = ['b', 'pre']; // Close inner tags first

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

