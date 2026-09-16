<?php

declare(strict_types=1);

use Modules\Dx\Internal\WhatAStackWouldSay;
use Modules\Dx\Internal\WhatTheContractDeclares;
use Tests\Support\WhatTheContractAccepts;

// N1-R59 — what a stand-in answers with is derived from the published contract.
//
// The requirement exists because of a lesson this repository has already paid
// for twice: a fixture written by the author of its reader proves that both are
// wrong in the same way. A stand-in is nothing *but* fixtures, so a hand-written
// one lets every screen render beautifully against a shape no stack sends — the
// device looks right while the app is broken against every real machine.
//
// So nothing is written down. {@see WhatAStackWouldSay} reads each envelope's
// own `@phpstan-type Data` line out of the installed SDK and builds a value from
// it, and this asks the other half of the question: that what comes out is a
// payload the contract would accept. `WhatTheContractAccepts` is the same
// checker `G12` already holds every hand-written stand-in to, so the generated
// ones are held to the reader the rest of the suite trusts rather than to a
// second opinion written beside them.
//
// Over every envelope rather than the handful the app reads today. A generator
// that happened to be right about `status` and wrong about the rest is one
// screen away from being found out, and the cost of asking about all of them is
// a second.

it('finds the envelopes it claims to read', function (): void {
    // The floor. A generated tree this cannot see reports nothing to check and
    // passes, which is the shape of silence every rule in this repository is
    // written against.
    expect(WhatTheContractDeclares::everyEnvelope())->not->toBeEmpty();
});

it('N1-R59 — every payload the stand-in builds is one the contract accepts', function (): void {
    $refused = [];

    foreach (WhatTheContractDeclares::everyEnvelope() as $envelope) {
        $complaints = WhatTheContractAccepts::complaintsAbout(
            $envelope,
            ['data' => WhatAStackWouldSay::inside($envelope)],
        );

        if ($complaints !== []) {
            $refused[] = sprintf('%s: %s', $envelope, implode('; ', $complaints));
        }
    }

    expect($refused)->toBe([], sprintf(
        "The stand-in builds payloads the contract refuses:\n  %s\n\n"
        . 'What it answers with is read off the envelope\'s own declaration, so a refusal '
        . "here is the generator misreading the notation rather than the contract moving.\n"
        . 'The shape that caught this before was a union of two `array{…}` arms: read as '
        . 'one shape, their fields merge, and the result carries two arms\' fields at once '
        . '— which is a payload no stack sends (N1-R59).',
        implode("\n  ", $refused),
    ));
});
