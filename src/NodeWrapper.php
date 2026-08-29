<?php declare(strict_types=1);

namespace HtmlValidator;

use HtmlValidator\Exception\InvalidArgumentException;

use function sprintf;

use const PHP_EOL;

class NodeWrapper
{
    /**
     * Wrap a document in a surrounding document.
     *
     * @param string  $parser  Parser name (HtmlValidator\Validator::PARSER_*)
     * @param string  $nodes   Nodes to wrap
     * @param ?string $charset Charset to use
     *
     * @throws InvalidArgumentException
     */
    public function wrap(string $parser, string $nodes, ?string $charset = null): string
    {
        return match ($parser) {
            Validator::PARSER_XML,
            Validator::PARSER_XMLDTD => $this->wrapInXmlDocument($nodes, $charset),
            Validator::PARSER_HTML,
            Validator::PARSER_HTML5 => $this->wrapInHtml5Document($nodes, $charset),
            Validator::PARSER_HTML4,
            Validator::PARSER_HTML4TR => $this->wrapInHtml4Document($nodes, $charset, $parser),
            default => throw new InvalidArgumentException(sprintf('Unknown parser: "%s"', $parser)),
        };
    }

    /**
     * Wraps a set of XML nodes in an XML-document.
     *
     * @param string  $nodes   One or more XML-nodes, as a string
     * @param ?string $charset Charset to specify in XML-document
     */
    private function wrapInXmlDocument(string $nodes, ?string $charset = null): string
    {
        $charset = strtoupper($charset ?: Validator::CHARSET_UTF_8);

        $document = '<?xml version="1.0" encoding="'.$charset.'"?>'.PHP_EOL;
        $document .= '<root>'.$nodes.'</root>';

        return $document;
    }

    /**
     * Wraps a set of HTML nodes in an HTML5-document.
     *
     * @param string  $nodes   One or more HTML-nodes, as a string
     * @param ?string $charset Charset to specify in meta tag
     */
    private function wrapInHtml5Document(string $nodes, ?string $charset = null): string
    {
        $charset = strtolower($charset ?: Validator::CHARSET_UTF_8);

        $document = '<!DOCTYPE html>'.PHP_EOL;
        $document .= '<html><head>'.PHP_EOL;
        $document .= '<meta charset="'.$charset.'">'.PHP_EOL;
        $document .= '<title>Validation document</title>'.PHP_EOL;
        $document .= '</head><body>'.$nodes.'</body></html>';

        return $document;
    }

    /**
     * Wraps a set of HTML nodes in an HTML4-document.
     *
     * @param string  $nodes   One or more HTML-nodes, as a string
     * @param ?string $charset Charset to specify in meta tag
     * @param ?string $parser  Validator parser used
     */
    private function wrapInHtml4Document(string $nodes, ?string $charset = null, ?string $parser = null): string
    {
        if (Validator::PARSER_HTML4TR === $parser) {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        } else {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        }

        $charset = strtolower($charset ?: Validator::CHARSET_UTF_8);

        $document = $doctype.PHP_EOL;
        $document .= '<html><head>'.PHP_EOL;
        $document .= '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">'.PHP_EOL;
        $document .= '<title>Validation document</title>'.PHP_EOL;
        $document .= '</head><body>'.$nodes.'</body></html>';

        return $document;
    }
}
