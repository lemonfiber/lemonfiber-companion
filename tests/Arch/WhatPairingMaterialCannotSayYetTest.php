<?php

declare(strict_types=1);

use Modules\Kernel\Api\WhatPairingMaterialSays;

// N1-R17 — the one thing pairing material does not say, and what waits on it.
//
// Material carries an address, a fingerprint and an expiry, and nothing that
// says *which machine*. The app holds several, so it has to decide
// that anyway, and both fields it could decide from are the ones re-pairing
// exists to change: an address DHCP moved, a certificate the stack announced it
// would replace. Matching on either identifies a stack by exactly the thing
// that just changed.
//
// What that costs is visible today. `Introducing::built()` mints a fresh
// identifier from entropy on every pairing, and `Configured::with()` matches on
// that identifier to decide whether this is a machine already held — so the
// branch that replaces one instead of adding a second cannot fire from the flow
// the app tells operators to use. Pairing the same machine twice leaves two
// rows on the screen whose whole job is to say which machines are in the house.
//
// It is not fixable here, and `N1-R17` says so: a capability the contract does
// not carry is raised against the contract, and the dependent work stops until
// it is closed.
//
// **The raise has landed, and the gap has moved.** `N1-R62` now requires the
// material to carry an identifier that is the stack's own and survives a
// re-issue, a change of address and a replacement of the certificate; `N1-R63`
// requires the app to decide which machine from that identifier alone and to
// replace what it holds rather than add a second; `N1-R64` settles the session
// on either side of a changed fingerprint. What is asked for is now written
// down. What is missing is a producer: no repository mints pairing material
// yet, so there is no field for this app to read and no version to raise
// against. The wait is on the machine that issues the material, not on the
// requirement any more.
//
// **The watch is the enum rather than the flaw.** {@see WhatPairingMaterialSays}
// is closed, so the material growing a field is the app growing a case here and
// nowhere else. The day one lands, this goes red and names the work: match on
// it in `Configured::with()`, restore the test that proved the duplicate, and
// answer `N1-R64` at the moment of replacement — a re-pairing that changes the
// pinned fingerprint forgets the session held for that stack, and one that does
// not keeps it.
//
// A register that only recorded the gap would outlive it silently, which is the
// one failure this shape cannot survive.

it('N1-R17 — pairing material still says nothing about which machine it is for', function (): void {
    $says = array_map(
        static fn(WhatPairingMaterialSays $said): string => $said->value,
        WhatPairingMaterialSays::cases(),
    );

    expect($says)->toBe(['address', 'fingerprint', 'expires'], sprintf(
        "Pairing material now says: %s\n\n"
        . "If a case was added, the gap this register holds may be closed. Check whether it names the\n"
        . "stack; if it does, `Configured::with()` must match on it instead of on the identifier\n"
        . "`Introducing::built()` mints, and the re-pairing case belongs back in\n"
        . "`TheFirstRunCanBeFinishedWithNoStackRunningTest` — scanning one code twice is one machine.\n"
        . "`N1-R62` says what the field must be, `N1-R63` what to decide from it, and `N1-R64`\n"
        . "whether the session survives a re-pairing that changed the fingerprint.\n\n"
        . 'If a case was removed, that is a narrowing of what a stack may say and this line is the '
        . 'wrong place to record it.',
        implode(', ', $says),
    ));
});
