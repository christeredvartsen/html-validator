<?php declare(strict_types=1);

namespace HtmlValidator;

use HtmlValidator\Exception\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function sprintf;

use const PHP_EOL;

#[CoversClass(Message::class)]
class MessageTest extends TestCase
{
    public function testCanPopulate(): void
    {
        $data = [
            'type' => 'error',
            'firstLine' => 1,
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        ];

        $message = new Message($data);
        $this->assertSame($data['type'], $message->getType(), 'Message type should be populated.');
        $this->assertSame($data['firstLine'], $message->getFirstLine(), 'Message first line should be populated.');
        $this->assertSame($data['lastLine'], $message->getLastLine(), 'Message last line should be populated.');
        $this->assertSame($data['firstColumn'], $message->getFirstColumn(), 'Message first column should be populated.');
        $this->assertSame($data['lastColumn'], $message->getLastColumn(), 'Message last column should be populated.');
        $this->assertSame($data['hiliteStart'], $message->getHighlightStart(), 'Message highlight start should be populated.');
        $this->assertSame($data['hiliteLength'], $message->getHighlightLength(), 'Message highlight length should be populated.');
        $this->assertSame($data['message'], $message->getText(), 'Message text should be populated.');
        $this->assertSame($data['extract'], $message->getExtract(), 'Message extract should be populated.');
    }

    public function testPopulatesFirstLineDataIfNotPresent(): void
    {
        $data = [
            'type' => 'error',
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        ];

        $message = new Message($data);
        $this->assertSame($data['lastLine'], $message->getFirstLine(), 'First line should default to the last line.');
    }

    public function testWillRejectAnUnsupportedMessageType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message type must be info, error, or non-document-error.');

        new Message(['type' => 'warning']);
    }

    public function testWillRejectAMissingMessageType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message type must be a non-empty string.');

        new Message();
    }

    public function testWillIgnoreAnUnknownMessageSubtype(): void
    {
        $message = new Message([
            'type' => 'error',
            'subtype' => 'future-subtype',
        ]);

        $this->assertSame('error', $message->getType(), 'A message with an unknown subtype should retain its type.');
    }

    public function testWillRejectAnInvalidConsumedField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message lastLine must be an integer.');

        new Message([
            'type' => 'error',
            'lastLine' => '1',
        ]);
    }

    public function testWillRejectANonStringConsumedField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message extract must be a string.');

        new Message([
            'type' => 'error',
            'extract' => 1,
        ]);
    }

    public function testCorrectPlainTextFormatting(): void
    {
        $data = [
            'type' => 'error',
            'lastLine' => 2,
            'firstColumn' => 3,
            'lastColumn' => 4,
            'hiliteStart' => 5,
            'hiliteLength' => 6,
            'message' => 'Foobar',
            'extract' => '<strong>Foo</strong>',
        ];

        $message = new Message($data);

        $format = '%s: %s'.PHP_EOL;
        $format .= 'From line %d, column %d; ';
        $format .= 'to line %d, column %d'.PHP_EOL;
        $format .= '%s';

        $expectedMessage = sprintf(
            $format,
            $data['type'],
            $data['message'],
            $data['lastLine'],
            $data['firstColumn'],
            $data['lastLine'],
            $data['lastColumn'],
            $data['extract'],
        );

        $this->assertSame($expectedMessage, (string) $message, 'Message should use the expected plain-text format.');
    }

    public function testCorrectHtmlFormatting(): void
    {
        $data = [
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 21,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is <strong>Imbo</strong>?',
        ];

        $message = new Message($data);

        $expected = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>'.PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>'.PHP_EOL;
        $expected .= 'How awesome is <span class="highlight">&lt;strong&gt;Imbo&lt;/strong&gt;</span>?';

        $this->assertSame($expected, $message->toHTML(), 'Message should use the expected HTML format.');
    }

    public function testHtmlFormattingUsesUtf16OffsetsForMultibyteCharacters(): void
    {
        $message = new Message([
            'type' => 'error',
            'message' => 'Multibyte character',
            'extract' => 'éX',
            'hiliteStart' => 1,
            'hiliteLength' => 1,
        ]);

        $expected = '<strong>error</strong>: Multibyte character<br>'.PHP_EOL;
        $expected .= '&eacute;<span class="highlight">X</span>';

        $this->assertSame($expected, $message->toHTML(), 'The default highlighter should not split multibyte UTF-8 characters.');
    }

    public function testHtmlFormattingUsesUtf16OffsetsForAstralCharacters(): void
    {
        $message = new Message([
            'type' => 'error',
            'message' => 'Astral character',
            'extract' => '😀X',
            'hiliteStart' => 2,
            'hiliteLength' => 1,
        ]);

        $expected = '<strong>error</strong>: Astral character<br>'.PHP_EOL;
        $expected .= '😀<span class="highlight">X</span>';

        $this->assertSame($expected, $message->toHTML(), 'The default highlighter should count astral characters as two UTF-16 units.');
    }

    public function testCanSetCustomCssClassNameForHighlighting(): void
    {
        $data = [
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 21,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is <strong>Imbo</strong>?',
        ];

        $message = new Message($data);
        $message->setHighlightClassName('pimp-pelican');

        $expected = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>'.PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>'.PHP_EOL;
        $expected .= 'How awesome is <span class="pimp-pelican">&lt;strong&gt;Imbo&lt;/strong&gt;</span>?';

        $this->assertSame($expected, $message->toHTML(), 'Message should use the configured highlight CSS class.');
    }

    public function testCanUseCustomHighlighter(): void
    {
        $data = [
            'type' => 'error',
            'lastLine' => 1,
            'firstColumn' => 15,
            'lastColumn' => 37,
            'hiliteStart' => 15,
            'hiliteLength' => 4,
            'message' => '“Imbo” is simply too awesome for words',
            'extract' => 'How awesome is Imbo?',
        ];

        $message = new Message($data);
        $message->setHighlighter(static fn (string $str, int $start, int $length): string => sprintf(
            '%s[¤¤]%s[/¤¤]%s',
            substr($str, 0, $start),
            substr($str, $start, $length),
            substr($str, $start + $length),
        ));

        $expected = '<strong>error</strong>: &ldquo;Imbo&rdquo; is simply too awesome for words<br>'.PHP_EOL;
        $expected .= 'From line 1, column 15; to line 1, column 37<br>'.PHP_EOL;
        $expected .= 'How awesome is [¤¤]Imbo[/¤¤]?';

        $this->assertSame($expected, $message->toHTML(), 'Message should use the configured custom highlighter.');
    }
}
