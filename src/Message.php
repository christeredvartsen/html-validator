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
 * HTML Validator message
 *
 * @author Espen Hovlandsdal <espen@hovlandsdal.com>
 * @copyright Copyright (c) Espen Hovlandsdal
 * @license http://www.opensource.org/licenses/mit-license MIT License
 * @link https://github.com/rexxars/html-validator
 */
class Message {

    /**
     * Type of error
     *
     * @var string
     */
    private $type;

    /**
     * Line number of where the error first occured
     *
     * @var int
     */
    private $firstLine;

    /**
     * Line number of where the error last occured
     *
     * @var int
     */
    private $lastLine;

    /**
     * First column index of where the error occured
     *
     * @var int
     */
    private $firstColumn;

    /**
     * Last column index of where the error occured
     *
     * @var int
     */
    private $lastColumn;

    /**
     * String offset within extract where the highlight should be started
     *
     * @var int
     */
    private $hiliteStart;

    /**
     * Length of highlighted string, within extract
     *
     * @var int
     */
    private $hiliteLength;

    /**
     * Text describing the error
     *
     * @var string
     */
    private $text;

    /**
     * An extract of an area where the error occured
     *
     * @var string
     */
    private $extract;

    /**
     * Callable highlighter function, overridable by user
     *
     * @var callable
     */
    private $highlighter;

    /**
     * CSS class name to use for the highlighted substring
     * (Only used if no custom highlighter is set)
     *
     * @var string
     */
    private $highlightClassName = 'highlight';

    /**
     * Default message values
     *
     * @var array
     */
    private $defaults = array(
        'lastLine'     => 0,
        'firstColumn'  => 0,
        'lastColumn'   => 0,
        'hiliteStart'  => 0,
        'hiliteLength' => 0,
        'message'      => '',
        'extract'      => '',
    );

    /**
     * Constructs a new message object
     *
     * @param array $info
     */
    public function __construct(array $info) {
        $info = array_merge($this->defaults, $info);

        $this->type = $info['type'];
        $this->firstLine = isset($info['firstLine']) ? $info['firstLine'] : $info['lastLine'];
        $this->lastLine = $info['lastLine'];
        $this->firstColumn = $info['firstColumn'];
        $this->lastColumn = $info['lastColumn'];
        $this->hiliteStart = $info['hiliteStart'];
        $this->hiliteLength = $info['hiliteLength'];
        $this->text = $info['message'];
        $this->extract = $info['extract'];
    }

    /**
     * Get the message type for this message
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get line number of first line where the error occured
     *
     * @return int
     */
    public function getFirstLine() {
        return $this->firstLine;
    }

    /**
     * Get line number of last line where the error occured
     *
     * @return int
     */
    public function getLastLine() {
        return $this->lastLine;
    }

    /**
     * Get start column where the error occured
     *
     * @return int
     */
    public function getFirstColumn() {
        return $this->firstColumn;
    }

    /**
     * Get last column where the error occured
     *
     * @return int
     */
    public function getLastColumn() {
        return $this->lastColumn;
    }

    /**
     * Get a text description of the message
     *
     * @return string
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get an extract of the problematic area
     *
     * @return string
     */
    public function getExtract() {
        return $this->extract;
    }

    /**
     * Get index offset of substring to highlight (within extract)
     *
     * @return int
     */
    public function getHighlightStart() {
        return $this->hiliteStart;
    }

    /**
     * Get length of the substring to highlight
     *
     * @return int
     */
    public function getHighlightLength() {
        return $this->hiliteLength;
    }

    /**
     * Set function to use for highlighting a substring within a string
     * Callable arguments:
     *  (string) $string - The full string in which to find the substring
     *  (int)    $start  - Start index of the substring to highlight
     *  (int)    $length - Length of substring to highlight
     *
     * @param callable $highlighter
     * @throws Exception
     * @return Message
     */
    public function setHighlighter($highlighter) {
        if (!is_callable($highlighter)) {
            throw new Exception('Highlighter passed to setHighlighter() must be callable');
        }

        $this->highlighter = $highlighter;
        return $this;
    }

    /**
     * Set the class name to use for the highlighted span.
     * Default: "highlight"
     *
     * @param  string $className Valid CSS class name
     * @return Message
     */
    public function setHighlightClassName($className) {
        $this->highlightClassName = (string) $className;

        return $this;
    }

    /**
     * Highlight the given string, enclosing it in a span
     *
     * @param  string $str    String to highlight
     * @param  int    $start  Start index of substring to highlight
     * @param  int    $length Length of substring to highlight
     * @return string
     */
    private function highlight($str, $start, $length) {
        $parts = array(
            substr($str, 0, $start),
            substr($str, $start, $length),
            substr($str, $start + $length)
        );

        $parts = array_map('htmlentities', $parts);

        $highlighted  = $parts[0] . '<span class="' . $this->highlightClassName . '">';
        $highlighted .= $parts[1] . '</span>' . $parts[2];

        return $highlighted;
    }

    /**
     * Format the message in readable format
     *
     * @param  boolean $html Whether to return an HTML-representation or not
     * @return string
     */
    public function format($html = false) {
        $format  = '%s: %s';

        if ($this->lastLine > 0) {
            $format .= PHP_EOL;
            $format .= 'From line %d, column %d; ' ;
            $format .= 'to line %d, column %d';
        }

        $message = sprintf(
            $format,
            $html ? '<strong>' . $this->type . '</strong>' : $this->type,
            $html ? htmlentities($this->text, ENT_COMPAT, 'UTF-8') : $this->text,
            $this->firstLine,
            $this->firstColumn,
            $this->lastLine,
            $this->lastColumn
        );

        if (!$html) {
            return $message . PHP_EOL . $this->extract;
        }

        // Check if the user has specified a custom highlighter
        if ($this->highlighter) {
            $highlighter = $this->highlighter;
            $extract = $highlighter($this->extract, $this->hiliteStart, $this->hiliteLength);
        } else {
            $extract = $this->highlight($this->extract, $this->hiliteStart, $this->hiliteLength);
        }

        $message .= PHP_EOL . $extract;
        return nl2br($message, false);
    }

    /**
     * Transforms message to a human-readable HTML string
     *
     * @return string
     */
    public function toHTML() {
        return $this->format(true);
    }

    /**
     * Transforms message to a human-readable string
     *
     * @return string
     */
    public function __toString() {
        return $this->format();
    }
}