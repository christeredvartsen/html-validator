<?php declare(strict_types=1);

namespace HtmlValidator;

use HtmlValidator\Exception\InvalidArgumentException;
use HtmlValidator\Exception\ServerException;
use JsonException;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use Stringable;

use function explode;
use function is_array;
use function sprintf;
use function strtolower;
use function trim;

use const JSON_OBJECT_AS_ARRAY;
use const JSON_THROW_ON_ERROR;
use const PHP_EOL;

class Response implements Stringable
{
    /**
     * @var list<Message>
     */
    private array $errors = [];

    /**
     * @var list<Message>
     */
    private array $warnings = [];

    /**
     * @var list<Message>
     */
    private array $messages = [];

    public function __construct(HttpResponse $response)
    {
        $this->parseMessages($this->validateResponse($response));
    }

    /**
     * Returns whether the markup the user tried to validate had any errors.
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Returns whether the markup the user tried to validate had any warnings.
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Returns whether the markup the user tried to validate resulted in any messages.
     */
    public function hasMessages(): bool
    {
        return !empty($this->messages);
    }

    /**
     * Returns all encountered errors.
     *
     * @return list<Message>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns all encountered warnings.
     *
     * @return list<Message>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Returns all encountered messages.
     *
     * @return list<Message>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Format the messages.
     */
    public function format(bool $withHtml = false): string
    {
        $msgs = [];
        foreach ($this->messages as $msg) {
            $msgs[] = $msg->format($withHtml);
        }

        return implode(PHP_EOL.PHP_EOL, $msgs);
    }

    /**
     * Returns the messages as a human-readable HTML string.
     */
    public function toHTML(): string
    {
        return $this->format(true);
    }

    /**
     * Returns the messages as a human-readable string.
     */
    public function __toString(): string
    {
        return $this->format();
    }

    /**
     * @return array<mixed,mixed>
     *
     * @throws ServerException
     */
    private function validateResponse(HttpResponse $response): array
    {
        if (200 !== $response->getStatusCode()) {
            $statusCode = $response->getStatusCode();
            throw new ServerException(sprintf('Expected HTTP 200, got: %d', $statusCode), $statusCode);
        }

        $contentType = $response->getHeaderLine('Content-Type');
        $mediaType = strtolower(trim(explode(';', $contentType, 2)[0]));
        if ('application/json' !== $mediaType) {
            throw new ServerException(sprintf('Expected Content-Type application/json, got: %s', $contentType));
        }

        $body = (string) $response->getBody();
        try {
            $data = json_decode($body, flags: JSON_THROW_ON_ERROR | JSON_OBJECT_AS_ARRAY);
        } catch (JsonException $e) {
            throw new ServerException(sprintf('Invalid JSON in HTTP response: %s', $e->getMessage()), previous: $e);
        }

        if (!is_array($data) || !isset($data['messages']) || !is_array($data['messages'])) {
            throw new ServerException('Invalid JSON structure from validator.');
        }

        return $data['messages'];
    }

    /**
     * @param array<mixed,mixed> $messages
     */
    private function parseMessages(array $messages): void
    {
        foreach ($messages as $message) {
            if (!is_array($message)) {
                continue;
            }

            try {
                $msg = new Message($message);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $this->messages[] = $msg;
            if ('error' === $msg->getType() || 'non-document-error' === $msg->getType()) {
                $this->errors[] = $msg;
            } elseif ('info' === $msg->getType() && 'warning' === ($message['subtype'] ?? null)) {
                $this->warnings[] = $msg;
            }
        }
    }
}
