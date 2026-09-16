<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_any;
use function basename;
use function explode;
use function file_get_contents;
use function implode;
use function in_array;
use function is_dir;
use function is_string;
use function preg_match;
use function preg_quote;

use RuntimeException;

use function sprintf;
use function trim;

/**
 * What ARCHITECTURE.md claims is enforced, and everything that could enforce it.
 *
 * **A class rather than a file of functions, and the reason is the device.** A
 * namespaced function cannot be reached by PSR-4, so a file holding one is
 * loaded through composer's `autoload-dev.files` — the one autoload entry that
 * is `require`d unconditionally at boot rather than when something asks for it.
 * NativePHP's bundler excludes `tests` at any depth, so a build carrying that
 * entry boots into `require(.../tests/Support/rules.php): Failed to open
 * stream` and the first frame never renders.
 *
 * Static methods are in the classmap instead, and a classmap entry naming a
 * file the bundle dropped is inert until something autoloads it, which on a
 * device nothing does. That is what lets a debug build carry development
 * dependencies — which is what a stand-in for a stack needs to reach a handset
 * at all (`N1-R57`).
 */
final readonly class Rules
{
    /**
     * Files that name identifiers without enforcing anything.
     *
     * This checker and its reader report on every identifier there is, so
     * including them would make every rule look enforced by the act of
     * checking; the fixture registry holds a violation of each rule verbatim,
     * so an identifier written inside one of those snippets would read as an
     * enforcement of it.
     */
    private const array THAT_ONLY_NAME_THEM = ['TheRulesAreRealTest.php', 'Rules.php', 'Fixtures.php'];

    /**
     * Every rule ARCHITECTURE.md documents, and the mechanism it claims.
     *
     * @return array<string, string> rule identifier => claimed enforcement
     */
    public static function documented(): array
    {
        $path = Tree::at('ARCHITECTURE.md');
        $document = file_get_contents($path);

        if (! is_string($document)) {
            throw new RuntimeException(sprintf('%s could not be read', $path));
        }

        $rules = [];

        foreach (explode("\n", $document) as $line) {
            // | **A1** | the rule, in words | how it is enforced |
            // The identifier is emphasised in the table; the emphasis is
            // stripped so the join is on the identifier itself.
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
     * Read as text rather than parsed, because the identifier appears in
     * several shapes — an arch rule's description, a PHPStan `message:`, a
     * comment above a configuration key — and inventing a schema for each would
     * be a second thing to keep in step.
     */
    public static function enforcementSources(): string
    {
        return implode("\n", [...self::configSources(), ...self::treeSources()]);
    }

    /**
     * Where each kind of mechanism a rule can claim actually lives.
     *
     * The join in {@see enforcementSources()} is the identifier anywhere at
     * all, which is right for *is this enforced* and too loose for *is it
     * enforced the way the row says*. A row naming three mechanisms needs one
     * of them to mention it, so a clause with nothing behind it passes on the
     * strength of a comment somewhere else.
     *
     * Two kinds, because two are unambiguous: a word in the claim that names a
     * directory this repository has. `test` is deliberately not one of them —
     * it appears in *every* arch claim by way of the file the rule lives in, so
     * it would say nothing.
     *
     * @return array<string, list<string>>
     */
    public static function whereEachKindLives(): array
    {
        return [
            'arch' => self::phpFilesUnder(Tree::at('tests/Arch')),
            'phpstan' => [...self::phpFilesUnder(Tree::at('phpstan')), self::theConfiguration('phpstan.neon')],
        ];
    }

    /**
     * Whether one claim names a kind of mechanism.
     *
     * Read as a word rather than as text found anywhere in the claim, which is
     * the same reading {@see carriesTheRule()} needed on the other side of this
     * join. A substring match cannot tell a claim from a mention: a row whose
     * prose says *architecture* would claim an arch mechanism by spelling, and
     * one naming the generated `@phpstan-type` line as what an arch rule reads
     * would claim a PHPStan rule it never had. Both borrow a mechanism, and a
     * borrowed mechanism is exactly the row this checker exists to refuse.
     *
     * Hyphens are excluded on both sides rather than only before, which plain
     * word boundaries would not do: `@phpstan-type` has a boundary after
     * `phpstan`, so `\b` alone reads the tag as the tool.
     *
     * Every clause is searched rather than the first, because a row may name
     * two mechanisms and put the second one last — `composer + arch` and
     * `review, plus an arch check` are both claims to an arch rule, and reading
     * only the head would quietly stop asking them for one.
     */
    public static function claimsTheKind(string $claim, string $kind): bool
    {
        return preg_match(sprintf('/(?<![-\w])%s(?![-\w])/i', preg_quote($kind, '/')), $claim) === 1;
    }

    /**
     * Whether any of those sources names that rule.
     *
     * Bounded on both sides, so `H1` is not found inside `H10` and a hyphenated
     * spec identifier is not read as a repository one — tokens rather than
     * text, which is the same reading `K1` had to be taught.
     *
     * @param list<string> $sources
     */
    public static function carriesTheRule(string $id, array $sources): bool
    {
        $token = sprintf('/(?<![-A-Za-z0-9])%s(?![0-9A-Za-z])/', preg_quote($id, '/'));

        return array_any($sources, static fn(string $source): bool => preg_match($token, $source) === 1);
    }

    /**
     * The configuration files that can carry a rule identifier in a comment or
     * a message.
     *
     * @return list<string>
     */
    private static function configSources(): array
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
    private static function treeSources(): array
    {
        $found = [];

        // `phpstan/` is here because our own PHPStan rules carry their
        // identifier in the message a developer reads when blocked, which is
        // the same join as an arch rule's description.
        foreach ([Tree::at('tests'), Tree::at('app-modules'), Tree::at('phpstan')] as $directory) {
            if (! is_dir($directory)) {
                continue;
            }

            foreach (self::phpFilesUnder($directory) as $contents) {
                $found[] = $contents;
            }
        }

        return $found;
    }

    /** One configuration file, as text, or nothing where it cannot be read. */
    private static function theConfiguration(string $file): string
    {
        $contents = file_get_contents(Tree::at($file));

        return is_string($contents) ? $contents : '';
    }

    /**
     * @return list<string>
     */
    private static function phpFilesUnder(string $directory): array
    {
        $found = [];

        foreach (Tree::filesUnder($directory, '.php') as $file) {
            if (in_array(basename($file), self::THAT_ONLY_NAME_THEM, strict: true)) {
                continue;
            }

            $contents = file_get_contents($file);

            if (is_string($contents)) {
                $found[] = $contents;
            }
        }

        return $found;
    }
}
