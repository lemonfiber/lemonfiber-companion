<?php

declare(strict_types=1);

use Tests\Support\Module;

// H4 — a test file mirrors its source file's location.
//
// One direction only, and the asymmetry is the whole design.
//
// An orphan test fails: `tests/Health/VerdictPolicyTest.php` with no
// `src/Health/VerdictPolicy.php` behind it means the class was renamed, moved
// or deleted and the test was left describing something that no longer exists.
// Those tests keep passing — they are usually asserting on a fixture — and they
// are read by the next person as current documentation of the design.
//
// A class with no dedicated test does NOT fail. The coverage and mutation
// floors already prove every line and every decision is exercised, and by a
// test that had a reason to exist. Requiring one file per class on top of that
// manufactures exactly the tests this suite is trying to avoid: a `it can be
// constructed` for a value object, written to satisfy a counter, that asserts
// nothing anyone cares about and has to be maintained forever.

it('H4 — every test sits beside the thing it tests', function (): void {
    $orphans = [];

    foreach (Module::all() as $module) {
        foreach ($module->testFiles() as $test) {
            $source = str_replace(
                [sprintf('%s/tests/', $module->path), 'Test.php'],
                [sprintf('%s/src/', $module->path), '.php'],
                $test,
            );

            if (! is_file($source)) {
                $orphans[] = sprintf('%s has no %s', $test, $source);
            }
        }
    }

    expect($orphans)->toBe([], sprintf(
        "These tests describe something that is not there:\n  %s\n\n"
        . 'A test whose subject moved keeps passing, because it asserts on the fixture '
        . 'it set up rather than on the application. It then gets read as a current '
        . 'description of a design that changed. Move the test to match, or delete it '
        . "(H4).\nThe reverse is deliberately not checked: a class needs no file named "
        . 'after it, because the coverage and mutation floors already prove it is '
        . 'exercised — and by a test somebody had a reason to write.',
        implode("\n  ", $orphans),
    ));
});
