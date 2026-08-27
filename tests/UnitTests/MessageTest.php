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

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class MessageTest extends \PHPUnit\Framework\TestCase {

    /**
     * Test construction and population of a message instance
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::getType
     * @covers \HtmlValidator\Message::getFirstLine
     * @covers \HtmlValidator\Message::getLastLine
     * @covers \HtmlValidator\Message::getFirstColumn
     * @covers \HtmlValidator\Message::getLastColumn
     * @covers \HtmlValidator\Message::getHighlightStart
     * @covers \HtmlValidator\Message::getHighlightLength
     * @covers \HtmlValidator\Message::getText
     * @covers \HtmlValidator\Message::getExtract
     */
    public function testCanPopulate() {
        $data = array(
            'type' => 'error',
            'firstLine' => 1,
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        );

        $message = new Message($data);
        $this->assertSame($data['type'], $message->getType());
        $this->assertSame($data['firstLine'], $message->getFirstLine());
        $this->assertSame($data['lastLine'], $message->getLastLine());
        $this->assertSame($data['firstColumn'], $message->getFirstColumn());
        $this->assertSame($data['lastColumn'], $message->getLastColumn());
        $this->assertSame($data['hiliteStart'], $message->getHighlightStart());
        $this->assertSame($data['hiliteLength'], $message->getHighlightLength());
        $this->assertSame($data['message'], $message->getText());
        $this->assertSame($data['extract'], $message->getExtract());
    }

    /**
     * Ensure first line gets populated if not present in data set
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::getFirstLine
     */
    public function testPopulatesFirstLineDataIfNotPresent() {
        $data = array(
            'type' => 'error',
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        );

        $message = new Message($data);
        $this->assertSame($data['lastLine'], $message->getFirstLine());
    }

    /**
     * Ensure proper plain-text formatting of message
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::__toString
     * @covers \HtmlValidator\Message::format
     */
    public function testCorrectPlainTextFormatting() {
        $data = array(
            'type' => 'error',
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        );

        $message = new Message($data);

        $format  = '%s: %s' . PHP_EOL;
        $format .= 'From line %d, column %d; ' ;
        $format .= 'to line %d, column %d' . PHP_EOL;
        $format .= '%s';

        $expectedMessage = sprintf(
            $format,
            $data['type'],
            $data['message'],
            $data['lastLine'],
            $data['firstColumn'],
            $data['lastLine'],
            $data['lastColumn'],
            $data['extract']
        );

        $this->assertSame($expectedMessage, (string) $message);
    }

    /**
     * Ensure proper HTML formatting of message
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::toHTML
     * @covers \HtmlValidator\Message::format
     * @covers \HtmlValidator\Message::highlight
     */
    public function testCorrectHtmlFormatting() {
        $data = array(
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 21,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is <strong>Imbo</strong>?',
        );

        $message = new Message($data);

        $expected  = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>' . PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>' . PHP_EOL;
        $expected .= 'How awesome is <span class="highlight">&lt;strong&gt;Imbo&lt;/strong&gt;</span>?';

        $this->assertSame($expected, $message->toHTML());
    }

    /**
     * Ensure ability to specify custom css class name
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::toHTML
     * @covers \HtmlValidator\Message::format
     * @covers \HtmlValidator\Message::highlight
     * @covers \HtmlValidator\Message::setHighlightClassName
     */
    public function testCanSetCustomCssClassNameForHighlighting() {
        $data = array(
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 21,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is <strong>Imbo</strong>?',
        );

        $message = new Message($data);
        $message->setHighlightClassName('pimp-pelican');

        $expected  = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>' . PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>' . PHP_EOL;
        $expected .= 'How awesome is <span class="pimp-pelican">&lt;strong&gt;Imbo&lt;/strong&gt;</span>?';

        $this->assertSame($expected, $message->toHTML());
    }

    /**
     * Ensure ability to specify custom highlighter
     *
     * @covers \HtmlValidator\Message::__construct
     * @covers \HtmlValidator\Message::toHTML
     * @covers \HtmlValidator\Message::format
     * @covers \HtmlValidator\Message::setHighlighter
     * @throws Exception
     */
    public function testCanUseCustomHighlighter() {
        $data = array(
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 4,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is Imbo?',
        );

        $message = new Message($data);
        $message->setHighlighter(function($str, $start, $length) {
            $first = substr($str, 0, $start);
            $juice = substr($str, $start, $length);
            $slack = substr($str, $start + $length);
            return $first . '[¤¤]' . $juice . '[/¤¤]' . $slack;
        });

        $expected  = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>' . PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>' . PHP_EOL;
        $expected .= 'How awesome is [¤¤]Imbo[/¤¤]?';

        $this->assertSame($expected, $message->toHTML());
    }

}