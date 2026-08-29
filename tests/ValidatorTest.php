<?php declare(strict_types=1);

namespace HtmlValidator;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use HtmlValidator\Exception\InvalidArgumentException;
use HtmlValidator\Exception\ServerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(Validator::class)]
class ValidatorTest extends TestCase
{
    private function getResponseStub(): ResponseInterface&Stub
    {
        return $this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor((string) json_encode(['messages' => []])),
        ]);
    }

    public function testCanSetAndGetParsers(): void
    {
        $validator = new Validator();
        $this->assertSame(Validator::PARSER_HTML5, $validator->getParser(), 'HTML5 should be the default parser.');
        $this->assertSame($validator, $validator->setParser(Validator::PARSER_XML), 'Setting the parser should be fluent.');
        $this->assertSame(Validator::PARSER_XML, $validator->getParser(), 'The configured parser should be returned.');
    }

    public function testWillRejectUnknownParser(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Validator())->setParser('unknown');
    }

    public function testCanSetAndGetCharset(): void
    {
        $validator = new Validator();
        $this->assertSame(Validator::CHARSET_UTF_8, $validator->getCharset(), 'UTF-8 should be the default charset.');
        $this->assertSame($validator, $validator->setCharset(Validator::CHARSET_ISO_8859_1), 'Setting the charset should be fluent.');
        $this->assertSame(Validator::CHARSET_ISO_8859_1, $validator->getCharset(), 'The configured charset should be returned.');
    }

    public function testValidateDocumentSendsCorrectContentType(): void
    {
        $document = '<p>Dat document</p>';

        $httpClient = $this->createMock(Client::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '', [
                'body' => $document,
                'headers' => ['Content-Type' => 'text/html; charset=utf-8'],
                'query' => [
                    'out' => 'json',
                    'parser' => 'html5',
                ],
            ])
            ->willReturn($this->getResponseStub());

        $s = $this->getResponseStub();

        (new Validator())
            ->setHttpClient($httpClient)
            ->validateDocument($document);
    }

    public function testValidateDocumentSendsCorrectContentTypeWithExplicitCharset(): void
    {
        $document = '<p>Dat document</p>';

        $httpClient = $this->createMock(Client::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '', [
                'body' => $document,
                'headers' => ['Content-Type' => 'text/html; charset=iso-8859-1'],
                'query' => [
                    'out' => 'json',
                    'parser' => 'html5',
                ],
            ])
            ->willReturn($this->getResponseStub());

        (new Validator())
            ->setCharset(Validator::CHARSET_ISO_8859_1)
            ->setHttpClient($httpClient)
            ->validateDocument($document);
    }

    public function testValidateNodesSendsCorrectRequest(): void
    {
        $nodes = '<item>Those</item><item>Nodes</itme>';

        $httpClient = $this->createMock(Client::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '', [
                'body' => '<?xml version="1.0" encoding="ISO-8859-1"?>'."\n<root>".$nodes.'</root>',
                'headers' => ['Content-Type' => 'application/xml; charset=iso-8859-1'],
                'query' => [
                    'out' => 'json',
                    'parser' => 'xml',
                ],
            ])
            ->willReturn($this->getResponseStub());

        (new Validator())
            ->setParser(Validator::PARSER_XML)
            ->setCharset(Validator::CHARSET_ISO_8859_1)
            ->setHttpClient($httpClient)
            ->validateNodes($nodes);
    }

    public function testValidateUrlNormalizesGuzzleExceptions(): void
    {
        $exception = new ConnectException('Connection failed', new Request('GET', 'https://validator.nu'));
        $httpClient = $this->createMock(Client::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        try {
            (new Validator())
                ->setHttpClient($httpClient)
                ->validateUrl('https://example.com');
            $this->fail('Expected a ServerException.');
        } catch (ServerException $e) {
            $this->assertSame('Unable to validate URL.', $e->getMessage(), 'URL validation should expose a stable library error message.');
            $this->assertSame($exception, $e->getPrevious(), 'URL validation should retain the Guzzle exception as the cause.');
        }
    }

    public function testValidateDocumentNormalizesGuzzleExceptions(): void
    {
        $exception = new ConnectException('Connection failed', new Request('POST', 'https://validator.nu'));
        $httpClient = $this->createMock(Client::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException($exception);

        try {
            (new Validator())
                ->setHttpClient($httpClient)
                ->validateDocument('<p>Document</p>');
            $this->fail('Expected a ServerException.');
        } catch (ServerException $e) {
            $this->assertSame('Unable to validate document.', $e->getMessage(), 'Document validation should expose a stable library error message.');
            $this->assertSame($exception, $e->getPrevious(), 'Document validation should retain the Guzzle exception as the cause.');
        }
    }
}
