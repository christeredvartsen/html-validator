<?php declare(strict_types=1);

namespace HtmlValidator;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use HtmlValidator\Exception\InvalidArgumentException;
use HtmlValidator\Exception\ServerException;

use function sprintf;

class Validator
{
    public const string PARSER_XML = 'xml';
    public const string PARSER_XMLDTD = 'xmldtd';
    public const string PARSER_HTML = 'html';
    public const string PARSER_HTML5 = 'html5';
    public const string PARSER_HTML4 = 'html4';
    public const string PARSER_HTML4TR = 'html4tr';

    public const string CHARSET_UTF_8 = 'UTF-8';
    public const string CHARSET_UTF_16 = 'UTF-16';
    public const string CHARSET_WINDOWS_1250 = 'Windows-1250';
    public const string CHARSET_WINDOWS_1251 = 'Windows-1251';
    public const string CHARSET_WINDOWS_1252 = 'Windows-1252';
    public const string CHARSET_WINDOWS_1253 = 'Windows-1253';
    public const string CHARSET_WINDOWS_1254 = 'Windows-1254';
    public const string CHARSET_WINDOWS_1255 = 'Windows-1255';
    public const string CHARSET_WINDOWS_1256 = 'Windows-1256';
    public const string CHARSET_WINDOWS_1257 = 'Windows-1257';
    public const string CHARSET_WINDOWS_1258 = 'Windows-1258';
    public const string CHARSET_ISO_8859_1 = 'ISO-8859-1';
    public const string CHARSET_ISO_8859_2 = 'ISO-8859-2';
    public const string CHARSET_ISO_8859_3 = 'ISO-8859-3';
    public const string CHARSET_ISO_8859_4 = 'ISO-8859-4';
    public const string CHARSET_ISO_8859_5 = 'ISO-8859-5';
    public const string CHARSET_ISO_8859_6 = 'ISO-8859-6';
    public const string CHARSET_ISO_8859_7 = 'ISO-8859-7';
    public const string CHARSET_ISO_8859_8 = 'ISO-8859-8';
    public const string CHARSET_ISO_8859_9 = 'ISO-8859-9';
    public const string CHARSET_ISO_8859_13 = 'ISO-8859-13';
    public const string CHARSET_ISO_8859_15 = 'ISO-8859-15';
    public const string CHARSET_KOI8_R = 'KOI8-R';
    public const string CHARSET_TIS_620 = 'TIS-620';
    public const string CHARSET_GBK = 'GBK';
    public const string CHARSET_GB18030 = 'GB18030';
    public const string CHARSET_BIG5 = 'Big5';
    public const string CHARSET_BIG5_HKSCS = 'Big5-HKSCS';
    public const string CHARSET_SHIFT_JIS = 'Shift_JIS';
    public const string CHARSET_ISO_2022_JP = 'ISO-2022-JP';
    public const string CHARSET_EUC_JP = 'EUC-JP';
    public const string CHARSET_ISO_2022_KR = 'ISO-2022-KR';
    public const string CHARSET_EUC_KR = 'EUC-KR';

    public const string DEFAULT_VALIDATOR_URL = 'https://validator.nu';

    private HttpClient $httpClient;
    private string $parser;
    private string $charset = self::CHARSET_UTF_8;
    private NodeWrapper $nodeWrapper;

    public function __construct(string $validatorUrl = self::DEFAULT_VALIDATOR_URL, string $parser = self::PARSER_HTML5)
    {
        $this->httpClient = new HttpClient([
            'base_uri' => $validatorUrl,
            'headers' => ['User-Agent' => 'christeredvartsen/html-validator'],
        ]);

        $this->nodeWrapper = new NodeWrapper();

        $this->setParser($parser);
    }

    public function setHttpClient(HttpClient $httpClient): static
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function getParser(): string
    {
        return $this->parser;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setParser(string $parser): static
    {
        switch ($parser) {
            case self::PARSER_XML:
            case self::PARSER_XMLDTD:
            case self::PARSER_HTML:
            case self::PARSER_HTML5:
            case self::PARSER_HTML4:
            case self::PARSER_HTML4TR:
                $this->parser = $parser;

                return $this;
            default:
                throw new InvalidArgumentException(sprintf('Unknown parser: "%s"', $parser));
        }
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function setCharset(string $charset): static
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Validate a complete document (including DOCTYPE).
     *
     * @throws ServerException
     */
    public function validateDocument(string $document, ?string $charset = null): Response
    {
        $headers = [
            'Content-Type' => $this->getContentTypeString(
                $this->getMimeTypeForParser($this->parser),
                $charset ?: $this->charset,
            ),
        ];

        try {
            $response = $this->httpClient->request('POST', '', [
                'body' => $document,
                'headers' => $headers,
                'query' => [
                    'out' => 'json',
                    'parser' => $this->parser,
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
                'parser' => $this->parser,
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
     * @param string  $nodes   HTML/XML-chunk, as string
     * @param ?string $charset Charset to report
     */
    public function validateNodes(string $nodes, ?string $charset = null): Response
    {
        $wrapped = $this->nodeWrapper->wrap(
            $this->parser,
            $nodes,
            $charset ?: $this->charset,
        );

        return $this->validateDocument($wrapped, $charset);
    }

    private function getMimeTypeForParser(string $parser): string
    {
        return match ($parser) {
            self::PARSER_XML => 'application/xml',
            self::PARSER_XMLDTD => 'application/xml-dtd',
            self::PARSER_HTML,
            self::PARSER_HTML5,
            self::PARSER_HTML4,
            self::PARSER_HTML4TR => 'text/html',
            default => throw new InvalidArgumentException(sprintf('Unknown parser: "%s"', $parser)),
        };
    }

    private function getContentTypeString(string $mimeType, string $charset): string
    {
        return $mimeType.'; charset='.strtolower($charset);
    }
}
