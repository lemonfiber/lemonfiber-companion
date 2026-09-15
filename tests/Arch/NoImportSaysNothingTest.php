<?php

declare(strict_types=1);

use Tests\Support\OurCode;
use Tests\Support\Tree;

// W5 — an import that does nothing fails the run without saying so.
//
// `use Closure;` in a file that declares no namespace imports a name into the
// namespace it is already in. PHP warns — "The use statement with non-compound
// name 'Closure' has no effect" — and that warning is the problem, because of
// where it happens.
//
// It is emitted while the file is being *compiled*, before PHPUnit's issue
// collector is listening. `G11` makes a diagnostic fail the run, so the run
// fails; and because nothing collected the warning, no part of the output says
// why. The observed behaviour is a suite reporting every test passed and
// exiting 1, with no message on stdout, none on stderr, and nothing in the
// JUnit log. That cost an hour to find once.
//
// Inside a namespace the same statement is meaningful and silent, which is why
// seventy-seven of them in this repository are fine and were left alone. The
// rule is narrow on purpose: only a file with no namespace of its own, which in
// practice means every Pest test file.

it('W5 — no import in a global-namespace file says nothing', function (): void {
    $offenders = [];

    // Every PHP file this repository owns rather than four directories. The
    // four left out `routes/` and `scripts/`, which are the two places outside
    // a test that declare no namespace at all — so they are where this is most
    // likely to be written and were the only places nothing looked (`R4`).
    foreach (OurCode::phpFiles() as $path) {
        $said = (string) file_get_contents($path);

        if (preg_match('/^namespace\s+/m', $said) === 1) {
            continue;
        }

        if (preg_match_all('/^use\s+(?!function\s|const\s)([A-Za-z_][A-Za-z0-9_]*)\s*;/m', $said, $found) !== 0) {
            foreach ($found[1] as $name) {
                $offenders[] = sprintf(
                    '%s imports %s into the namespace it is already in',
                    str_replace(sprintf('%s/', Tree::root()), '', $path),
                    $name,
                );
            }
        }
    }

    expect($offenders)->toBe([], sprintf(
        "These imports do nothing, and PHP says so in a way nothing can hear:\n  %s\n\n"
        . 'A file with no namespace of its own is already in the global one, so importing '
        . "a global name into it has no effect and PHP raises a warning.\n"
        . 'The warning is raised while the file is compiled, before PHPUnit is listening '
        . 'for issues. G11 makes a diagnostic fail the run, so the run fails — and since '
        . 'nothing collected the warning, nothing says why: every test reports passing, '
        . "the process exits 1, and stdout, stderr and the JUnit log are all silent.\n"
        . 'Delete the import. A global class needs none (W5).',
        implode("\n  ", $offenders),
    ));
});
