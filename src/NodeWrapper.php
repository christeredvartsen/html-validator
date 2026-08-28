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
 * Node wrapper - tries to wrap a set of XML/DOM-nodes in a surrounding
 * document based on the passed validator markup parser
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/html-validator
 */
class NodeWrapper {

    /**
     * Attempts to wrap a document in a surrounding document
     *
     * @param  string $parser  Parser name (HtmlValidator\Validator::PARSER_*)
     * @param  string $nodes   Nodes to wrap
     * @param  string $charset Charset to use
     * @throws Exception\UnknownParserException If the given parser is not known
     * @return string
     */
    public function wrap($parser, $nodes, $charset = null) {
        switch ($parser) {
            case Validator::PARSER_XML:
            case Validator::PARSER_XMLDTD:
                return $this->wrapInXmlDocument($nodes, $charset);
            case Validator::PARSER_HTML:
            case Validator::PARSER_HTML5:
                return $this->wrapInHtml5Document($nodes, $charset);
            case Validator::PARSER_HTML4:
            case Validator::PARSER_HTML4TR:
                return $this->wrapInHtml4Document($nodes, $charset, $parser);
            default:
                throw new Exception\UnknownParserException('Unknown parser "' . $parser . '"');
        }
    }

    /**
     * Wraps a set of XML nodes in an XML-document
     *
     * @param  string $nodes   One or more XML-nodes, as a string
     * @param  string $charset Charset to specify in XML-document
     * @return string
     */
    protected function wrapInXmlDocument($nodes, $charset = null) {
        $charset = strtoupper($charset ?: Validator::CHARSET_UTF_8);

        $document  = '<?xml version="1.0" encoding="' . $charset . '"?>' . PHP_EOL;
        $document .= '<root>' . $nodes . '</root>';

        return $document;
    }

    /**
     * Wraps a set of HTML nodes in an HTML5-document
     *
     * @param  string $nodes   One or more HTML-nodes, as a string
     * @param  string $charset Charset to specify in meta tag
     * @return string
     */
    protected function wrapInHtml5Document($nodes, $charset = null) {
        $charset = strtolower($charset ?: Validator::CHARSET_UTF_8);

        $document  = '<!DOCTYPE html>' . PHP_EOL;
        $document .= '<html><head>' . PHP_EOL;
        $document .= '<meta charset="' . $charset . '">' . PHP_EOL;
        $document .= '<title>Validation document</title>' . PHP_EOL;
        $document .= '</head><body>' . $nodes . '</body></html>';

        return $document;
    }

    /**
     * Wraps a set of HTML nodes in an HTML4-document
     *
     * @param  string $nodes   One or more HTML-nodes, as a string
     * @param  string $charset Charset to specify in meta tag
     * @param  string $parser  Validator parser used
     * @return string
     */
    protected function wrapInHtml4Document($nodes, $charset = null, $parser = null) {
        if ($parser === Validator::PARSER_HTML4TR) {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
        } else {
            $doctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
        }

        $charset = strtolower($charset ?: Validator::CHARSET_UTF_8);

        $document  = $doctype . PHP_EOL;
        $document .= '<html><head>' . PHP_EOL;
        $document .= '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '">' . PHP_EOL;
        $document .= '<title>Validation document</title>' . PHP_EOL;
        $document .= '</head><body>' . $nodes . '</body></html>';

        return $document;
    }

}