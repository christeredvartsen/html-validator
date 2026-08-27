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
class ResponseTest extends \PHPUnit\Framework\TestCase {

    /**
     * Ensure construction of non-200 response throws ServerException
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     */
    public function testWillThrowOnNon200Reponse() {
        $this->expectException(\HtmlValidator\Exception\ServerException::class);
        $responseMock = $this->getGuzzleResponseMock();
        $responseMock
            ->expects($this->exactly(2))
            ->method('getStatusCode')
            ->willReturn(500);

        $response = new Response($responseMock);
    }

    /**
     * Ensure construction of response with a non-JSON response throws ServerException
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     */
    public function testWillThrowOnNonJsonResponse() {
        $this->expectException(\HtmlValidator\Exception\ServerException::class);
        $responseMock = $this->getGuzzleResponseMock();
        $responseMock
            ->expects($this->exactly(1))
            ->method('getStatusCode')
            ->willReturn(200);

        $responseMock
            ->expects($this->exactly(1))
            ->method('getHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(['text/html']);

        $response = new Response($responseMock);
    }

    /**
     * Ensure construction of response with an invalid JSON body throws ServerException
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     */
    public function testWillThrowOnInvalidJsonResponse() {
        $this->expectException(\HtmlValidator\Exception\ServerException::class);
        $responseMock = $this->getGuzzleResponseMock();
        $responseMock
            ->expects($this->exactly(1))
            ->method('getStatusCode')
            ->willReturn(200);

        $responseMock
            ->expects($this->exactly(1))
            ->method('getHeader')
            ->with($this->equalTo('Content-Type'))
            ->willReturn(['application/json']);

        $responseMock
            ->expects($this->exactly(1))
            ->method('getBody')
            ->willReturn(Utils::streamFor('{\"incomplete\"}'));

        $response = new Response($responseMock);
    }

    /**
     * Ensure population of errors
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     * @covers \HtmlValidator\Response::parse
     * @covers \HtmlValidator\Response::getErrors
     * @covers \HtmlValidator\Response::hasErrors
     * @throws \HtmlValidator\Exception\ServerException
     */
    public function testWillPopulateErrors() {
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

        $responseMock = $this->getGuzzleResponseMock(true, 1);
        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn(Utils::streamFor(json_encode($data)));

        $response = new Response($responseMock);

        $errors = $response->getErrors();
        $this->assertTrue($response->hasErrors());
        $this->assertSame(2, count($errors));
        $this->assertSame($data['messages'][0]['message'], $errors[0]->getText());
        $this->assertSame($data['messages'][1]['message'], $errors[1]->getText());
    }

    /**
     * Ensure population of warnings
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     * @covers \HtmlValidator\Response::parse
     * @covers \HtmlValidator\Response::getWarnings
     * @covers \HtmlValidator\Response::hasWarnings
     */
    public function testWillPopulateWarnings() {
        $data = array(
            'messages' => array(
                array(
                    'type' => 'warning',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar',
                    'extract' => '<strong>Foo</strong>',
                ),
                array(
                    'type' => 'warning',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican',
                    'extract' => '<em>Pelican</em>',
                ),
            ),
        );

        $responseMock = $this->getGuzzleResponseMock(true, 1);
        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn(Utils::streamFor(json_encode($data)));

        $response = new Response($responseMock);

        $warnings = $response->getWarnings();
        $this->assertTrue($response->hasWarnings());
        $this->assertSame(2, count($warnings));
        $this->assertSame($data['messages'][0]['message'], $warnings[0]->getText());
        $this->assertSame($data['messages'][1]['message'], $warnings[1]->getText());
    }

    /**
     * Ensure population of messages
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     * @covers \HtmlValidator\Response::parse
     * @covers \HtmlValidator\Response::getMessages
     * @covers \HtmlValidator\Response::hasMessages
     */
    public function testWillPopulateMessages() {
        $data = array(
            'messages' => array(
                array(
                    'type' => 'non-document-error',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar message',
                    'extract' => '<strong>Foo</strong>',
                ),
                array(
                    'type' => 'warning',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican warning',
                    'extract' => '<em>Pelican</em>',
                ),
                array(
                    'type' => 'error',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 5,
                    'hiliteLength' => 4,
                    'message' => 'Pimp Pelican error',
                    'extract' => '<em>Pelican</em>',
                ),
            ),
        );

        $responseMock = $this->getGuzzleResponseMock(true, 1);
        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn(Utils::streamFor(json_encode($data)));

        $response = new Response($responseMock);

        $messages = $response->getMessages();
        $this->assertTrue($response->hasMessages());
        $this->assertSame(3, count($messages));
        $this->assertSame($data['messages'][0]['message'], $messages[0]->getText());
        $this->assertSame($data['messages'][1]['message'], $messages[1]->getText());
        $this->assertSame($data['messages'][2]['message'], $messages[2]->getText());
    }

    /**
     * Test proper formatting of errors/warnings
     *
     * @covers \HtmlValidator\Response::__construct
     * @covers \HtmlValidator\Response::validateResponse
     * @covers \HtmlValidator\Response::parse
     * @covers \HtmlValidator\Response::format
     * @covers \HtmlValidator\Response::__toString
     * @covers \HtmlValidator\Response::toHTML
     */
    public function testWillFormat() {
        $data = array(
            'messages' => array(
                array(
                    'type' => 'warning',
                    'firstLine' => 1,
                    'lastLine' => 2,
                    'firstColumn' => 3,
                    'lastColumn' => 4,
                    'hiliteStart' => 5,
                    'hiliteLength' => 6,
                    'message' => 'Foobar',
                    'extract' => '<strong>Foo</strong>',
                ),
                array(
                    'type' => 'error',
                    'firstLine' => 9,
                    'lastLine' => 8,
                    'firstColumn' => 7,
                    'lastColumn' => 6,
                    'hiliteStart' => 9,
                    'hiliteLength' => 7,
                    'message' => 'Pimp Pelican',
                    'extract' => '<em>Pimp Pelican</em>',
                ),
            ),
        );

        $responseMock = $this->getGuzzleResponseMock(true, 1);
        $responseMock
            ->expects($this->exactly(2))
            ->method('getBody')
            ->willReturn(Utils::streamFor(json_encode($data)));

        $response = new Response($responseMock);

        // Plain text
        $expected  = 'warning: Foobar' . PHP_EOL;
        $expected .= 'From line 1, column 3; to line 2, column 4' . PHP_EOL;
        $expected .= '<strong>Foo</strong>' . PHP_EOL . PHP_EOL;

        $expected .= 'error: Pimp Pelican' . PHP_EOL;
        $expected .= 'From line 9, column 7; to line 8, column 6' . PHP_EOL;
        $expected .= '<em>Pimp Pelican</em>';

        $this->assertSame($expected, (string) $response);

        // HTML
        $expected  = '<strong>warning</strong>: Foobar<br>' . PHP_EOL;
        $expected .= 'From line 1, column 3; to line 2, column 4<br>' . PHP_EOL;
        $expected .= '&lt;stro<span class="highlight">ng&gt;Foo</span>&lt;/strong&gt;' . PHP_EOL . PHP_EOL;

        $expected .= '<strong>error</strong>: Pimp Pelican<br>' . PHP_EOL;
        $expected .= 'From line 9, column 7; to line 8, column 6<br>' . PHP_EOL;
        $expected .= '&lt;em&gt;Pimp <span class="highlight">Pelican</span>&lt;/em&gt;';

        $this->assertSame($expected, $response->toHTML());
    }

    /**
     * Get a guzzle response mock
     *
     * @param  boolean $expectSuccess Whether to prepare the mock with the default expectations
     * @return \GuzzleHttp\Psr7\Response
     */
    private function getGuzzleResponseMock(bool $expectSuccess = false, int $expected = 2) {
        $mock = ($this->getMockBuilder('GuzzleHttp\Psr7\Response')
            ->disableOriginalConstructor()
            ->getMock());

        if ($expectSuccess) {
            $mock
                ->expects($this->exactly($expected))
                ->method('getStatusCode')
                ->willReturn(200);

            $mock
                ->expects($this->exactly($expected))
                ->method('getHeader')
                ->with($this->equalTo('Content-Type'))
                ->willReturn(['application/json']);
        }

        return $mock;
    }

}