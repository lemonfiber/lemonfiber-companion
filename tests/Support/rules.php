<?php

declare(strict_types=1);

namespace Tests\Support;

use function basename;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_dir;
use function is_string;
use function preg_match;

use RuntimeException;

use function sprintf;
use function trim;

/**
 * Every rule ARCHITECTURE.md documents, and the mechanism it claims.
 *
 * @return array<string, string> rule identifier => claimed enforcement
 */
function documentedRules(): array
{
    $path = Tree::at('ARCHITECTURE.md');
    $document = file_get_contents($path);

    if (! is_string($document)) {
        throw new RuntimeException(sprintf('%s could not be read', $path));
    }

    $rules = [];

    foreach (explode("\n", $document) as $line) {
        // | **A1** | the rule, in words | how it is enforced |
        // The identifier is emphasised in the table; the emphasis is stripped
        // so the join is on the identifier itself.
        if (preg_match('/^\|\s*\*{0,2}([A-Z]\d{1,2})\*{0,2}\s*\|\s*(.+?)\s*\|\s*(.+?)\s*\|\s*$/u', $line, $found) !== 1) {
            continue;
        }

        $rules[$found[1]] = trim($found[3]);
    }

    if ($rules === []) {
        throw new RuntimeException(
            'No rules were read from ARCHITECTURE.md. Either the tables moved or the '
            . 'format changed — and a checker that silently finds nothing to check is '
            . 'the failure it exists to prevent.',
        );
    }

    return $rules;
}

/**
 * Everything that could carry a rule identifier, concatenated.
 *
 * Read as text rather than parsed, because the identifier appears in several
 * shapes — an arch rule's description, a PHPStan `message:`, a comment above a
 * configuration key — and inventing a schema for each would be a second thing
 * to keep in step.
 */
function enforcementSources(): string
{
    return implode("\n", [...configSources(), ...treeSources()]);
}

/**
 * The configuration files that can carry a rule identifier in a comment or a
 * message.
 *
 * @return list<string>
 */
function configSources(): array
{
    $found = [];

    $configs = [
        'phpstan.neon',
        'phpunit.xml',
        'composer.json',
        'pint.json',
        'rector.php',
        'composer-dependency-analyser.php',
        '.github/workflows/ci.yml',
    ];

    foreach ($configs as $file) {
        $contents = file_get_contents(Tree::at($file));

        if (is_string($contents)) {
            $found[] = $contents;
        }
    }

    return $found;
}

/**
 * Every test and module source file, which is where an arch rule names its
 * identifier.
 *
 * @return list<string>
 */
function treeSources(): array
{
    $found = [];

    // `phpstan/` is here because our own PHPStan rules carry their identifier in
    // the message a developer reads when blocked, which is the same join as an
    // arch rule's description.
    foreach ([Tree::at('tests'), Tree::at('app-modules'), Tree::at('phpstan')] as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        foreach (phpFilesUnder($directory) as $contents) {
            $found[] = $contents;
        }
    }

    return $found;
}

/**
 * @return list<string>
 */
function phpFilesUnder(string $directory): array
{
    // Three files name identifiers without enforcing anything. This checker and
    // its reader report on every identifier there is, so including them would
    // make every rule look enforced by the act of checking; the fixture registry
    // holds a violation of each rule verbatim, so an identifier written inside
    // one of those snippets would read as an enforcement of it.
    $excluded = ['TheRulesAreRealTest.php', 'rules.php', 'Fixtures.php'];
    $found = [];

    foreach (Tree::filesUnder($directory, '.php') as $file) {
        if (in_array(basename($file), $excluded, strict: true)) {
            continue;
        }

        $contents = file_get_contents($file);

        if (is_string($contents)) {
            $found[] = $contents;
        }
    }

    return $found;
}
