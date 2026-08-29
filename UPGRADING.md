# Upgrading

## Upgrading from v2.3.0 to v3.0

Version 3 is a major release with new runtime requirements and breaking API changes. This guide covers changes from v2.3.0.

### Package and runtime requirements

The Composer package has moved from `rexxars/html-validator` to `christeredvartsen/html-validator`. Update your dependency declaration:

```sh
composer remove rexxars/html-validator
composer require christeredvartsen/html-validator:^3.0
```

PHP 8.3 or newer is now required. v3 also requires Guzzle 8.1 or later. Update your application's PHP version and any dependency constraints that prevent Guzzle 8 from being installed.

### Parser and charset configuration

`Validator::PARSER_*` and `Validator::CHARSET_*` constants have been removed. Parser and charset values are now represented by the `Parser` and `Charset` backed enums.

Before:

```php
$validator = new HtmlValidator\Validator(
    'https://validator.nu',
    HtmlValidator\Validator::PARSER_XML,
);

$validator->setCharset(HtmlValidator\Validator::CHARSET_ISO_8859_1);
```

After:

```php
$validator = new HtmlValidator\Validator(
    'https://validator.nu',
    HtmlValidator\Parser::XML,
);

$validator->setCharset(HtmlValidator\Charset::ISO88591);
```

Use enum cases rather than their backing strings. For example, `Parser::HTML5->value` is `html5` and `Charset::UTF8->value` is `UTF-8`, but the validator API accepts the enum instances.

The following methods now use enums:

| Method                                          | v2.3.0                              | v3.0                    |
| ----------------------------------------------- | ----------------------------------- | ----------------------- |
| `Validator::__construct()` second argument      | parser string                       | `Parser`                |
| `Validator::getParser()`                        | parser string                       | `Parser`                |
| `Validator::setParser()`                        | parser string                       | `Parser`                |
| `Validator::getCharset()`                       | charset string                      | `Charset`               |
| `Validator::setCharset()`                       | charset string                      | `Charset`               |
| `Validator::validateDocument()` second argument | nullable charset string             | `?Charset`              |
| `Validator::validateNodes()` second argument    | nullable charset string             | `?Charset`              |
| `NodeWrapper::wrap()`                           | parser and nullable charset strings | `Parser` and `?Charset` |

Invalid parser values are no longer handled with `HtmlValidator\Exception\UnknownParserException`; use a `Parser` case. Passing a string to an enum-typed API raises PHP's `TypeError`.

### Removed `validate()` alias

`Validator::validate()` has been removed. Use `Validator::validateDocument()`
instead; it provides the same behavior.

### Exceptions

The former `HtmlValidator\Exception` base class has been removed. Catch the new marker interface when handling every package exception:

```php
try {
    $response = $validator->validateDocument($document);
} catch (HtmlValidator\Exception\HtmlValidatorException $e) {
    // Handle errors raised by this package.
}
```

`HtmlValidator\Exception\ServerException` now extends `RuntimeException`. Guzzle failures from both document and URL validation are normalized to `ServerException` and preserved as the previous exception:

```php
try {
    $validator->validateUrl($url);
} catch (HtmlValidator\Exception\ServerException $e) {
    $transportFailure = $e->getPrevious();
}
```

`HtmlValidator\Exception\InvalidArgumentException` is new and extends PHP's `InvalidArgumentException`. It is raised when directly constructing a `Message` with invalid validator message data.

### Typed public APIs

v3 adds parameter and return types throughout the public API. Code must pass the declared object types, such as `Parser`, `Charset`, and `GuzzleHttp\ClientInterface`. Scalar parameter coercion follows PHP's normal `strict_types` rules in the calling file.

In particular:

- `Validator::setHttpClient()` accepts `GuzzleHttp\ClientInterface`.
- `Validator::validateDocument()` and `validateNodes()` accept strings for
  their document or node input.
- `Validator::validateUrl()` accepts a string URL and an array of options.
- `Message::setHighlighter()` accepts a callable.
- `Message::setHighlightClassName()` accepts a string.

`Message` now validates message types, subtypes, and consumed field types when constructed directly. Invalid input raises `HtmlValidator\Exception\InvalidArgumentException` instead of being accepted unchanged. When parsing a validator response, malformed individual message entries are skipped to remain compatible with the validator.nu processing model.

### Extending `NodeWrapper`

The wrapper implementation methods `wrapInXmlDocument()`, `wrapInHtml5Document()`, and `wrapInHtml4Document()` are now private. They were previously protected, so subclasses that overrode them can no longer customize the generated wrapper documents. Use composition around `Validator` or maintain a project-specific wrapper before calling `validateDocument()` instead.

### Response validation and message formatting

v3 validates that a validator response has HTTP status 200, a JSON media type, valid JSON, and a top-level `messages` array. An invalid response raises `ServerException` instead of producing PHP warnings or partially parsed data.

The built-in HTML formatter now interprets validator.nu highlight offsets as UTF-16 code units. This preserves UTF-8 text containing multibyte or astral characters. Custom highlighters continue to receive the original UTF-16 offset and length values.
