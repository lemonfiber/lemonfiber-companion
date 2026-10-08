<?php

declare(strict_types=1);

/*
 * The tests that hold a tree or a path, and whether they cover all of what they hold.
 *
 * Part of `scripts/mutation.php`, which requires it.
 */

use Tests\Support\MeasuredTree;

/**
 * Each path a group declares it holds — a measured tree, or a file or directory
 * inside one — by that path.
 *
 * Asked of the suite rather than read out of test sources, so the answer is
 * the groups Pest will actually select by. A group naming a path nothing
 * measures, or one that is not there, is refused rather than ignored: a
 * misspelt path would otherwise be mutated against the whole suite again,
 * correct and slow and with no sign the declaration was never read.
 *
 * @param  list<MeasuredTree>    $trees
 * @return array<string, string>
 */
function whatHoldsEachTree(string $root, array $trees): array
{
    $said = shell_exec(sprintf('%s/vendor/bin/pest --list-groups --colors=never 2>&1', escapeshellarg($root)));
    $said = is_string($said) ? $said : '';

    // Refused rather than read as *no groups*: a listing that failed and one
    // that found none would otherwise both mutate every tree against the whole
    // suite, and only one of them is true.
    if (! str_contains($said, 'Available test group')) {
        fwrite(STDERR, sprintf("Pest could not list the suite's groups:\n%s\n", $said));

        exit(1);
    }

    $listed = explode("\n", $said);

    $measured = array_map(static fn(MeasuredTree $tree): string => $tree->path, $trees);
    $holders = [];

    foreach ($listed as $line) {
        if (preg_match('/^\s*-\s+(holds:(\S+))\s+\(/u', $line, $found) !== 1) {
            continue;
        }

        if (! isMeasured($root, $found[2], $measured)) {
            fwrite(STDERR, sprintf(<<<'SAID'
                A test declares the group %s, and %s is not a tree phpunit.xml measures or a path inside one.

                The group names what its tests hold, so the name has to be a measured tree
                or a file or directory in one, spelt as the repository spells it —
                otherwise what it meant is mutated against the whole suite again, and
                nothing says why it is slow.

                SAID, $found[1], $found[2]));

            exit(1);
        }

        $holders[$found[2]] = $found[1];
    }

    return $holders;
}

/**
 * Whether a group reaches every line of what it holds that anything could reach.
 *
 * Measured the way `test:report` measures, over the group alone, and held to
 * every statement in the tree: the coverage floor already holds the suite to
 * all of them, so a statement the group misses is one the suite reaches and
 * the group does not — and `--covered-only` would drop its mutants without a
 * word. Files `phpunit.xml` leaves out of `<source>` are not in the report,
 * which is the same exemption the device-only runloop has everywhere else.
 */
function theGroupCoversWhatItHolds(string $root, string $group, string $tree): bool
{
    $clover = sprintf('%s/lemonfiber-held-%s.xml', sys_get_temp_dir(), hash('sha256', $group));

    passthru(sprintf(
        "php -d pcov.directory=%s -d pcov.exclude='~/(vendor|bootstrap/cache)/~' %s/vendor/bin/pest --group=%s --coverage-clover=%s",
        escapeshellarg($root),
        escapeshellarg($root),
        escapeshellarg($group),
        escapeshellarg($clover),
    ), $status);

    if ($status !== 0 || ! is_file($clover)) {
        fwrite(STDERR, sprintf("The group %s did not pass on its own, so it cannot judge %s.\n", $group, $tree));

        return false;
    }

    $missed = whatTheReportLeavesUnreached($clover, $root, $tree);
    unlink($clover);

    if ($missed === []) {
        return true;
    }

    fwrite(STDERR, sprintf(<<<'SAID'
        %s does not cover %s, so its mutants cannot be judged by it.

        Not reached: %s

        Every line the suite reaches has to be reached by the tests that hold the
        tree, or `--covered-only` stops mutating it and nothing says so. Add the
        test that runs it to the group.

        SAID, $group, $tree, implode(', ', $missed)));

    return false;
}

/**
 * Every statement under a path a clover report says nothing reached.
 *
 * A path with no file in the report at all is answered as unreached as a
 * whole, rather than as nothing missed: a group that runs none of it covers
 * none of it, and an empty list would read as the opposite.
 *
 * @return list<string>
 */
function whatTheReportLeavesUnreached(string $clover, string $root, string $tree): array
{
    $report = simplexml_load_file($clover);
    $exactly = sprintf('%s/%s', $root, $tree);
    $under = sprintf('%s/', $exactly);
    $missed = [];
    $reached = false;

    foreach ($report === false ? [] : $report->xpath('//file') ?? [] as $file) {
        $name = (string) $file['name'];

        if ($name !== $exactly && ! str_starts_with($name, $under)) {
            continue;
        }

        $reached = true;

        foreach ($file->xpath('line[@type="stmt"][@count="0"]') ?? [] as $line) {
            $missed[] = sprintf('%s:%s', mb_substr($name, mb_strlen($root) + 1), (string) $line['num']);
        }
    }

    return $reached ? $missed : [sprintf('%s, all of it', $tree)];
}

/**
 * Whether a path is a measured tree, or a file or directory that exists in one.
 *
 * @param list<string> $measured
 */
function isMeasured(string $root, string $path, array $measured): bool
{
    foreach ($measured as $tree) {
        if ($path === $tree) {
            return true;
        }

        if (str_starts_with($path, sprintf('%s/', $tree)) && file_exists(sprintf('%s/%s', $root, $path))) {
            return true;
        }
    }

    return false;
}
