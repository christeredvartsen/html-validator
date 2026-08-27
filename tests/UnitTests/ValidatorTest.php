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

use GuzzleHttp\Psr7\Utils;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class ValidatorTest extends \PHPUnit\Framework\TestCase {

    /**
     * Ensure the client can be instantiated without errors with no arguments passed
     *
     * @covers \HtmlValidator\Validator::__construct
     */
    public function testCanConstructClientWithDefaultArguments() {
        $client = new Validator();
        $this->assertInstanceOf('HtmlValidator\Validator', $client);
    }

    /**
     * Test that we can both set and get the parsers to be used when validating
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getParser
     * @covers \HtmlValidator\Validator::setParser
     * @throws \HtmlValidator\Exception\UnknownParserException
     */
    public function testCanSetAndGetParsers() {
        $client = new Validator();

        // Ensure that we default to the 'HTML5' parser
        $this->assertSame(Validator::PARSER_HTML5, $client->getParser());

        // Ensure we can set a different parser (and that the setter returns the client)
        $this->assertSame($client, $client->setParser(Validator::PARSER_XML));

        // Ensure that the set value is the one returned from the getter
        $this->assertSame(Validator::PARSER_XML, $client->getParser());
    }

    /**
     * Test that we can both set and get the charset to be used when validating
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getCharset
     * @covers \HtmlValidator\Validator::setCharset
     */
    public function testCanSetAndGetCharset() {
        $client = new Validator();

        // Ensure default is UTF-8
        $this->assertSame(Validator::CHARSET_UTF_8, $client->getCharset());

        // Ensure we can set a different charset (and that the setter returns the client)
        $this->assertSame($client, $client->setCharset(Validator::CHARSET_ISO_8859_1));

        // Ensure that the set value is the one returned from the getter
        $this->assertSame(Validator::CHARSET_ISO_8859_1, $client->getCharset());
    }

    /**
     * Ensure that the validator sends the correct content type and charset for the parser given
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getCharset
     * @covers \HtmlValidator\Validator::setCharset
     * @covers \HtmlValidator\Validator::getContentTypeString
     * @covers \HtmlValidator\Validator::getMimeTypeForParser
     * @throws \HtmlValidator\Exception\UnknownParserException
     * @throws \HtmlValidator\Exception\ServerException
     */
    public function testValidateDocumentSendsCorrectContentType() {
        $client = new Validator();

        $document = '<p>Dat document</p>';

        $responseMock = $this->getGuzzleResponseMock(['messages' => []], 2);

        $httpClientMock = $this->getHttpClientMock();
        $httpClientMock
            ->expects($this->exactly(1))
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->equalTo([
                    'body' => $document,
                    'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                    'query' => [
                        'out'    => 'json',
                        'parser' => 'html5'
                    ]
                ])
            )
            ->willReturn($responseMock);

        $client->setCharset(Validator::CHARSET_UTF_8);
        $client->setHttpClient($httpClientMock);
        $client->validateDocument($document);
    }

    /**
     * Ensure that the validator sends the correct content type and charset for the parser given
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getCharset
     * @covers \HtmlValidator\Validator::setCharset
     * @covers \HtmlValidator\Validator::getContentTypeString
     * @covers \HtmlValidator\Validator::getMimeTypeForParser
     * @throws \HtmlValidator\Exception\UnknownParserException
     * @throws \HtmlValidator\Exception\ServerException
     */
    public function testValidateDocumentSendsCorrectContentTypeWithExplicitCharset() {
        $client = new Validator();

        $document = '<p>Dat document</p>';

        $responseMock = $this->getGuzzleResponseMock(['messages' => []]);

        $httpClientMock = $this->getHttpClientMock();
        $httpClientMock
            ->expects($this->exactly(1))
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->equalTo([
                    'body' => $document,
                    'headers' => ['Content-Type' => 'text/html; charset=iso-8859-1'],
                    'query' => [
                        'out'    => 'json',
                        'parser' => 'html5'
                    ]
                ])
            )
            ->willReturn($responseMock);

        $client->setCharset(Validator::CHARSET_ISO_8859_1);
        $client->setHttpClient($httpClientMock);
        $client->validateDocument($document);
    }

    /**
     * Ensure that the validate() method aliases validateDocument()
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getCharset
     * @covers \HtmlValidator\Validator::setCharset
     * @covers \HtmlValidator\Validator::getContentTypeString
     * @covers \HtmlValidator\Validator::getMimeTypeForParser
     * @covers \HtmlValidator\Validator::validateDocument
     * @covers \HtmlValidator\Validator::validate
     * @throws \HtmlValidator\Exception\UnknownParserException
     * @throws \HtmlValidator\Exception\ServerException
     */
    public function testValidateAliasesValidateDocument() {
        $client = new Validator();

        $document = '<p>Dat document</p>';

        $responseMock = $this->getGuzzleResponseMock(array('messages' => array()));

        $httpClientMock = $this->getHttpClientMock();
        $httpClientMock
            ->expects($this->exactly(1))
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->equalTo([
                    'body' => $document,
                    'headers' => ['Content-Type' => 'text/html; charset=iso-8859-1'],
                    'query' => [
                        'out'    => 'json',
                        'parser' => 'html5'
                    ]
                ])
            )
            ->willReturn($responseMock);

        $client->setCharset(Validator::CHARSET_ISO_8859_1);
        $client->setHttpClient($httpClientMock);
        $client->validate($document);
    }

    /**
     * Ensure that the validator sends the correct content type and charset for the parser given
     *
     * @covers \HtmlValidator\Validator::__construct
     * @covers \HtmlValidator\Validator::getCharset
     * @covers \HtmlValidator\Validator::setCharset
     * @covers \HtmlValidator\Validator::getContentTypeString
     * @covers \HtmlValidator\Validator::getMimeTypeForParser
     * @throws \HtmlValidator\Exception\UnknownParserException
     * @throws \HtmlValidator\Exception\ServerException
     */
    public function testValidateNodesSendsCorrectRequest() {
        $client = new Validator();

        $nodes = '<item>Those</item><item>Nodes</itme>';

        $responseMock = $this->getGuzzleResponseMock(['messages' => []], 2);

        $httpClientMock = $this->getHttpClientMock();
        $httpClientMock
            ->expects($this->exactly(1))
            ->method('request')
            ->with(
                $this->equalTo('POST'),
                $this->equalTo(''),
                $this->equalTo([
                    'body' => '<?xml version="1.0" encoding="ISO-8859-1"?>' . "\r\n<root>". $nodes . '</root>',
                    'headers' => ['Content-Type' => 'application/xml; charset=iso-8859-1'],
                    'query' => [
                        'out'    => 'json',
                        'parser' => 'xml'
                    ]
                ])
            )
            ->willReturn($responseMock);

        $client->setParser(Validator::PARSER_XML);
        $client->setCharset(Validator::CHARSET_ISO_8859_1);
        $client->setHttpClient($httpClientMock);
        $client->validateNodes($nodes);
    }

    /**
     * Get a mocked HTTP client
     *
     * @return \GuzzleHttp\Client
     */
    private function getHttpClientMock() {
        $mock = ($this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->getMock());

        return $mock;
    }

    /**
     * Get a guzzle response mock
     *
     * @param  mixed $body Request body
     * @return \GuzzleHttp\Psr7\Response
     */
    private function getGuzzleResponseMock(array $body, int $expected = 2) {
        $mock = ($this->getMockBuilder('GuzzleHttp\Psr7\Response')
            ->disableOriginalConstructor()
            ->getMock());

        $mock
            ->expects($this->exactly(1))
            ->method('getStatusCode')
            ->willReturn(200);

        $mock
            ->expects($this->exactly(1))
            ->method('getHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(['application/json']);

        $mock
            ->expects($this->exactly($expected))
            ->method('getBody')
						->willReturn(Utils::streamFor(json_encode('')));

        return $mock;
    }
}