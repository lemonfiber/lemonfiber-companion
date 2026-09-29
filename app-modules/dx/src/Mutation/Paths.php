<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function array_any;
use function explode;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function preg_replace;
use function sprintf;
use function str_starts_with;

/** Paths as the repository writes them, relative to its root. */
final readonly class Paths
{
    /**
     * Whether a path is one of these, or inside one of them.
     *
     * @param list<string> $paths
     */
    public static function under(string $path, array $paths): bool
    {
        return array_any($paths, static fn(string $tree): bool => $path === $tree || str_starts_with($path, sprintf('%s/', $tree)));
    }

    /** A path under a root, relative to it; any other path as it is. */
    public static function relativeTo(string $root, string $path): string
    {
        return str_starts_with($path, sprintf('%s/', $root)) ? mb_substr($path, mb_strlen($root) + 1) : $path;
    }

    /** A test file's path or a test's class, as the letters and digits both are spelt with. */
    public static function lettersOf(string $name): string
    {
        return mb_strtolower(preg_replace('#[^A-Za-z0-9]#u', '', $name) ?? $name);
    }

    /** The class a test id names, as the letters and digits it is spelt with. */
    public static function classOfTest(string $id): string
    {
        return self::lettersOf(preg_replace('#^P\\\\#u', '', explode('::', $id, 2)[0]) ?? $id);
    }

    /** A test file's class, as the letters and digits Pest names it with. */
    public static function classOfTestFile(string $path): string
    {
        return self::lettersOf(mb_substr($path, 0, -4));
    }
}
