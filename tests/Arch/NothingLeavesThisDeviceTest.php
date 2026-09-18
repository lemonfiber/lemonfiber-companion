<?php

declare(strict_types=1);

use Tests\Support\Manifests;

// The app sends no analytics, telemetry or crash reports to a third
// party.
//
// **This is about transmission, not about knowing what happened.** Logging,
// debugging and diagnostics are untouched and wanted: a log written to the
// device, `Log::debug`, a stack trace in a console, a report assembled for an
// operator to read — all of that is how a fault gets understood, and none of it
// is what this rule is about. What the rule refuses is a payload leaving the
// device for somebody who is not the operator.
//
// It is a requirement kept by *not* doing something, which is the kind that
// erodes: nobody adds telemetry on purpose here, and somebody adds a package
// that carries it. Sentry, Bugsnag and Flare all arrive as a dependency plus a
// service provider, and each is an ordinary thing to install in a Laravel
// application. The first anybody would know is a crash report on a third
// party's dashboard.
//
// Read over the manifests rather than over imports, because that is where it
// enters: a package in `require` ships whether or not any line here names it,
// and Laravel's discovery boots its provider without an import anywhere.
//
// `N1-R15` is the other half of the reason. A crash reporter's payload is a
// stack trace with local variables in it, and the local variables in this
// application are session tokens and stack addresses.

it('N4-R12 — no package that reports to a third party is installed', function (): void {
    $found = Manifests::reportingToAThirdParty();

    sort($found);

    expect($found)->toBe([], sprintf(
        "These packages send something off the device:\n  %s\n\n"
        . 'N4-R12 refuses analytics, telemetry and crash reporting outright — not '
        . "configured off, not sampled at zero, absent.\n"
        . 'The reason is N1-R15: a crash reporter sends a stack trace with local '
        . 'variables in it, and the local variables here are session tokens and stack '
        . "addresses. A sampling rate is not a guard against that.\n"
        . 'This does not touch logging. Write to the device, log as loudly as the '
        . 'problem deserves, and assemble a diagnostic report for the operator to send '
        . 'themselves (N4-R13) — that is the supported way to get a fault off a device, '
        . 'and it is the operator who sends it.',
        implode("\n  ", $found),
    ));
});
