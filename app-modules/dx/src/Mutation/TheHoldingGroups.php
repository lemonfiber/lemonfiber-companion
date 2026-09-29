<?php

declare(strict_types=1);

namespace Dx\Mutation;

use function in_array;
use function preg_match_all;
use function sprintf;
use function str_contains;

/**
 * Each path a group of the suite holds, by that path, read from the suite's own
 * listing of its groups.
 *
 * A test file that asserts on what some code does, rather than merely passing
 * through it, may declare that it holds that code, and the code is then mutated
 * against that group instead of against the suite. That is for code every test
 * passes through: each of its mutants would otherwise run the whole suite. How
 * a group says what it holds is the repository's to answer
 * ({@see TheLayout::pathHeldBy()}).
 *
 * Asked of the suite rather than read out of test sources, so the answer is the
 * groups Pest will actually select by. A group naming a path nothing measures,
 * or one that is not there, is refused rather than ignored: a misspelt path
 * would otherwise be mutated against the whole suite again, correct and slow
 * and with no sign the declaration was never read.
 */
final readonly class TheHoldingGroups
{
    /** @param array<string, string> $byPath the group that holds each held path */
    private function __construct(public array $byPath) {}

    /**
     * @param string       $listing  what `pest --list-groups` printed
     * @param list<string> $measured every tree the suite measures
     */
    public static function in(string $listing, array $measured, TheLayout $layout, TheRepository $repository): self
    {
        // Refused rather than read as *no groups*: a listing that failed and one
        // that found none would otherwise both mutate every tree against the
        // whole suite, and only one of them is true.
        if (! str_contains($listing, 'Available test group')) {
            throw TheGateCannotRun::because(sprintf("Pest could not list the suite's groups:\n%s\n", $listing));
        }

        preg_match_all('/^\s*-\s+(\S+)\s+\(/mu', $listing, $found);

        if (! in_array($layout->canary(), $found[1], strict: true)) {
            throw TheGateCannotRun::because(sprintf(<<<'SAID'
                No test is in the group %s.

                Every shard opens on that group rather than on the whole suite, because it
                takes its coverage from the run the tests job already made. A shard with no
                canary opens on nothing, and a shard whose application cannot boot would
                then pass. Put a test that boots the application back in the group.

                SAID, $layout->canary()));
        }

        $byPath = [];

        foreach ($found[1] as $group) {
            $path = $layout->pathHeldBy($group);

            if ($path !== '') {
                self::refuseUnmeasured($group, $path, $measured, $repository);
                $byPath[$path] = $group;
            }
        }

        return new self($byPath);
    }

    /** @param list<string> $measured */
    private static function refuseUnmeasured(string $group, string $path, array $measured, TheRepository $repository): void
    {
        if (in_array($path, $measured, strict: true) || (Paths::under($path, $measured) && $repository->exists($path))) {
            return;
        }

        throw TheGateCannotRun::because(sprintf(<<<'SAID'
            A test declares the group %s, and %s is not a tree phpunit.xml measures or a path inside one.

            The group names what its tests hold, so the name has to be a measured tree
            or a file or directory in one, spelt as the repository spells it —
            otherwise what it meant is mutated against the whole suite again, and
            nothing says why it is slow.

            SAID, $group, $path));
    }
}
