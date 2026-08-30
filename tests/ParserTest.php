<?php declare(strict_types=1);

namespace HtmlValidator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Parser::class)]
class ParserTest extends TestCase
{
    /**
     * @return iterable<string,array{Parser,string}>
     */
    public static function parserMimeTypes(): iterable
    {
        yield 'XML' => [Parser::XML, 'application/xml'];
        yield 'XML DTD' => [Parser::XMLDTD, 'application/xml-dtd'];
        yield 'HTML' => [Parser::HTML, 'text/html'];
        yield 'HTML5' => [Parser::HTML5, 'text/html'];
        yield 'HTML4' => [Parser::HTML4, 'text/html'];
        yield 'HTML4 transitional' => [Parser::HTML4TR, 'text/html'];
    }

    #[DataProvider('parserMimeTypes')]
    public function testReturnsExpectedMimeType(Parser $parser, string $expectedMimeType): void
    {
        $this->assertSame($expectedMimeType, $parser->getMimeType(), 'The parser should map to its expected MIME type.');
    }
}
