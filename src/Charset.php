<?php declare(strict_types=1);

namespace HtmlValidator;

enum Charset: string
{
    case UTF8 = 'UTF-8';
    case UTF16 = 'UTF-16';
    case WINDOWS1250 = 'Windows-1250';
    case WINDOWS1251 = 'Windows-1251';
    case WINDOWS1252 = 'Windows-1252';
    case WINDOWS1253 = 'Windows-1253';
    case WINDOWS1254 = 'Windows-1254';
    case WINDOWS1255 = 'Windows-1255';
    case WINDOWS1256 = 'Windows-1256';
    case WINDOWS1257 = 'Windows-1257';
    case WINDOWS1258 = 'Windows-1258';
    case ISO88591 = 'ISO-8859-1';
    case ISO88592 = 'ISO-8859-2';
    case ISO88593 = 'ISO-8859-3';
    case ISO88594 = 'ISO-8859-4';
    case ISO88595 = 'ISO-8859-5';
    case ISO88596 = 'ISO-8859-6';
    case ISO88597 = 'ISO-8859-7';
    case ISO88598 = 'ISO-8859-8';
    case ISO88599 = 'ISO-8859-9';
    case ISO885913 = 'ISO-8859-13';
    case ISO885915 = 'ISO-8859-15';
    case KOI8R = 'KOI8-R';
    case TIS620 = 'TIS-620';
    case GBK = 'GBK';
    case GB18030 = 'GB18030';
    case BIG5 = 'Big5';
    case BIG5HKSCS = 'Big5-HKSCS';
    case SHIFTJIS = 'Shift_JIS';
    case ISO2022JP = 'ISO-2022-JP';
    case EUCJP = 'EUC-JP';
    case ISO2022KR = 'ISO-2022-KR';
    case EUCKR = 'EUC-KR';
}
