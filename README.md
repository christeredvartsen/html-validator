# html-validator

PHP client for the [validator.nu](https://validator.nu/) API. Can be configured to use a self-hosted version of the API.

## Installing

Requires [PHP 8.3](https://php.net) or later and [Guzzle 8.1](https://docs.guzzlephp.org/) or later.

Install the package with [Composer](https://getcomposer.org):

```sh
composer require christeredvartsen/html-validator
```

## Usage

```php
<?php declare(strict_types=1);

$document = file_get_contents('my-page.html');

$validator = new HtmlValidator\Validator();
$result = $validator->validateDocument($document);

$result->hasErrors();   // bool
$result->hasWarnings(); // bool
$result->getErrors();   // list<HtmlValidator\Message>
echo $result;           // Prints all messages in human-readable format
echo $result->toHTML(); // Prints all messages HTML-formatted
```

## Example

Document to be validated (`validate-me.html`):

```html
<!doctype html>
<html lang="en">
<head>
    <title>Invalid HTML5!</title>
</head>
<body>
    <p>This document is not a proper, well-formed HTML5 document!</p>
    <p>It contains fatal flaws, like:</p>
    <ul>
        <li><div> tags which are not closed</li>
        <li>span-tags which are never opened are attempted closed </span></li>
    </ul>
</body>
</html>
```

Using the validator:

```php
<?php declare(strict_types=1);

$document = file_get_contents('validate-me.html');

$validator = new HtmlValidator\Validator();
echo $validator->validateDocument($document);
```

Output:

```
error: End tag “li” seen, but there were open elements.
From line 10, column 44; to line 10, column 48
not closed</li>


error: Unclosed element “div”.
From line 10, column 13; to line 10, column 17
      <li><div> tags

error: Stray end tag “span”.
From line 11, column 67; to line 11, column 73
ed closed </span></li>
```

## Validating a URL

```php
<?php declare(strict_types=1);

$validator = new HtmlValidator\Validator();
echo $validator->validateUrl($url);
```

Note that if you want to check pages that return status codes that are not in the 2xx-range (like a 404-page), you need to pass a `checkErrorPages` option:

```php
<?php declare(strict_types=1);

$validator = new HtmlValidator\Validator();
echo $validator->validateUrl($url, ['checkErrorPages' => true]);
```

## Using a self-hosted version of the API

Check out [validator.nu](http://about.validator.nu/#src) for instructions on setting up the service. Once set up, you can configure the validator to use a different host:

```php
<?php declare(strict_types=1);

$validator = new HtmlValidator\Validator('http://self-hosted-validator.domain.com');
```

## Configuration

Use the `HtmlValidator\Parser` and `HtmlValidator\Charset` enums to configure the validator:

```php
<?php declare(strict_types=1);

$validator = new HtmlValidator\Validator(
    parser: HtmlValidator\Parser::XML,
);

$validator->setCharset(HtmlValidator\Charset::ISO88591);
```

## Integration tests

Integration tests use the Validator.nu and fixture services defined in `docker-compose.yaml`. Start the services and wait for them to become healthy before running the integration test group:

```sh
docker compose up --detach --wait && vendor/bin/phpunit --group integration
```

Stop the services when finished:

```sh
docker compose down
```

## License

MIT licensed. See [LICENSE](./LICENSE) for full terms.
