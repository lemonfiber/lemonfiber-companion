<?php

declare(strict_types=1);

use Tests\Support\Coverage;
use Tests\Support\MeasuredTree;

// G9 — no measured tree is below the floor its nearest manifest declared.
//
// One percentage for fifteen trees is an average, and an average is true about
// what it covered and silent about what it covered over: a capability at 100%
// carries an adapter at 40% and the gate reports a pass. The same clover report
// already holds the per-file numbers, so splitting it by directory costs
// nothing at the point of measurement and turns one number into fifteen.
//
// The trees are the ones `phpunit.xml` measures rather than the modules, which
// is the same list `G7` holds to a bar and `scripts/mutation.php` mutates. Read
// from the modules instead, this was silent about `bridge/src` and
// `bootstrap/Composition` — 2,900 lines inside the global floor and outside
// every per-directory one.
//
// This suite is not part of `composer test`. It reads a report rather than
// producing one, so it runs after `composer test:report` — which is also why it
// refuses to run without one rather than skipping: a floors gate that passes
// when the report is missing is a floors gate that passes in exactly the case
// nobody measured anything.

it('G9 — the coverage report is there to be read', function (): void {
    expect(Coverage::reportExists())->toBeTrue(sprintf(
        "No coverage report at %s.\n\n"
        . 'Run `composer test:report`, which writes the clover and JUnit reports. This '
        . 'suite reads the report rather than producing it, so the numbers it checks are '
        . "the ones the run printed.\nIt fails rather than skipping on purpose: a gate "
        . 'that passes when its input is missing passes in exactly the case where nobody '
        . 'measured anything (G9).',
        Coverage::reportPath(),
    ));
});

it('G9 — every measured tree meets the coverage floor its manifest declared', function (): void {
    $coverage = Coverage::fromReport();
    $below = [];
    $empty = [];

    foreach (MeasuredTree::all() as $tree) {
        $floor = $tree->coverageFloor;

        // An undeclared floor is G7's finding, reported there by name. Skipping
        // it here would be the silent exemption both rules exist to remove, so
        // G7 runs in the Arch suite where it cannot be missed.
        if ($floor === null) {
            continue;
        }

        $actual = $coverage->percentageUnder($tree->path);

        if ($actual === null) {
            $empty[] = $tree->path;

            continue;
        }

        if ($actual + 0.0001 < $floor) {
            $below[] = sprintf('%s is at %.1f%% against a floor of %d%%', $tree->path, $actual, $floor);
        }
    }

    // Printed rather than asserted. A tree with no code is not a tree that
    // failed, and reporting it as 0% would fail every empty one against any
    // floor above zero — where the cure would be to lower the floors, which is
    // the opposite of the point. Visible so that "nothing was measured" is
    // never mistaken for "everything passed".
    fwrite(STDOUT, sprintf(
        "\n  trees measured: %d, with no code yet: %d%s\n",
        count(MeasuredTree::all()) - count($empty),
        count($empty),
        $empty === [] ? '' : sprintf(' (%s)', implode(', ', $empty)),
    ));

    expect($below)->toBe([], sprintf(
        "These trees are below the bar their manifest set them:\n  %s\n\n"
        . 'Cover the lines, or lower the floor in the manifest nearest that tree and say '
        . 'why in the commit. Lowering it is one line in a reviewed diff, and the ratchet '
        . 'in G7 notices if the total drops (G9, G7).',
        implode("\n  ", $below),
    ));
});

it('G9 — the slack between the floors and the real numbers is visible', function (): void {
    $coverage = Coverage::fromReport();
    $slack = [];

    foreach (MeasuredTree::all() as $tree) {
        $floor = $tree->coverageFloor;

        if ($floor === null) {
            continue;
        }

        $actual = $coverage->percentageUnder($tree->path);

        if ($actual !== null && $actual - $floor > 1.0) {
            $slack[] = sprintf('%s: %.1f%% against a floor of %d%%', $tree->path, $actual, $floor);
        }
    }

    // Reported, never failed. Requiring the floor to track the measurement is
    // the gate that blocks its own cure: a tree gaining a well-tested class
    // raises its real coverage without anyone deciding to, and the build would
    // turn red for an improvement. So the slack is printed and argued down in
    // review, and only the declared numbers are ratcheted.
    //
    // What is asserted is that there was something to compare, which is the
    // one thing a reporter can be wrong about. None above their floor reads
    // exactly like every floor being flush, and a walk that found no trees at
    // all says it in the same words.
    expect(MeasuredTree::all())->not->toBe([], 'no tree is measured, so nothing was compared against a floor');

    fwrite(STDOUT, sprintf(
        "  trees above their floor: %d%s\n",
        count($slack),
        $slack === [] ? '' : sprintf("\n    %s", implode("\n    ", $slack)),
    ));
});
