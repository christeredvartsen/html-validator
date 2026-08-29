<?php declare(strict_types=1);

namespace HtmlValidator;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function sprintf;

#[CoversClass(Validator::class)]
#[Group('integration')]
class ValidatorIntegrationTest extends TestCase
{
    private const VALIDATOR_URL = 'http://localhost:8888';
    private const FIXTURE_SERVER_URL = 'http://fixtures';

    protected function setUp(): void
    {
        set_error_handler(static fn (): bool => true);
        $connection = fsockopen('localhost', 8_888);
        restore_error_handler();

        if (false === $connection) {
            $this->markTestSkipped('Start the Docker Compose services to run the integration tests');
        }

        fclose($connection);
    }

    private function getFixture(string $filename): string
    {
        $path = __DIR__.'/fixtures/'.$filename;
        if (!file_exists($path)) {
            $this->fail('Fixture file not found: '.$path);
        }

        return (string) file_get_contents($path);
    }

    private function getFixtureUrl(string $filename): string
    {
        return sprintf('%s/%s', self::FIXTURE_SERVER_URL, $filename);
    }

    private function getValidator(string $parser = Validator::PARSER_HTML5): Validator
    {
        return new Validator(self::VALIDATOR_URL, $parser);
    }

    public function testCanValidateUtf8Html5Document(): void
    {
        $response = $this->getValidator()->validateDocument(
            $this->getFixture('document-valid-utf8-html5.html'),
            Validator::CHARSET_UTF_8,
        );

        $this->assertFalse($response->hasErrors(), 'Valid UTF-8 document should produce no errors');
        $this->assertFalse($response->hasWarnings(), 'Valid UTF-8 document should produce no warnings');
    }

    public function testCanValidateXmlDocument(): void
    {
        $response = $this->getValidator(Validator::PARSER_XML)->validateDocument(
            $this->getFixture('document-valid-xml.xml'),
        );

        $this->assertFalse($response->hasErrors(), 'Valid XML document should produce no errors');
        $this->assertFalse($response->hasWarnings(), 'Valid XML document should produce no warnings');
    }

    public function testCanValidateHtml4Document(): void
    {
        $response = $this->getValidator(Validator::PARSER_HTML4)->validateDocument(
            $this->getFixture('document-valid-html4.html'),
        );

        $this->assertTrue($response->hasErrors(), 'Valid HTML4 document should produce errors');
        $this->assertCount(1, $response->getErrors(), 'Expected exactly 1 error for valid HTML4 document');
        $this->assertSame('Obsolete doctype. Expected “<!DOCTYPE html>”.', $response->getErrors()[0]->getText(), 'Expected obsolete doctype error for HTML4 document');
        $this->assertFalse($response->hasWarnings(), 'Valid HTML4 document should produce no warnings');
    }

    public function testDetectsErrorsOnInvalidHtml5(): void
    {
        $response = $this->getValidator()->validateDocument(
            $this->getFixture('document-invalid-utf8-html5.html'),
            Validator::CHARSET_UTF_8,
        );

        $this->assertTrue($response->hasErrors(), 'Invalid HTML5 document should produce errors');
        $this->assertFalse($response->hasWarnings(), 'Invalid HTML5 document should produce no warnings');

        $found = false;
        foreach ($response->getErrors() as $error) {
            if ('Stray end tag “span”.' === $error->getText()) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Expected stray end tag error for <span> in HTML5 document');
    }

    public function testDetectsErrorsOnInvalidXml(): void
    {
        $response = $this->getValidator(Validator::PARSER_XML)->validateDocument(
            $this->getFixture('document-invalid-xml.xml'),
            Validator::CHARSET_UTF_8,
        );

        $this->assertTrue($response->hasErrors(), 'Invalid XML document should produce errors');
        $this->assertFalse($response->hasWarnings(), 'Invalid XML document should produce no warnings');

        $found = false;
        foreach ($response->getErrors() as $error) {
            if ('name expected' === $error->getText()) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Expected "name expected" error in XML document');
    }

    public function testDetectsErrorsOnInvalidHtml4(): void
    {
        $response = $this->getValidator(Validator::PARSER_HTML4)->validateDocument(
            $this->getFixture('document-invalid-html4.html'),
            Validator::CHARSET_UTF_8,
        );

        $this->assertTrue($response->hasErrors(), 'Invalid HTML4 document should produce errors');
        $this->assertFalse($response->hasWarnings(), 'Invalid HTML4 document should produce no warnings');

        $found = false;
        foreach ($response->getErrors() as $error) {
            if ('Stray end tag “span”.' === $error->getText()) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Expected stray end tag error for <span> in HTML4 document');
    }

    public function testValidateUrl(): void
    {
        $response = $this->getValidator()->validateUrl(
            $this->getFixtureUrl('document-invalid-utf8-html5.html'),
        );

        $this->assertTrue($response->hasErrors(), 'Invalid HTML5 should produce errors');

        $found = false;
        foreach ($response->getErrors() as $error) {
            if ('Stray end tag “span”.' === $error->getText()) {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, 'Expected stray end tag error for <span> in HTML5 document');
    }

    public function testValidateUrlWith404(): void
    {
        $response = $this->getValidator()->validateUrl(
            $this->getFixtureUrl('not-found'),
        );

        $this->assertTrue($response->hasErrors(), 'Invalid HTML5 should produce errors');
        $this->assertCount(1, $response->getErrors(), 'Expected exactly one error for 404 response');
        $this->assertFalse($response->hasWarnings(), 'Invalid HTML5 should produce no warnings');

        $error = $response->getErrors()[0];
        $this->assertSame('non-document-error', $error->getType(), 'Expected non-document-error type for 404 response');
        $this->assertTrue(str_contains($error->getText(), 'The HTTP status from the remote server was: 404'), 'Expected 404 error in response');
    }

    public function testValidateUrlWithAllowed404(): void
    {
        $validator = $this->getValidator();
        $response = $validator->validateUrl(
            $this->getFixtureUrl('not-found'),
            ['checkErrorPages' => true],
        );

        $this->assertFalse($response->hasErrors(), '404 errors should be allowed when checkErrorPages is true');
        $this->assertFalse($response->hasWarnings(), '404 errors should be allowed when checkErrorPages is true');
    }
}
