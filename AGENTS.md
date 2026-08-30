# AGENTS.md

## Project Overview

`christeredvartsen/html-validator` is a PHP 8.3+ library that validates HTML through the Validator.nu API. Production code is in `src/` under the `HtmlValidator\` namespace; tests are in `tests/` and use the same namespace for test fixtures.

## Development

Install dependencies with:

```sh
composer install
```

Use Composer scripts for all quality checks:

```sh
composer run ci       # coding style, static analysis, and tests
composer run cs       # check coding style only
composer run cs:fix   # fix coding style issues
composer run sa       # run static analysis using PHPStan
composer run test     # run the PHPUnit suite
```

Run the relevant test class while developing, then run `composer run ci` before submitting a change. PHPUnit requires coverage metadata, so add or update `#[CoversClass(...)]` attributes when introducing tests for source classes.

## Integration Tests

Integration tests are tagged with the `integration` group and require the Validator.nu and fixture services defined in `docker-compose.yaml`:

```sh
docker compose up --detach --wait
composer run test # run unit and integration tests
composer run test -- --group integration # only run integration tests
docker compose down
```

Do not require these services for unit tests. Keep integration fixtures in `tests/fixtures/`. The regular test suite will automatically skip integration tests if the necessary services are not available.

## Code Conventions

- Follow the existing Imbo coding standard; use `composer run cs:fix` rather than hand-formatting uncertain code.
- Maintain PHPStan level according to the phpstan.dist.neon configuration file. Prefer precise native types and PHPDoc types where static analysis needs additional detail.
- Preserve the public API unless the change explicitly calls for a breaking change.
- Add focused PHPUnit coverage for behavior changes. Unit tests should mock `GuzzleHttp\ClientInterface` rather than call external services.
- Do not edit `vendor/`, `.php-cs-fixer.cache`, or `.phpunit.result.cache` or other files that is ignored according to `.gitignore`.

## Commit Messages

Use Conventional Commits, for example `feat: add a validation option`, `fix: handle malformed responses`, or `docs: clarify configuration`. The optional local commit-msg hook is documented in `scripts/conventional-commit-msg.php`.
