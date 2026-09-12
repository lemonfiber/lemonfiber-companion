<?php

declare(strict_types=1);

namespace Tests\Support;

use function dirname;
use function file_get_contents;

use FilesystemIterator;

use function in_array;
use function is_array;
use function is_dir;
use function is_string;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

    /** The repository root. */
    public static function root(): string
    {
        return dirname(__DIR__, 2);
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
