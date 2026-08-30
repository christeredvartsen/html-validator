# Contributing

Contributions are welcome. Please open an issue before starting substantial work so the proposed change can be discussed.

## Development setup

The project requires PHP 8.3 or later and Composer. Install dependencies from the repository root:

```sh
composer install
```

## Tests and quality checks

Run the relevant tests while developing. Before opening a pull request, run the full QA suite:

```sh
composer run ci
```

This checks coding style, runs PHPStan, and executes PHPUnit. Use the Composer scripts to run individual checks:

```sh
composer run cs       # check coding style
composer run cs:fix   # fix coding style
composer run sa       # run static analysis
composer run test     # run the test suite
```

The test suite includes integration tests. Start the Validator.nu and fixture services before running them:

```sh
docker compose up --detach --wait
composer run test
docker compose down
```

## Code and tests

- Follow the existing coding standard; use `composer run cs:fix` to format changes.
- Preserve the public API unless the change is explicitly intended to be breaking.
- Add focused tests for behavior changes. Unit tests should mock `GuzzleHttp\ClientInterface` rather than call external services.
- PHPUnit requires coverage metadata for source classes. Add or update `#[CoversClass(...)]` attributes when adding tests.
- Update `README.md` and `UPGRADING.md` when a change affects public usage or upgrade paths.

## Pull requests

Keep pull requests focused and include a clear description of the change and its testing. Pull-request titles must use the Conventional Commits format, for example:

```text
feat: add a validation option
fix: handle malformed responses
docs: clarify configuration
```

The pull-request workflow validates the title and runs the quality checks on supported PHP versions.
