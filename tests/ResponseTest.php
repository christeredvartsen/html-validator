<?php declare(strict_types=1);

namespace HtmlValidator;

use GuzzleHttp\Psr7\NoSeekStream;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Psr7\Utils;
use HtmlValidator\Exception\ServerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

use const PHP_EOL;

#[CoversClass(Response::class)]
class ResponseTest extends TestCase
{
    public function testWillThrowOnNon200Reponse(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Expected HTTP 200, got: 500');
        $this->expectExceptionCode(500);
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 500,
        ]));
    }

    public function testWillThrowOnNonJsonResponse(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Expected Content-Type application/json, got: text/html');
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'text/html',
        ]));
    }

    public function testWillThrowOnResponseWithoutContentType(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Expected Content-Type application/json, got:');
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => '',
        ]));
    }

    public function testWillAcceptCaseInsensitiveJsonContentType(): void
    {
        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'Application/JSON; charset=UTF-8',
            'getBody' => Utils::streamFor('{"messages":[]}'),
        ]));

        $this->assertFalse($response->hasMessages(), 'A case-insensitive JSON media type should be accepted.');
    }

    public function testWillRejectNonJsonContentTypeWithJsonParameter(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Expected Content-Type application/json, got: text/plain; profile=application/json');
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'text/plain; profile=application/json',
        ]));
    }

    public function testWillThrowOnInvalidJsonResponse(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Control character error, possibly incorrectly encoded');
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor('{"incompl'),
        ]));
    }

    public function testWillThrowOnInvalidJsonStructure(): void
    {
        $this->expectException(ServerException::class);
        $this->expectExceptionMessage('Invalid JSON structure from validator.');
        new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor('{}'),
        ]));
    }

    public function testWillReadResponseBodyOnce(): void
    {
        $httpResponse = $this->createMock(ResponseInterface::class);
        $httpResponse
            ->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $httpResponse
            ->expects($this->once())
            ->method('getHeaderLine')
            ->with('Content-Type')
            ->willReturn('application/json');
        $httpResponse
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(Utils::streamFor('{"messages":[]}'));

        $response = new Response($httpResponse);

        $this->assertFalse($response->hasMessages(), 'A valid response body should be parsed after its single read.');
    }

    public function testWillParseNonSeekableResponseBodies(): void
    {
        $response = new Response(new Psr7Response(
            200,
            ['Content-Type' => 'application/json'],
            new NoSeekStream(Utils::streamFor('{"messages":[{"type":"error","message":"Invalid document"}]}')),
        ));

        $this->assertTrue($response->hasErrors(), 'A non-seekable response body should be parsed successfully.');
        $this->assertSame('Invalid document', $response->getErrors()[0]->getText(), 'The error from a non-seekable response body should be retained.');
    }

    public function testWillSkipMalformedMessages(): void
    {
        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor('{"messages":["invalid",{"type":"warning"},{"type":"error","message":"Valid error"}]}'),
        ]));

        $this->assertTrue($response->hasErrors(), 'Valid messages should be retained after malformed messages are skipped.');
        $this->assertCount(1, $response->getMessages(), 'Only the valid message should be retained.');
        $this->assertSame('Valid error', $response->getErrors()[0]->getText(), 'The retained error message should be accessible.');
    }

    public function testWillRetainMessagesWithUnknownSubtypes(): void
    {
        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor('{"messages":[{"type":"info","subtype":"notice","message":"Informational message"}]}'),
        ]));

        $this->assertTrue($response->hasMessages(), 'Messages with unknown subtypes should be retained.');
        $this->assertFalse($response->hasWarnings(), 'An unknown info subtype should not be classified as a warning.');
        $this->assertSame('Informational message', $response->getMessages()[0]->getText(), 'The message text should be retained.');
    }

    public function testWillPopulateErrors(): void
    {
        $data = [
            'messages' => [
                [
                    'type' => 'error',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar',
                    'extract' => '<strong>Foo</strong>',
                ],
                [
                    'type' => 'error',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican',
                    'extract' => '<em>Pelican</em>',
                ],
            ],
        ];

        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor((string) json_encode($data)),
        ]));

        $this->assertTrue($response->hasErrors(), 'Error messages should be classified as errors.');
        $errors = $response->getErrors();
        $this->assertCount(2, $errors, 'All error messages should be returned.');
        $this->assertSame($data['messages'][0]['message'], $errors[0]->getText(), 'The first error text should be retained.');
        $this->assertSame($data['messages'][1]['message'], $errors[1]->getText(), 'The second error text should be retained.');
    }

    public function testWillPopulateWarnings(): void
    {
        $data = [
            'messages' => [
                [
                    'type' => 'info',
                    'subtype' => 'warning',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar',
                    'extract' => '<strong>Foo</strong>',
                ],
                [
                    'type' => 'info',
                    'subtype' => 'warning',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican',
                    'extract' => '<em>Pelican</em>',
                ],
            ],
        ];

        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor((string) json_encode($data)),
        ]));

        $this->assertTrue($response->hasWarnings(), 'Warning messages should be classified as warnings.');
        $warnings = $response->getWarnings();
        $this->assertCount(2, $warnings, 'All warning messages should be returned.');
        $this->assertSame($data['messages'][0]['message'], $warnings[0]->getText(), 'The first warning text should be retained.');
        $this->assertSame($data['messages'][1]['message'], $warnings[1]->getText(), 'The second warning text should be retained.');
    }

    public function testWillPopulateMessages(): void
    {
        $data = [
            'messages' => [
                [
                    'type' => 'non-document-error',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar message',
                    'extract' => '<strong>Foo</strong>',
                ],
                [
                    'type' => 'info',
                    'subtype' => 'warning',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican warning',
                    'extract' => '<em>Pelican</em>',
                ],
                [
                    'type' => 'error',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican error',
                    'extract' => '<em>Pelican</em>',
                ],
            ],
        ];

        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor((string) json_encode($data)),
        ]));

        $this->assertTrue($response->hasMessages(), 'Parsed messages should be reported.');
        $messages = $response->getMessages();
        $this->assertCount(3, $messages, 'All message types should be returned.');
        $this->assertSame($data['messages'][0]['message'], $messages[0]->getText(), 'The non-document error text should be retained.');
        $this->assertSame($data['messages'][1]['message'], $messages[1]->getText(), 'The warning text should be retained.');
        $this->assertSame($data['messages'][2]['message'], $messages[2]->getText(), 'The error text should be retained.');
    }

    public function testWillFormat(): void
    {
        $data = [
            'messages' => [
                [
                    'type' => 'info',
                    'subtype' => 'warning',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar',
                    'extract' => '<strong>Foo</strong>',
                ],
                [
                    'type' => 'error',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 9,
                    'hiliteLength' => 7,
                    'message' => 'Pimp Pelican',
                    'extract' => '<em>Pimp Pelican</em>',
                ],
            ],
        ];

        $response = new Response($this->createConfiguredStub(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getHeaderLine' => 'application/json',
            'getBody' => Utils::streamFor((string) json_encode($data)),
        ]));

        // Plain text
        $expected = 'info: Foobar'.PHP_EOL;
        $expected .= 'From line 1, column 3; to line 2, column 4'.PHP_EOL;
        $expected .= '<strong>Foo</strong>'.PHP_EOL.PHP_EOL;

        $expected .= 'error: Pimp Pelican'.PHP_EOL;
        $expected .= 'From line 9, column 7; to line 8, column 6'.PHP_EOL;
        $expected .= '<em>Pimp Pelican</em>';

        $this->assertSame($expected, (string) $response, 'Response should use the expected plain-text format.');

        // HTML
        $expected = '<strong>info</strong>: Foobar<br>'.PHP_EOL;
        $expected .= 'From line 1, column 3; to line 2, column 4<br>'.PHP_EOL;
        $expected .= '&lt;stro<span class="highlight">ng&gt;Foo</span>&lt;/strong&gt;'.PHP_EOL.PHP_EOL;

        $expected .= '<strong>error</strong>: Pimp Pelican<br>'.PHP_EOL;
        $expected .= 'From line 9, column 7; to line 8, column 6<br>'.PHP_EOL;
        $expected .= '&lt;em&gt;Pimp <span class="highlight">Pelican</span>&lt;/em&gt;';

        $this->assertSame($expected, $response->toHTML(), 'Response should use the expected HTML format.');
    }
}
