<?php

declare(strict_types=1);

use Tests\Support\Coverage;
use Tests\Support\Module;

// G9 — no module is below the floor it declared.
//
// One percentage for twelve modules is an average, and an average is true about
// what it covered and silent about what it covered over: a capability at 100%
// carries an adapter at 40% and the gate reports a pass. The same clover report
// already holds the per-file numbers, so splitting it by directory costs
// nothing at the point of measurement and turns one number into twelve.
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

it('G9 — every module meets the coverage floor it declared', function (): void {
    $coverage = Coverage::fromReport();
    $below = [];
    $empty = [];

    foreach (Module::all() as $module) {
        $floor = $module->coverageFloor;

        // An undeclared floor is G7's finding, reported there by name. Skipping
        // it here would be the silent exemption both rules exist to remove, so
        // G7 runs in the Arch suite where it cannot be missed.
        if ($floor === null) {
            continue;
        }

        $actual = $coverage->percentageUnder($module->relativeSourcePath());

        if ($actual === null) {
            $empty[] = $module->name;

            continue;
        }

        if ($actual + 0.0001 < $floor) {
            $below[] = sprintf('%s is at %.1f%% against a floor of %d%%', $module->name, $actual, $floor);
        }
    }

    // Printed rather than asserted. A module with no code is not a module that
    // failed, and reporting it as 0% would fail every empty module against any
    // floor above zero — where the cure would be to lower the floors, which is
    // the opposite of the point. Visible so that "nothing was measured" is
    // never mistaken for "everything passed".
    fwrite(STDOUT, sprintf(
        "\n  modules measured: %d, with no code yet: %d%s\n",
        count(Module::all()) - count($empty),
        count($empty),
        $empty === [] ? '' : sprintf(' (%s)', implode(', ', $empty)),
    ));

    expect($below)->toBe([], sprintf(
        "These modules are below the bar they set themselves:\n  %s\n\n"
        . 'Cover the lines, or lower the floor in that module\'s manifest and say why in '
        . 'the commit. Lowering it is one line in a reviewed diff, and the ratchet in G7 '
        . 'notices if the total drops (G9, G7).',
        implode("\n  ", $below),
    ));
});

it('G9 — the slack between the floors and the real numbers is visible', function (): void {
    $coverage = Coverage::fromReport();
    $slack = [];

    foreach (Module::all() as $module) {
        $floor = $module->coverageFloor;

        if ($floor === null) {
            continue;
        }

        $actual = $coverage->percentageUnder($module->relativeSourcePath());

        if ($actual !== null && $actual - $floor > 1.0) {
            $slack[] = sprintf('%s: %.1f%% against a floor of %d%%', $module->name, $actual, $floor);
        }
    }

    // Reported, never failed. Requiring the floor to track the measurement is
    // the gate that blocks its own cure: a module gaining a well-tested class
    // raises its real coverage without anyone deciding to, and the build would
    // turn red for an improvement. So the slack is printed and argued down in
    // review, and only the declared numbers are ratcheted.
    expect($slack)->toBeArray();

    fwrite(STDOUT, sprintf(
        "  modules above their floor: %d%s\n",
        count($slack),
        $slack === [] ? '' : sprintf("\n    %s", implode("\n    ", $slack)),
    ));
});
