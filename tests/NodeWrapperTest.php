<?php declare(strict_types=1);

namespace HtmlValidator;

use DOMDocument;
use DOMElement;
use DOMNode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NodeWrapper::class)]
class NodeWrapperTest extends TestCase
{
    private NodeWrapper $wrapper;

    protected function setUp(): void
    {
        $this->wrapper = new NodeWrapper();
    }

    public function testWrapsSingleXmlNodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::XML,
            '<item>Moo</item>',
        );

        $root = $this->loadXmlRoot($wrapped);

        // "<root>"-tag should automatically inserted
        $this->assertSame('root', $root->nodeName, 'XML nodes should be wrapped in a root element.');

        // It shouldn't insert more than the node we gave it
        $this->assertSame(1, $root->childNodes->length, 'The XML wrapper should retain one child node.');

        // The "<item>" node should exist and have the correct value
        $item = $this->getFirstChild($root);
        $this->assertSame('item', $item->nodeName, 'The XML child should retain its element name.');
        $this->assertSame('Moo', $item->nodeValue, 'The XML child should retain its text content.');
    }

    public function testWrapsMultipleXmlNodesCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::XML,
            '<item>Foo</item><item>Bar</item>',
        );

        $root = $this->loadXmlRoot($wrapped);

        // "<root>"-tag should automatically inserted
        $this->assertSame('root', $root->nodeName, 'XML nodes should be wrapped in a root element.');

        // It shouldn't insert more than the two nodes we gave it
        $this->assertSame(2, $root->childNodes->length, 'The XML wrapper should retain both child nodes.');

        // The "<item>" nodes should exist and have the correct values
        $firstItem = $this->getFirstChild($root);
        $this->assertSame('item', $firstItem->nodeName, 'The first XML child should retain its element name.');
        $this->assertSame('Foo', $firstItem->nodeValue, 'The first XML child should retain its text content.');

        $lastItem = $this->getLastChild($root);
        $this->assertSame('item', $lastItem->nodeName, 'The last XML child should retain its element name.');
        $this->assertSame('Bar', $lastItem->nodeValue, 'The last XML child should retain its text content.');
    }

    public function testWrapsXmlNodesInGivenCharset(): void
    {
        $document = new DOMDocument();
        $document->loadXML($this->wrapper->wrap(
            Parser::XML,
            '<item>Moo</item>',
            Charset::ISO88591,
        ));

        $this->assertSame('ISO-8859-1', $document->encoding, 'The XML declaration should use the requested charset.');
    }

    public function testWrapsSingleHtml5NodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML5,
            '<p>Moo</p>',
        );

        // Document should start with doctype html
        $this->assertSame(0, strpos($wrapped, '<!DOCTYPE html>'), 'The HTML5 wrapper should start with an HTML5 doctype.');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups), 'The HTML5 wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertSame(1, $root->childNodes->length, 'The HTML5 wrapper should retain one child node.');

        // The "<p>" node should exist and have the correct value
        $paragraph = $this->getFirstChild($root);
        $this->assertSame('p', $paragraph->nodeName, 'The HTML5 child should retain its element name.');
        $this->assertSame('Moo', $paragraph->nodeValue, 'The HTML5 child should retain its text content.');
    }

    public function testWrapsMultipleHtml5NodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML5,
            '<p>Foo</p><p>Bar</p>',
        );

        // Document should start with doctype html
        $this->assertSame(0, strpos($wrapped, '<!DOCTYPE html>'), 'The HTML5 wrapper should start with an HTML5 doctype.');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups), 'The HTML5 wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertSame(2, $root->childNodes->length, 'The HTML5 wrapper should retain both child nodes.');

        // The "<p>" nodes should exist and have the correct values
        $firstParagraph = $this->getFirstChild($root);
        $this->assertSame('p', $firstParagraph->nodeName, 'The first HTML5 child should retain its element name.');
        $this->assertSame('Foo', $firstParagraph->nodeValue, 'The first HTML5 child should retain its text content.');

        $lastParagraph = $this->getLastChild($root);
        $this->assertSame('p', $lastParagraph->nodeName, 'The last HTML5 child should retain its element name.');
        $this->assertSame('Bar', $lastParagraph->nodeValue, 'The last HTML5 child should retain its text content.');
    }

    public function testWrapsHtml5NodesInGivenCharset(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML,
            '<span>Moo</span>',
            Charset::ISO88591,
        );

        // Expecting: <meta charset="iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]*charset=[\'"](.*?)[\'"]/i', $wrapped, $groups), 'The HTML5 wrapper should contain a charset meta tag.');

        $this->assertSame('iso-8859-1', $groups[1], 'The HTML5 charset meta tag should use the requested charset.');
    }

    public function testWrapsSingleHtml4NodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4,
            '<p>Moo</p>',
        );

        // Document should start with HTML4 doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'Document did not start with HTML4 doctype');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups), 'The HTML4 wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertSame(1, $root->childNodes->length, 'The HTML4 wrapper should retain one child node.');

        // The "<p>" node should exist and have the correct value
        $paragraph = $this->getFirstChild($root);
        $this->assertSame('p', $paragraph->nodeName, 'The HTML4 child should retain its element name.');
        $this->assertSame('Moo', $paragraph->nodeValue, 'The HTML4 child should retain its text content.');
    }

    public function testWrapsMultipleHtml4NodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4,
            '<p>Foo</p><p>Bar</p>',
        );

        // Document should start with the HTML4 doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'Document did not start with HTML4 doctype');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups), 'The HTML4 wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertSame(2, $root->childNodes->length, 'The HTML4 wrapper should retain both child nodes.');

        // The "<p>" nodes should exist and have the correct values
        $firstParagraph = $this->getFirstChild($root);
        $this->assertSame('p', $firstParagraph->nodeName, 'The first HTML4 child should retain its element name.');
        $this->assertSame('Foo', $firstParagraph->nodeValue, 'The first HTML4 child should retain its text content.');

        $lastParagraph = $this->getLastChild($root);
        $this->assertSame('p', $lastParagraph->nodeName, 'The last HTML4 child should retain its element name.');
        $this->assertSame('Bar', $lastParagraph->nodeValue, 'The last HTML4 child should retain its text content.');
    }

    public function testWrapsHtml4NodesInGivenCharset(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4,
            '<span>Moo</span>',
            Charset::ISO88591,
        );

        // Expecting: <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]+charset=(.*?)[\'"]>/i', $wrapped, $groups), 'The HTML4 wrapper should contain a charset meta tag.');

        $this->assertSame('iso-8859-1', $groups[1], 'The HTML4 charset meta tag should use the requested charset.');
    }

    public function testWrapsSingleHtml4TrNodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4TR,
            '<p>Moo</p>',
        );

        // Document should start with HTML4 transitional doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'The HTML4 transitional wrapper should start with its doctype.');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups), 'The HTML4 transitional wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertSame(1, $root->childNodes->length, 'The HTML4 transitional wrapper should retain one child node.');

        // The "<p>" node should exist and have the correct value
        $paragraph = $this->getFirstChild($root);
        $this->assertSame('p', $paragraph->nodeName, 'The HTML4 transitional child should retain its element name.');
        $this->assertSame('Moo', $paragraph->nodeValue, 'The HTML4 transitional child should retain its text content.');
    }

    public function testWrapsMultipleHtml4TrNodeCorrectly(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4TR,
            '<p>Foo</p><p>Bar</p>',
        );

        // Document should start with the HTML4 transitional doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'The HTML4 transitional wrapper should start with its doctype.');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups), 'The HTML4 transitional wrapper should contain a body element.');

        // Load the body into a DOMDocument
        $root = $this->loadXmlRoot($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertSame(2, $root->childNodes->length, 'The HTML4 transitional wrapper should retain both child nodes.');

        // The "<p>" nodes should exist and have the correct values
        $firstParagraph = $this->getFirstChild($root);
        $this->assertSame('p', $firstParagraph->nodeName, 'The first HTML4 transitional child should retain its element name.');
        $this->assertSame('Foo', $firstParagraph->nodeValue, 'The first HTML4 transitional child should retain its text content.');

        $lastParagraph = $this->getLastChild($root);
        $this->assertSame('p', $lastParagraph->nodeName, 'The last HTML4 transitional child should retain its element name.');
        $this->assertSame('Bar', $lastParagraph->nodeValue, 'The last HTML4 transitional child should retain its text content.');
    }

    public function testWrapsHtml4TrNodesInGivenCharset(): void
    {
        $wrapped = $this->wrapper->wrap(
            Parser::HTML4TR,
            '<span>Moo</span>',
            Charset::ISO88591,
        );

        // Expecting: <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]+charset=(.*?)[\'"]>/i', $wrapped, $groups), 'The HTML4 transitional wrapper should contain a charset meta tag.');

        $this->assertSame('iso-8859-1', $groups[1], 'The HTML4 transitional charset meta tag should use the requested charset.');
    }

    private function loadXmlRoot(string $xml): DOMElement
    {
        $document = new DOMDocument();
        $this->assertTrue($document->loadXML($xml), 'Wrapped markup should be valid XML.');
        $this->assertInstanceOf(DOMElement::class, $document->documentElement, 'Wrapped markup should have a document element.');

        return $document->documentElement;
    }

    private function getFirstChild(DOMNode $node): DOMNode
    {
        $this->assertNotNull($node->firstChild, 'The node should have a first child.');

        return $node->firstChild;
    }

    private function getLastChild(DOMNode $node): DOMNode
    {
        $this->assertNotNull($node->lastChild, 'The node should have a last child.');

        return $node->lastChild;
    }
}
