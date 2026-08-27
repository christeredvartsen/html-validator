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
class ValidatorIntegrationTest extends \PHPUnit\Framework\TestCase {

    public function setUp() : void {
        if (!HTML_VALIDATOR_ENABLE_INTEGRATION_TESTS) {
            $this->markTestSkipped('Integration tests disabled in configuration');
        }
    }

    public function testCanValidateUtf8Html5Document() {
        $validator = $this->getValidator();

        $document  = file_get_contents(FIXTURES_DIR . '/document-valid-utf8-html5.html');
        $response  = $validator->validateDocument($document, Validator::CHARSET_UTF_8);

        $this->assertFalse($response->hasErrors(), 'Valid UTF-8 document should produce no errors');
        $this->assertFalse($response->hasWarnings(), 'Valid UTF-8 document should produce no errors');
    }

    public function testCanValidateXmlDocument() {
        $validator = $this->getValidator(Validator::PARSER_XML);

        $document  = file_get_contents(FIXTURES_DIR . '/document-valid-xml.xml');
        $response  = $validator->validateDocument($document);

        $this->assertFalse($response->hasErrors(), 'Valid XML document should produce no errors');
        $this->assertFalse($response->hasWarnings(), 'Valid XML document should produce no warnings');
    }

    public function testCanValidateHtml4Document() {
        $validator = $this->getValidator(Validator::PARSER_HTML4);

        $document  = file_get_contents(FIXTURES_DIR . '/document-valid-html4.html');
        $response  = $validator->validateDocument($document);

        $this->assertCount(1, $response->getErrors());
        $this->assertSame('Obsolete doctype. Expected “<!DOCTYPE html>”.', $response->getErrors()[0]->getText());
        $this->assertFalse($response->hasWarnings(), 'Valid HTML document should produce no warnings');
    }

    public function testDetectsErrorsOnInvalidHtml5() {
        $validator = $this->getValidator();

        $document  = file_get_contents(FIXTURES_DIR . '/document-invalid-utf8-html5.html');
        $response  = $validator->validateDocument($document, Validator::CHARSET_UTF_8);

        $this->assertTrue($response->hasErrors(), 'Invalid HTML5 should produce errors');

        // Can't guarantee order of messages, but assume this one won't go away
        $strayTagFound = false;
        foreach ($response->getErrors() as $error) {
            $strayTagFound = $strayTagFound || strpos($error->getText(), 'Stray end tag “span”.') !== false;
        }

        $this->assertTrue($strayTagFound, 'Stray <span>-tag was not discovered by validator found');
    }

    public function testDetectsErrorsOnInvalidXml() {
        $validator = $this->getValidator(Validator::PARSER_XML);

        $document  = file_get_contents(FIXTURES_DIR . '/document-invalid-xml.xml');
        $response  = $validator->validateDocument($document, Validator::CHARSET_UTF_8);

        $this->assertTrue($response->hasErrors());

        // Can't guarantee order of messages, but assume this one won't go away
        $nameExpectedFound = false;
        foreach ($response->getErrors() as $error) {
            $nameExpectedFound = $nameExpectedFound || ($error->getText() === 'name expected');
        }

        $this->assertTrue($nameExpectedFound, '"name expected"-message was not found');
    }

    public function testDetectsErrorsOnInvalidHtml4() {
        $validator = $this->getValidator(Validator::PARSER_HTML4);

        $document  = file_get_contents(FIXTURES_DIR . '/document-invalid-html4.html');
        $response  = $validator->validateDocument($document, Validator::CHARSET_UTF_8);

        $this->assertTrue($response->hasErrors(), 'Invalid HTML4 should produce errors');

        // Can't guarantee order of messages, but assume this one won't go away
        $strayTagFound = false;
        foreach ($response->getErrors() as $error) {
            $strayTagFound = $strayTagFound || strpos($error->getText(), 'Stray end tag “span”.') !== false;
        }

        $this->assertTrue($strayTagFound, 'Stray <span>-tag was not discovered by validator found');
    }

    public function testValidateUrlWith404() {
        $validator = $this->getValidator();
        $response  = $validator->validateUrl('https://www.w3.org/404');

        $this->assertInstanceOf('\HtmlValidator\Response', $response);
        $this->assertTrue($response->hasErrors(), 'Invalid HTML5 should produce errors');

        $error = $response->getErrors()[0];
        $this->assertTrue(strpos($error->getText(), '404') !== false);
    }

    private function getValidator($parser = Validator::PARSER_HTML5) {
        return new Validator(HTML_VALIDATOR_URL, $parser);
    }
}