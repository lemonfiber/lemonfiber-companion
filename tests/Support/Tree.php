<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_values;
use function dirname;
use function file_exists;
use function file_get_contents;

use FilesystemIterator;

use function implode;
use function in_array;
use function is_array;
use function is_dir;
use function is_string;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function sprintf;
use function str_ends_with;
use function token_get_all;

/**
 * Files on disk, found recursively.
 *
 * One recursive walk, in one place, because `glob()` has no globstar: a pattern
 * like `resources/views/**\/*.blade.php` matches exactly one level down and
 * silently reports nothing for everything deeper. A checker that quietly finds
 * nothing to check is the failure mode every rule in this suite exists to
 * prevent, and it is invisible — the suite stays green while the templates go
 * unread.
 */
final readonly class Tree
{
    /**
     * What tells this repository apart from a directory that merely resembles it.
     *
     * Three rather than one, because each alone is common: a parent directory
     * holding several checkouts has none of them, but a sibling worktree of this
     * repository has all three — and a sibling worktree is the wrong tree that
     * answers every question plausibly.
     */
    private const array LANDMARKS = ['composer.json', 'phpstan.neon', 'app-modules'];
    /**
     * Every file under a directory whose name ends with the given suffix.
     *
     * @return list<string>
     */
    public static function filesUnder(string $directory, string $suffix): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $found = [];

        $tree = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($tree as $file) {
            if (str_ends_with($file->getFilename(), $suffix)) {
                $found[] = $file->getPathname();
            }
        }

        return $found;
    }

    /**
     * The repository root.
     *
     * Checked rather than computed and trusted. `dirname(__DIR__, 2)` is correct
     * for exactly as long as this file stays two directories down, and wrong
     * silently the moment it does not: `filesUnder` answers `[]` for a directory
     * that is not there, so every rule built on this would read no files and
     * report that nothing violates anything.
     */
    public static function root(): string
    {
        $root = dirname(__DIR__, 2);
        $missing = self::landmarksMissingFrom($root);

        if ($missing !== []) {
            throw new RuntimeException(sprintf(
                '%s is not this repository: it has no %s. Every path in this harness '
                . 'is built from here, and a root pointing somewhere else produces '
                . 'empty file lists rather than an error — which reads as nothing '
                . 'violating anything.',
                $root,
                implode(', ', $missing),
            ));
        }

        return $root;
    }

    /**
     * Whether a directory is this repository rather than somewhere plausible.
     *
     * Separate from `root()` so it can be asked about a directory that is not
     * the answer. The failure this guards against has no file that could carry
     * it — the root is what every path is built from, so a fixture could not be
     * written to a tree the harness could no longer find — and the judgement is
     * the only part of it that can be handed a violation.
     */
    public static function isTheRepository(string $directory): bool
    {
        return self::landmarksMissingFrom($directory) === [];
    }

    /** A path below the repository root. */
    public static function at(string $relative): string
    {
        return sprintf('%s/%s', self::root(), $relative);
    }

    /**
     * Whether a file declares a class, an interface, an enum or a trait.
     *
     * Read over tokens rather than with a pattern, for the reason `Vocabulary`
     * is: every comment in this codebase quotes code, so a text search for
     * `class ` finds the paragraph explaining a rule before it finds a breach of
     * one.
     *
     * `bootstrap/` is the directory this exists for. It holds framework wiring
     * that is not classes at all — `app.php` and `providers.php` return values —
     * so a rule about what may live there has to be able to tell the two apart.
     */
    public static function declaresAClass(string $file): bool
    {
        $source = file_get_contents($file);

        if (! is_string($source)) {
            return false;
        }

        $tokens = token_get_all($source);

        foreach ($tokens as $at => $token) {
            if (! is_array($token)) {
                continue;
            }

            if (! in_array($token[0], [T_CLASS, T_INTERFACE, T_ENUM, T_TRAIT], strict: true)) {
                continue;
            }

            // `CompositionRoot::class` tokenises as `T_CLASS` too, and a file
            // that merely *names* a class is not a file that declares one.
            // `bootstrap/providers.php` is exactly that — it returns a list of
            // class names — and without this it reads as a stray declaration in
            // the one directory this rule is about.
            if (self::isAConstantFetch($tokens, $at)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Every test file in the repository, root suites and module suites alike.
     *
     * @return list<string>
     */
    public static function testFiles(): array
    {
        $found = self::filesUnder(self::at('tests'), 'Test.php');

        foreach (Module::all() as $module) {
            $found = [...$found, ...$module->testFiles()];
        }

        return $found;
    }

    /**
     * Which landmarks a directory has not got.
     *
     * The answer rather than a yes or no, so the refusal above can name what is
     * actually absent. A message listing all three when one is missing is a
     * message a reader stops reading, and the one-missing case is the likelier
     * one: a sibling worktree has every landmark, a half-built checkout has
     * some.
     *
     * @return list<string>
     */
    private static function landmarksMissingFrom(string $directory): array
    {
        return array_values(array_filter(
            self::LANDMARKS,
            static fn(string $landmark): bool => ! file_exists(
                sprintf('%s/%s', $directory, $landmark),
            ),
        ));
    }

    /**
     * Whether the `class` at `$at` is the `::class` of a constant fetch.
     *
     * Looks back past whitespace, because `Foo :: class` is legal and rare
     * enough that somebody will eventually write it.
     *
     * @param list<array{int, string, int}|string> $tokens
     */
    private static function isAConstantFetch(array $tokens, int $at): bool
    {
        for ($here = $at - 1; $here >= 0; $here--) {
            $before = $tokens[$here];

            if (is_array($before) && $before[0] === T_WHITESPACE) {
                continue;
            }

            return is_array($before) && $before[0] === T_DOUBLE_COLON;
        }

        return false;
    }
}
