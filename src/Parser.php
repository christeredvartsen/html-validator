<?php declare(strict_types=1);

namespace HtmlValidator;

enum Parser: string
{
    case XML = 'xml';
    case XMLDTD = 'xmldtd';
    case HTML = 'html';
    case HTML5 = 'html5';
    case HTML4 = 'html4';
    case HTML4TR = 'html4tr';

    public function getMimeType(): string
    {
        return match ($this) {
            self::XML => 'application/xml',
            self::XMLDTD => 'application/xml-dtd',
            self::HTML,
            self::HTML5,
            self::HTML4,
            self::HTML4TR => 'text/html',
        };
    }
}
