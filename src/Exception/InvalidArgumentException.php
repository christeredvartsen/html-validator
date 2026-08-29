<?php declare(strict_types=1);

namespace HtmlValidator\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements HtmlValidatorException
{
}
