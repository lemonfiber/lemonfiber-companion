<?php

declare(strict_types=1);

use Tests\Support\Settings;

// Certificate verification is not disabled in any build, under any
// flag or configuration value.
//
// Three spellings, three mechanisms, and this is the third. S3's PHPStan rule
// reads array items, so it sees `['verify' => false]` however the options array
// is written. A second rule beside it refuses `withoutVerifying()` and the curl
// options, which are a call and a positional argument and invisible to the
// first. Both read PHP.
//
// A configuration value is not PHP. `LEMONFIBER_VERIFY_TLS=false` in an
// environment file is a build where verification is off, and every rule above
// reads the source and finds nothing wrong with it — because there is nothing
// wrong with it. The switch is in the deployment.
//
// **So the flag may not exist, whatever it is set to.** That is the literal
// reading of the requirement and it is also the only enforceable one: a default
// is a fact about one file at one moment, and "must not be disabled under any
// flag" is a statement about every environment this app will ever run in. A
// setting that defaults to secure is still a setting somebody can change in
// a `.env` nobody reviews.

it('N1-R21 — no setting exists that could turn certificate verification off', function (): void {
    $found = Settings::namingVerification();

    sort($found);

    expect($found)->toBe([], sprintf(
        "These settings name certificate verification:\n  %s\n\n"
        . 'N1-R21 says verification MUST NOT be disabled in any build under any flag or '
        . "configuration value, so the flag may not exist — not even defaulting to on.\n"
        . 'A default is a fact about one file at one moment; the requirement is about '
        . 'every environment this app will run in, and a `.env` is not reviewed. '
        . 'ADR-0018 is what stands behind this: a stack is trusted by a fingerprint '
        . 'taken from the material an operator scanned, so there is no certificate '
        . "authority to fall back to and nothing for a switch to usefully relax.\n"
        . 'If a certificate is being refused, the fingerprint is the thing to look at '
        . '(N1-R21, S3).',
        implode("\n  ", $found),
    ));
});
