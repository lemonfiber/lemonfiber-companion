<?php

declare(strict_types=1);

use Tests\Support\Tree;

// How a test is written, checked over the text of the test files themselves.
//
// Reading the source rather than reflecting over loaded tests is deliberate:
// several of these are about things that are true before a test runs, and one
// of them — a committed `->only()` — stops the rest of the suite from running
// at all, so a check that depended on the suite running would be the first
// casualty of the thing it is looking for.

/**
 * Every test file, paired with its contents.
 *
 * @return array<string, string>
 */
function testSources(): array
{
    $found = [];

    foreach (Tree::testFiles() as $path) {
        $contents = file_get_contents($path);

        if (is_string($contents)) {
            $found[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
        }
    }

    return $found;
}

it('G5 — a test asserts one way', function (): void {
    $offenders = [];

    foreach (testSources() as $path => $contents) {
        if ($path === 'tests/Arch/TestConventionsTest.php') {
            continue;  // this file names the idiom it refuses, in order to refuse it
        }

        if (preg_match('/\bassert[A-Z]\w*\s*\(/', $contents) === 1) {
            $offenders[] = $path;
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These mix PHPUnit's assertions into a Pest suite:\n  %s\n\n"
        . 'Two assertion idioms in one suite means two failure-message formats, two sets '
        . 'of negation rules and two things to learn before reading a test. Pest\'s '
        . 'expect() is the one this suite uses: expect($x)->toBe($y), not '
        . 'assertSame($y, $x) — which also puts the arguments in the other order, so '
        . 'mixing them is how a reversed expectation gets read as correct (G5).',
        implode("\n  ", $offenders),
    ));
});
