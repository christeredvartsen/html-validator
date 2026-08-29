<?php declare(strict_types=1);

namespace HtmlValidator;

use const PHP_EOL;

class NodeWrapper
{
    /**
     * Wrap a document in a surrounding document.
     *
     * @param Parser   $parser  Parser to use
     * @param string   $nodes   Nodes to wrap
     * @param ?Charset $charset Charset to use
     */
    public function wrap(Parser $parser, string $nodes, ?Charset $charset = null): string
    {
        return match ($parser) {
            Parser::XML,
            Parser::XMLDTD => $this->wrapInXmlDocument($nodes, $charset),
            Parser::HTML,
            Parser::HTML5 => $this->wrapInHtml5Document($nodes, $charset),
            Parser::HTML4,
            Parser::HTML4TR => $this->wrapInHtml4Document($nodes, $charset, $parser),
        };
    }

    /**
     * Wraps a set of XML nodes in an XML-document.
     *
     * @param string   $nodes   One or more XML-nodes, as a string
     * @param ?Charset $charset Charset to specify in XML-document
     */
    private function wrapInXmlDocument(string $nodes, ?Charset $charset = null): string
    {
        $charset = strtoupper(($charset ?? Charset::UTF8)->value);

        $document = '<?xml version="1.0" encoding="'.$charset.'"?>'.PHP_EOL;
        $document .= '<root>'.$nodes.'</root>';

        return $document;
    }

    /**
     * Wraps a set of HTML nodes in an HTML5-document.
     *
     * @param string   $nodes   One or more HTML-nodes, as a string
     * @param ?Charset $charset Charset to specify in meta tag
     */
    private function wrapInHtml5Document(string $nodes, ?Charset $charset = null): string
    {
        $charset = strtolower(($charset ?? Charset::UTF8)->value);

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
     * @param string   $nodes   One or more HTML-nodes, as a string
     * @param ?Charset $charset Charset to specify in meta tag
     * @param Parser   $parser  Validator parser used
     */
    private function wrapInHtml4Document(string $nodes, ?Charset $charset, Parser $parser): string
    {
        if (Parser::HTML4TR === $parser) {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        } else {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        }

        $charset = strtolower(($charset ?? Charset::UTF8)->value);

        $document = $doctype.PHP_EOL;
        $document .= '<html><head>'.PHP_EOL;
        $document .= '<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">'.PHP_EOL;
        $document .= '<title>Validation document</title>'.PHP_EOL;
        $document .= '</head><body>'.$nodes.'</body></html>';

        return $document;
    }
}
