<?php
/**
 * This file is part of the html-validator package.
 *
 * (c) Espen Hovlandsdal <espen@hovlandsdal.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace HtmlValidator;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use HtmlValidator\Exception\ServerException;
use HtmlValidator\Exception\UnknownParserException;

/**
 * HTML Validator (uses checker.html5.org as backend)
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/html-validator
 */
class Validator {

    /**
     * Parser constants
     *
     * @var string
     */
    const PARSER_XML     = 'xml';
    const PARSER_XMLDTD  = 'xmldtd';
    const PARSER_HTML    = 'html';
    const PARSER_HTML5   = 'html5';
    const PARSER_HTML4   = 'html4';
    const PARSER_HTML4TR = 'html4tr';

    /**
     * Characters sets
     *
     * @var string
     */
    const CHARSET_UTF_8 = 'UTF-8';
    const CHARSET_UTF_16 = 'UTF-16';
    const CHARSET_WINDOWS_1250 = 'Windows-1250';
    const CHARSET_WINDOWS_1251 = 'Windows-1251';
    const CHARSET_WINDOWS_1252 = 'Windows-1252';
    const CHARSET_WINDOWS_1253 = 'Windows-1253';
    const CHARSET_WINDOWS_1254 = 'Windows-1254';
    const CHARSET_WINDOWS_1255 = 'Windows-1255';
    const CHARSET_WINDOWS_1256 = 'Windows-1256';
    const CHARSET_WINDOWS_1257 = 'Windows-1257';
    const CHARSET_WINDOWS_1258 = 'Windows-1258';
    const CHARSET_ISO_8859_1 = 'ISO-8859-1';
    const CHARSET_ISO_8859_2 = 'ISO-8859-2';
    const CHARSET_ISO_8859_3 = 'ISO-8859-3';
    const CHARSET_ISO_8859_4 = 'ISO-8859-4';
    const CHARSET_ISO_8859_5 = 'ISO-8859-5';
    const CHARSET_ISO_8859_6 = 'ISO-8859-6';
    const CHARSET_ISO_8859_7 = 'ISO-8859-7';
    const CHARSET_ISO_8859_8 = 'ISO-8859-8';
    const CHARSET_ISO_8859_9 = 'ISO-8859-9';
    const CHARSET_ISO_8859_13 = 'ISO-8859-13';
    const CHARSET_ISO_8859_15 = 'ISO-8859-15';
    const CHARSET_KOI8_R = 'KOI8-R';
    const CHARSET_TIS_620 = 'TIS-620';
    const CHARSET_GBK = 'GBK';
    const CHARSET_GB18030 = 'GB18030';
    const CHARSET_BIG5 = 'Big5';
    const CHARSET_BIG5_HKSCS = 'Big5-HKSCS';
    const CHARSET_SHIFT_JIS = 'Shift_JIS';
    const CHARSET_ISO_2022_JP = 'ISO-2022-JP';
    const CHARSET_EUC_JP = 'EUC-JP';
    const CHARSET_ISO_2022_KR = 'ISO-2022-KR';
    const CHARSET_EUC_KR = 'EUC-KR' ;

    /**
     * Default validator URL
     *
     * @var string
     */
    const DEFAULT_VALIDATOR_URL = 'https://validator.nu';

    /**
     * Holds the HTTP client used to communicate with the API
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Parser to use for validating
     *
     * @var string
     */
    private $parser;

    /**
     * Default charset to report to the validator
     *
     * @var string
     */
    private $defaultCharset = self::CHARSET_UTF_8;

    /**
     * Node wrapper tool
     *
     * @var NodeWrapper
     */
    private $nodeWrapper;

    /**
     * Constructs a new validator instance
     *
     * @param string $validatorUrl
     * @param string $parser
     * @throws UnknownParserException
     */
    public function __construct($validatorUrl = self::DEFAULT_VALIDATOR_URL, $parser = self::PARSER_HTML5) {
        $this->httpClient = new HttpClient([
            'base_uri' => $validatorUrl,
            'headers' => ['User-Agent' => 'rexxars/html-validator']
        ]);

        $this->nodeWrapper = new NodeWrapper();

        $this->setParser($parser);
    }

    /**
     * Set the HTTP client to use for requests
     *
     * @param HttpClient $httpClient
     * @return Validator
     */
    public function setHttpClient($httpClient) {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * Get the set parser type
     *
     * @return string
     */
    public function getParser() {
        return $this->parser;
    }

    /**
     * Set parser to use for the given markup
     *
     * @param string $parser Parser name (use Validator::PARSER_*)
     * @return Validator Returns current instance
     * @throws UnknownParserException If parser specified is not known
     */
    public function setParser($parser) {
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
                throw new UnknownParserException('Unknown parser "' . $parser . '"');
        }
    }

    /**
     * Get the charset to report to the validator
     *
     * @return string
     */
    public function getCharset() {
        return $this->defaultCharset;
    }

    /**
     * Set the charset to report to the validator
     *
     * @param string $charset Charset name (defaults to 'utf-8')
     * @return Validator
     */
    public function setCharset($charset) {
        $this->defaultCharset = $charset;

        return $this;
    }

    /**
     * Get the correct mime-type for the given parser
     *
     * @param  string $parser Parser name
     * @return string
     */
    private function getMimeTypeForParser($parser) {
        switch ($parser) {
            case self::PARSER_XML:
                return 'application/xml';
            case self::PARSER_XMLDTD:
                return 'application/xml-dtd';
            default:
            case self::PARSER_HTML:
            case self::PARSER_HTML5:
            case self::PARSER_HTML4:
            case self::PARSER_HTML4TR:
                return 'text/html';
        }
    }

    /**
     * Get a string usable for the Content-Type header,
     * based on the given mime type and charset
     *
     * @param  string $mimeType Mime type to use
     * @param  string $charset  Character set to use
     * @return string
     */
    private function getContentTypeString($mimeType, $charset) {
        return $mimeType . '; charset=' . strtolower($charset);
    }

    /**
     * Validate a complete document (including DOCTYPE)
     *
     * @param string $document HTML/XML-document, as string
     * @param string $charset Charset to report (defaults to utf-8)
     * @return Response
     * @throws ServerException
     */
    public function validateDocument($document, $charset = null) {
        $document = (string) $document;
        $charset  = $charset ?: $this->defaultCharset;
        $headers  = [
            'Content-Type' => $this->getContentTypeString(
                $this->getMimeTypeForParser($this->parser),
                $charset
            ),
        ];

        try {
            $response = $this->httpClient->request('POST', '', [
                'body' => $document,
                'headers' => $headers,
                'query' => [
                    'out' => 'json',
                    'parser' => $this->parser
                ],
            ]);

            $response = new Response($response);
        } catch (GuzzleException $e) {
            throw new ServerException($e->getMessage());
        }

        return $response;
    }

    /**
     * Validate a URL
     *
     * @param string $url The absolute URL to the document
     * @param array  $options Array of options:
     *                        'checkErrorPages' - Set to true if you want to validate pages that
     *                        return status codes which is not in the 2xx range
     *
     * @return Response
     * @throws ServerException
     */
    public function validateUrl($url, $options = []) {
        try {
            $query = [
                'out'    => 'json',
                'parser' => $this->parser,
                'doc'    => (string) $url,
            ];
            
            if (isset($options['checkErrorPages']) && $options['checkErrorPages'] === true) {
                $query['checkerrorpages'] = true;
            }
            
            $response = $this->httpClient->get('', [
                'query' => $query,
            ]);

            return new Response($response);
        } catch (RequestException $e) {
            throw new ServerException($e->getMessage());
        }
    }

    /**
     * Validates a chunk of HTML/XML. A surrounding document will be
     * created on the fly based on the formatter specified. Note that
     * this can lead to unexpected behaviour:
     *   - Line numbers reported will be incorrect
     *   - Injected document might not be right for your use case
     *
     * NOTE: Use validateDocument() whenever possible.
     *
     * @param string $nodes HTML/XML-chunk, as string
     * @param string $charset Charset to report (defaults to configured client charset)
     * @return Response
     * @throws UnknownParserException
     * @throws ServerException
     */
    public function validateNodes($nodes, $charset = null) {
        $wrapped = $this->nodeWrapper->wrap(
            $this->parser,
            $nodes,
            $charset ?: $this->defaultCharset
        );

        return $this->validateDocument($wrapped, $charset);
    }

    /**
     * Validate a complete document (including DOCTYPE)
     *
     * @param string $document HTML/XML-document, as string
     * @param string $charset Charset to report (defaults to configured client charset)
     * @return Response
     * @throws ServerException
     */
    public function validate($document, $charset = null) {
        return $this->validateDocument($document, $charset ?: $this->defaultCharset);
    }

}