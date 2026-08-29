<?php declare(strict_types=1);

namespace HtmlValidator;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use HtmlValidator\Exception\ServerException;

class Validator
{
    public const string DEFAULT_VALIDATOR_URL = 'https://validator.nu';

    private ClientInterface $httpClient;
    private Parser $parser;
    private Charset $charset = Charset::UTF8;
    private NodeWrapper $nodeWrapper;

    public function __construct(string $validatorUrl = self::DEFAULT_VALIDATOR_URL, Parser $parser = Parser::HTML5)
    {
        $this->httpClient = new Client([
            'base_uri' => $validatorUrl,
            'headers' => ['User-Agent' => 'christeredvartsen/html-validator'],
        ]);

        $this->nodeWrapper = new NodeWrapper();

        $this->parser = $parser;
    }

    public function setHttpClient(ClientInterface $httpClient): static
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function getParser(): Parser
    {
        return $this->parser;
    }

    public function setParser(Parser $parser): static
    {
        $this->parser = $parser;

        return $this;
    }

    public function getCharset(): Charset
    {
        return $this->charset;
    }

    public function setCharset(Charset $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Validate a complete document (including DOCTYPE).
     *
     * @throws ServerException
     */
    public function validateDocument(string $document, ?Charset $charset = null): Response
    {
        $headers = [
            'Content-Type' => $this->getContentTypeString(
                $this->parser->getMimeType(),
                $charset ?: $this->charset,
            ),
        ];

        try {
            $response = $this->httpClient->request('POST', '', [
                'body' => $document,
                'headers' => $headers,
                'query' => [
                    'out' => 'json',
                    'parser' => $this->parser->value,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new ServerException('Unable to validate document.', previous: $e);
        }

        return new Response($response);
    }

    /**
     * Validate a URL.
     *
     * @param array{checkErrorPages?:bool} $options
     *
     * @throws ServerException
     */
    public function validateUrl(string $url, array $options = []): Response
    {
        try {
            $query = [
                'out' => 'json',
                'parser' => $this->parser->value,
                'doc' => (string) $url,
            ];

            if (isset($options['checkErrorPages']) && true === $options['checkErrorPages']) {
                $query['checkerrorpages'] = true;
            }

            $response = $this->httpClient->request('GET', '', [
                'query' => $query,
            ]);
        } catch (GuzzleException $e) {
            throw new ServerException('Unable to validate URL.', previous: $e);
        }

        return new Response($response);
    }

    /**
     * Validate a chunk of HTML/XML.
     *
     * A surrounding document will be created on the fly based on the formatter specified. Note that
     * this can lead to unexpected behaviour:
     *
     * - Line numbers reported will be incorrect
     * - Injected document might not be right for your use case
     *
     * NOTE: Use validateDocument() whenever possible.
     *
     * @param string   $nodes   HTML/XML-chunk, as string
     * @param ?Charset $charset Charset to report
     */
    public function validateNodes(string $nodes, ?Charset $charset = null): Response
    {
        $wrapped = $this->nodeWrapper->wrap(
            $this->parser,
            $nodes,
            $charset ?: $this->charset,
        );

        return $this->validateDocument($wrapped, $charset);
    }

    private function getContentTypeString(string $mimeType, Charset $charset): string
    {
        return $mimeType.'; charset='.strtolower($charset->value);
    }
}
