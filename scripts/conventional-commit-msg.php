#!/usr/bin/env php
<?php

// Add to git hooks as a commit-msg hook to enforce conventional commit messages locally.
//
// From the repository root, run:
// ln -s ../../scripts/conventional-commit-msg.php .git/hooks/commit-msg

declare(strict_types=1);

namespace HtmlValidator;

use Ramsey\ConventionalCommits\Exception\InvalidCommitMessage;
use Ramsey\ConventionalCommits\Parser;

use const PHP_EOL;
use const STDERR;

require __DIR__.'/../vendor/autoload.php';

if (!isset($argc, $argv)) {
    fwrite(STDERR, 'Missing $argc and/or $argv'.PHP_EOL);
    exit(1);
}

if (2 !== $argc) {
    fwrite(STDERR, 'Missing required argument: <path-to-commit-message | commit-message>'.PHP_EOL);
    exit(1);
}

$input = $argv[1];
if (is_file($input)) {
    $handle = fopen($input, 'r');
    if (false === $handle) {
        fwrite(STDERR, 'Failed to open commit message file'.PHP_EOL);
        exit(1);
    }

    $line = fgets($handle);
    if (false === $line) {
        fwrite(STDERR, 'Failed to read commit message file'.PHP_EOL);
        exit(1);
    }

    $input = trim($line);
    fclose($handle);
} else {
    $input = trim(explode(PHP_EOL, $input)[0]);
}

try {
    (new Parser())->parse($input);
} catch (InvalidCommitMessage $e) {
    fwrite(STDERR, <<<EOF
    Invalid Conventional Commit message:

    > $input

    Examples:
    - feat(matcher): add support for new fancy matcher
    - build(deps): bump dependencies
    - docs: fix typo in README

    Please see
    - https://www.conventionalcommits.org/en/v1.0.0/#summary

    EOF);
    exit(1);
}
