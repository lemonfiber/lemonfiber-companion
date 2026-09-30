<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\OneHome;

use function array_any;
use function str_contains;

/**
 * The files D8 and D9 read.
 *
 * What ships is a module's `src`, the plugin's `src` and the composition
 * root. D9 reads `tests/Support` beside it, because a fake and a helper that
 * spell a value the application already declares are a second home the suite
 * then agrees with. D8 does not: what the suites share reads PHP's tokens,
 * EDGE's node types and the repository's file names, and a set of those is
 * the outside world's rather than a vocabulary of ours.
 *
 * A module's own tests are read by neither, because a test's `7` is the test
 * saying seven.
 */
final readonly class Ours
{
    /** The trees whose files ship, as a path spells them. */
    private const array SHIPPED = ['/src/', '/bootstrap/Composition/'];

    /** What the suites share. */
    private const string SHARED = '/tests/Support/';

    /** Whether this file ships, or is shared by the suites. */
    public static function holds(string $file): bool
    {
        return self::ships($file) || str_contains($file, self::SHARED);
    }

    /** Whether this file ships. */
    public static function ships(string $file): bool
    {
        return ! str_contains($file, '/tests/')
            && array_any(self::SHIPPED, static fn(string $tree): bool => str_contains($file, $tree));
    }
}
