<?php

declare(strict_types=1);

use Tests\Support\Tree;

// G12 — a suite that stands a payload in for a stack reads it against the
// contract.
//
// `G2` runs both implementations against the same assertions, which proves they
// agree with each other and not that either agrees with the machine. The
// payload they are both run against is hand-written, by whoever wrote the
// reader — so when the reader looks for a field at a path the contract has not
// got and the payload obliges, both halves pass and the application is broken
// against every real stack.
//
// `WhatTheContractAccepts` closes that, and this is what keeps it closed
// everywhere rather than in whichever suite last remembered. The row in the
// document says *every* suite, so one suite outside the check would have that
// row claim a guarantee a single file's assertion was carrying.
//
// Read as text rather than by running anything, for `G10`'s reason: what is
// being asked is whether the file makes the check at all, and a suite that had
// to be loaded to answer that is a suite the Guards harness could not plant a
// violation of — a real violation of this rule is an ordinary green suite, and
// the fixture for it has to sit where no testsuite collects it.

/**
 * Every test file, by the path it is at, with what it says.
 *
 * Its own reading rather than `TestConventionsTest`'s `testSources()`, which is
 * the same shape one directory along. Calling that one would tie this file to
 * whether the other has been loaded yet, and `G10` is what stops the obvious
 * alternative of declaring a second function by that name.
 *
 * @return array<string, string>
 */
function whatEveryTestFileSays(): array
{
    $said = [];

    foreach (Tree::testFiles() as $path) {
        $contents = file_get_contents($path);

        if (is_string($contents)) {
            $said[str_replace(sprintf('%s/', Tree::root()), '', $path)] = $contents;
        }
    }

    return $said;
}

/**
 * Every test that stands a payload in for a stack, by the path it is at.
 *
 * Found by `api_version`, which is written by nothing but an envelope: a body
 * carrying it is a body claiming to have come off a wire. `kind` alone would
 * have been the looser half of the same question — it appears wherever anything
 * is sorted into kinds, and this repository has a `Kind` of its own.
 *
 * @return list<string>
 */
function everySuiteStandingInForAStack(): array
{
    $standing = [];

    foreach (whatEveryTestFileSays() as $path => $contents) {
        if (preg_match('/[\'"]api_version[\'"]\s*=>/', $contents) === 1) {
            $standing[] = $path;
        }
    }

    sort($standing);

    return $standing;
}

it('G12 — every payload stood in for a stack is read against the contract', function (): void {
    $said = whatEveryTestFileSays();
    $unchecked = [];

    foreach (everySuiteStandingInForAStack() as $path) {
        if (! str_contains($said[$path] ?? '', 'WhatTheContractAccepts')) {
            $unchecked[] = $path;
        }
    }

    expect($unchecked)->toBe([], sprintf(
        "These write a payload a stack is supposed to have sent, and never ask whether a stack could send it:\n  %s\n\n"
        . 'A hand-written payload is written by whoever wrote the reader, so the two agree about a field that is '
        . 'not there and the suite stays green against a machine nobody has run it against. Put the payload in a '
        . "function and read it with WhatTheContractAccepts::complaintsAbout().\n",
        implode("\n  ", $unchecked),
    ));
});

it('G12 — the rule has something to read, so a silent pass is not one', function (): void {
    // What makes the rule above mean anything. A mark that matched no file
    // would report no violations, which reads exactly like compliance — and
    // this is the failure mode the rule exists to catch, arriving through the
    // rule itself.
    expect(everySuiteStandingInForAStack())->not->toBe([]);
});
