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

use DOMDocument;

/**
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 */
class NodeWrapperTest extends \PHPUnit\Framework\TestCase {
    /**
     * NodeWrapper instance
     *
     * @var NodeWrapper
     */
    private $wrapper;

    /**
     * Set up the wrapper
     */
    public function setUp() : void {
        $this->wrapper = new NodeWrapper();
    }

    /**
     * Tear down the wrapper
     */
    public function tearDown() : void {
        $this->wrapper = null;
    }

    /**
     * ================================================================
     * =========================== [ XML ] ============================
     * ================================================================
     */

    /**
     * Ensure the XML wrapper can wrap a single XML node correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInXmlDocument
     * @throws Exception\UnknownParserException
     */
    public function testWrapsSingleXmlNodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_XML,
            '<item>Moo</item>'
        );

        $document = new DOMDocument();
        $document->loadXML($wrapped);

        // "<root>"-tag should automatically inserted
        $this->assertEquals('root', $document->firstChild->nodeName);

        // It shouldn't insert more than the node we gave it
        $this->assertEquals(1, $document->firstChild->childNodes->length);

        // The "<item>" node should exist and have the correct value
        $this->assertEquals('item', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Moo', $document->firstChild->firstChild->nodeValue);
    }

    /**
     * Ensure the XML wrapper can wrap multiple XML nodes correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInXmlDocument
     * @throws Exception\UnknownParserException
     */
    public function testWrapsMultipleXmlNodesCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_XML,
            '<item>Foo</item><item>Bar</item>'
        );

        $document = new DOMDocument();
        $document->loadXML($wrapped);

        // "<root>"-tag should automatically inserted
        $this->assertEquals('root', $document->firstChild->nodeName);

        // It shouldn't insert more than the two nodes we gave it
        $this->assertEquals(2, $document->firstChild->childNodes->length);

        // The "<item>" nodes should exist and have the correct values
        $this->assertEquals('item', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Foo', $document->firstChild->firstChild->nodeValue);

        $this->assertEquals('item', $document->firstChild->lastChild->nodeName);
        $this->assertEquals('Bar', $document->firstChild->lastChild->nodeValue);
    }

    /**
     * Ensure the XML wrapper uses the passed charset
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInXmlDocument
     * @throws Exception\UnknownParserException
     */
    public function testWrapsXmlNodesInGivenCharset() {
        $document = new DOMDocument();
        $document->loadXML($this->wrapper->wrap(
            Validator::PARSER_XML,
            '<item>Moo</item>',
            'iso-8859-1'
        ));

        $this->assertEquals('ISO-8859-1', $document->encoding);
    }

    /**
     * ================================================================
     * ========================== [ HTML5 ] ===========================
     * ================================================================
     */

    /**
     * Ensure the HTML5 wrapper can wrap a single HTML5 node correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml5Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsSingleHtml5NodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML5,
            '<p>Moo</p>'
        );

        // Document should start with doctype html
        $this->assertSame(0, strpos($wrapped, '<!DOCTYPE html>'));

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertEquals(1, $document->firstChild->childNodes->length);

        // The "<p>" node should exist and have the correct value
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Moo', $document->firstChild->firstChild->nodeValue);
    }

    /**
     * Ensure the HTML5 wrapper can wrap multiple HTML5 nodes correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml5Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsMultipleHtml5NodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML5,
            '<p>Foo</p><p>Bar</p>'
        );

        // Document should start with doctype html
        $this->assertSame(0, strpos($wrapped, '<!DOCTYPE html>'));

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertEquals(2, $document->firstChild->childNodes->length);

        // The "<p>" nodes should exist and have the correct values
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Foo', $document->firstChild->firstChild->nodeValue);

        $this->assertEquals('p', $document->firstChild->lastChild->nodeName);
        $this->assertEquals('Bar', $document->firstChild->lastChild->nodeValue);
    }

    /**
     * Ensure the HTML5 wrapper uses the passed charset
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml5Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsHtml5NodesInGivenCharset() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML,
            '<span>Moo</span>',
            'ISO-8859-1'
        );

        // Expecting: <meta charset="iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]*charset=[\'"](.*?)[\'"]/i', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        $this->assertEquals('iso-8859-1', $groups[1]);
    }

    /**
     * ================================================================
     * ========================== [ HTML4 ] ===========================
     * ================================================================
     */

    /**
     * Ensure the HTML4 wrapper can wrap a single HTML4 node correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsSingleHtml4NodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4,
            '<p>Moo</p>'
        );

        // Document should start with HTML4 doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'Document did not start with HTML4 doctype');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertEquals(1, $document->firstChild->childNodes->length);

        // The "<p>" node should exist and have the correct value
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Moo', $document->firstChild->firstChild->nodeValue);
    }

    /**
     * Ensure the HTML4 wrapper can wrap multiple HTML4 nodes correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsMultipleHtml4NodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4,
            '<p>Foo</p><p>Bar</p>'
        );

        // Document should start with the HTML4 doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype), 'Document did not start with HTML4 doctype');

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertEquals(2, $document->firstChild->childNodes->length);

        // The "<p>" nodes should exist and have the correct values
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Foo', $document->firstChild->firstChild->nodeValue);

        $this->assertEquals('p', $document->firstChild->lastChild->nodeName);
        $this->assertEquals('Bar', $document->firstChild->lastChild->nodeValue);
    }

    /**
     * Ensure the HTML4 wrapper uses the passed charset
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsHtml4NodesInGivenCharset() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4,
            '<span>Moo</span>',
            'ISO-8859-1'
        );

        // Expecting: <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]+charset=(.*?)[\'"]>/i', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        $this->assertEquals('iso-8859-1', $groups[1]);
    }


    /**
     * ================================================================
     * ==================== [ HTML4 Transitional ] ====================
     * ================================================================
     */

    /**
     * Ensure the HTML4-TR wrapper can wrap a single HTML4 node correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsSingleHtml4TrNodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4TR,
            '<p>Moo</p>'
        );

        // Document should start with HTML4 transitional doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype));

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/s', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It shouldn't insert more than the node we gave it
        $this->assertEquals(1, $document->firstChild->childNodes->length);

        // The "<p>" node should exist and have the correct value
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Moo', $document->firstChild->firstChild->nodeValue);
    }

    /**
     * Ensure the HTML4-TR wrapper can wrap multiple HTML4 nodes correctly
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsMultipleHtml4TrNodeCorrectly() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4TR,
            '<p>Foo</p><p>Bar</p>'
        );

        // Document should start with the HTML4 transitional doctype
        $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        $this->assertSame(0, strpos($wrapped, $doctype));

        // Ensure body tag has been inserted
        // (I know, regex and such: DOMDocument fails on meta charset tag)
        $this->assertSame(1, preg_match('/(<body[^>]*>.*<\/body>)/si', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        // Load the body into a DOMDocument
        $document = new DOMDocument();
        $document->loadXML($groups[1]);

        // It should insert both nodes that we gave it
        $this->assertEquals(2, $document->firstChild->childNodes->length);

        // The "<p>" nodes should exist and have the correct values
        $this->assertEquals('p', $document->firstChild->firstChild->nodeName);
        $this->assertEquals('Foo', $document->firstChild->firstChild->nodeValue);

        $this->assertEquals('p', $document->firstChild->lastChild->nodeName);
        $this->assertEquals('Bar', $document->firstChild->lastChild->nodeValue);
    }

    /**
     * Ensure the HTML4-TR wrapper uses the passed charset
     *
     * @covers \HtmlValidator\NodeWrapper::wrap
     * @covers \HtmlValidator\NodeWrapper::wrapInHtml4Document
     * @throws Exception\UnknownParserException
     */
    public function testWrapsHtml4TrNodesInGivenCharset() {
        $wrapped = $this->wrapper->wrap(
            Validator::PARSER_HTML4TR,
            '<span>Moo</span>',
            'ISO-8859-1'
        );

        // Expecting: <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
        $this->assertSame(1, preg_match('/<meta[^>]+charset=(.*?)[\'"]>/i', $wrapped, $groups));
        $this->assertSame(2, count($groups));

        $this->assertEquals('iso-8859-1', $groups[1]);
    }

}