<?php declare(strict_types=1);

namespace HtmlValidator;

use HtmlValidator\Exception\InvalidArgumentException;
use Stringable;

use function array_key_exists;
use function in_array;
use function is_int;
use function is_string;
use function ord;
use function sprintf;
use function strlen;

use const ENT_COMPAT;
use const PHP_EOL;

class Message implements Stringable
{
    private string $type;
    private int $firstLine;
    private int $lastLine;
    private int $firstColumn;
    private int $lastColumn;
    private int $hiliteStart;
    private int $hiliteLength;
    private string $text;
    private string $extract;

    /**
     * @var ?callable(string,int,int):string
     */
    private $highlighter;

    /**
     * CSS class name to use for the highlighted substring.
     *
     * Only used if no custom highlighter is set.
     */
    private string $highlightClassName = 'highlight';

    /**
     * @param array<mixed,mixed> $data
     *
     * @throws InvalidArgumentException
     *
     * @see https://github.com/validator/validator/wiki/Output-%C2%BB-JSON
     */
    public function __construct(array $data = [])
    {
        if (empty($data['type']) || !is_string($data['type'])) {
            throw new InvalidArgumentException('Message type must be a non-empty string.');
        }

        if (!in_array($data['type'], ['info', 'error', 'non-document-error'], true)) {
            throw new InvalidArgumentException('Message type must be info, error, or non-document-error.');
        }

        $this->type = $data['type'];
        $this->lastLine = $this->getInteger($data, 'lastLine');
        $this->firstLine = $this->getInteger($data, 'firstLine', $this->lastLine);
        $this->firstColumn = $this->getInteger($data, 'firstColumn');
        $this->lastColumn = $this->getInteger($data, 'lastColumn');
        $this->hiliteStart = $this->getInteger($data, 'hiliteStart');
        $this->hiliteLength = $this->getInteger($data, 'hiliteLength');
        $this->text = $this->getString($data, 'message');
        $this->extract = $this->getString($data, 'extract');
    }

    /**
     * Get the message's general class: info, error, or non-document-error.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the one-based first line of the associated source range.
     */
    public function getFirstLine(): int
    {
        return $this->firstLine;
    }

    /**
     * Get the one-based last line of the associated source range.
     */
    public function getLastLine(): int
    {
        return $this->lastLine;
    }

    /**
     * Get the one-based first column of the associated source range, measured in UTF-16 code units.
     */
    public function getFirstColumn(): int
    {
        return $this->firstColumn;
    }

    /**
     * Get the one-based last column of the associated source range, measured in UTF-16 code units.
     */
    public function getLastColumn(): int
    {
        return $this->lastColumn;
    }

    /**
     * Get the concise natural-language description of the message.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Get the source extract around the associated source range.
     */
    public function getExtract(): string
    {
        return $this->extract;
    }

    /**
     * Get the UTF-16 code-unit index in the source extract where highlighting starts.
     */
    public function getHighlightStart(): int
    {
        return $this->hiliteStart;
    }

    /**
     * Get the highlighted portion's length in UTF-16 code units.
     */
    public function getHighlightLength(): int
    {
        return $this->hiliteLength;
    }

    /**
     * Set function to use for highlighting a substring within a string.
     *
     * Arguments for the function:
     *
     * string $str    The full string in which to find the substring
     * int    $start  Start index of the substring to highlight
     * int    $length Length of substring to highlight
     *
     * @param callable(string,int,int):string $highlighter
     */
    public function setHighlighter(callable $highlighter): static
    {
        $this->highlighter = $highlighter;

        return $this;
    }

    /**
     * Set the CSS class name to use for the highlighted span.
     */
    public function setHighlightClassName(string $className): static
    {
        $this->highlightClassName = $className;

        return $this;
    }

    /**
     * Format the message.
     */
    public function format(bool $withHtml = false): string
    {
        $format = '%s: %s';

        if ($this->lastLine > 0) {
            $format .= PHP_EOL;
            $format .= 'From line %d, column %d; ';
            $format .= 'to line %d, column %d';
        }

        $message = sprintf(
            $format,
            $withHtml ? '<strong>'.$this->type.'</strong>' : $this->type,
            $withHtml ? htmlentities($this->text, ENT_COMPAT, 'UTF-8') : $this->text,
            $this->firstLine,
            $this->firstColumn,
            $this->lastLine,
            $this->lastColumn,
        );

        if (!$withHtml) {
            return $message.PHP_EOL.$this->extract;
        }

        $cb = $this->highlighter ?? $this->highlight(...);
        $extract = $cb($this->extract, $this->hiliteStart, $this->hiliteLength);
        $message .= PHP_EOL.$extract;

        return nl2br($message, false);
    }

    /**
     * Returns the message as a human-readable HTML string.
     */
    public function toHTML(): string
    {
        return $this->format(true);
    }

    /**
     * Returns the message as a human-readable string.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @param array<mixed,mixed> $data
     *
     * @throws InvalidArgumentException
     */
    private function getInteger(array $data, string $key, int $default = 0): int
    {
        if (!array_key_exists($key, $data)) {
            return $default;
        }

        if (!is_int($data[$key])) {
            throw new InvalidArgumentException(sprintf('Message %s must be an integer.', $key));
        }

        return $data[$key];
    }

    /**
     * @param array<mixed,mixed> $data
     *
     * @throws InvalidArgumentException
     */
    private function getString(array $data, string $key): string
    {
        if (!array_key_exists($key, $data)) {
            return '';
        }

        if (!is_string($data[$key])) {
            throw new InvalidArgumentException(sprintf('Message %s must be a string.', $key));
        }

        return $data[$key];
    }

    private function highlight(string $str, int $start, int $length): string
    {
        $startOffset = $this->getUtf8ByteOffset($str, $start);
        $endOffset = $this->getUtf8ByteOffset($str, $start + $length);
        $parts = array_map('htmlentities', [
            substr($str, 0, $startOffset),
            substr($str, $startOffset, $endOffset - $startOffset),
            substr($str, $endOffset),
        ]);

        return sprintf(
            '%s<span class="%s">%s</span>%s',
            $parts[0],
            $this->highlightClassName,
            $parts[1],
            $parts[2],
        );
    }

    /**
     * Convert a validator.nu UTF-16 code-unit offset into a UTF-8 byte offset.
     *
     * PHP's substr() operates on bytes, while validator.nu reports highlights in UTF-16 code units.
     * UTF-8 characters use one to four bytes; only four-byte UTF-8 characters, which represent code
     * points outside the BMP, use two UTF-16 code units.
     */
    private function getUtf8ByteOffset(string $str, int $utf16Offset): int
    {
        $byteOffset = 0;
        $units = 0;
        $length = strlen($str);

        while ($byteOffset < $length && $units < $utf16Offset) {
            $byte = ord($str[$byteOffset]);
            $byteLength = $byte < 0x80 ? 1 : ($byte < 0xE0 ? 2 : ($byte < 0xF0 ? 3 : 4));
            $units += 4 === $byteLength ? 2 : 1;
            $byteOffset += $byteLength;
        }

        return $byteOffset;
    }
}
