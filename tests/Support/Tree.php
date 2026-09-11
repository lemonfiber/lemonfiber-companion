<?php

declare(strict_types=1);

namespace Tests\Support;

use function dirname;

use FilesystemIterator;

use function is_dir;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function sprintf;
use function str_ends_with;

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
}
