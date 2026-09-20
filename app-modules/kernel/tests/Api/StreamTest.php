<?php

declare(strict_types=1);

use Modules\Kernel\Api\Stream;

it('N2-R10 — says which mouth a line came out of, as a key', function (): void {
    expect(Stream::Stdout->saidOnTheScreen())->toBe('health.stream.stdout')
        ->and(Stream::Stderr->saidOnTheScreen())->toBe('health.stream.stderr');
});

it('one of the two is worth letting stand out', function (): void {
    expect(Stream::Stderr->worthNoticing())->toBeTrue()
        ->and(Stream::Stdout->worthNoticing())->toBeFalse();
});

it('worth noticing is deliberately weaker than wrong', function (): void {
    // Plenty of well-behaved services write ordinary progress to `stderr`, so a
    // screen treating this as *error* would put a red mark against a service
    // that is working. That judgement belongs to `Severity`, which comes from a
    // check that decided something.
    //
    // So what is held here is that nothing on this enum can answer *how bad*:
    // a key and a bool, and nothing else a screen could take a severity off.
    // The whole surface rather than one method name somebody thought of,
    // because that is not how the distinction gets lost — a screen needs a
    // colour, this is the value in hand, and something appears here to give it
    // one. Three of the five are the language's own, which
    // `get_class_methods()` counts too.
    //
    // Not the number of cases, which is a real thing about this enum and is
    // held somewhere it can fail: `EveryWireValueIsACaseTest` reads the two
    // against the contract the stack answers by. A count written out here
    // agrees with whatever this file says and notices nothing.
    $surface = get_class_methods(Stream::class);

    sort($surface);

    expect($surface)->toBe(['cases', 'from', 'saidOnTheScreen', 'tryFrom', 'worthNoticing'], sprintf(
        "`Stream` answers something other than the two readers it is meant to:\n  %s\n\n"
        . 'A line is stdout or stderr, and that is a file descriptor rather than a verdict, '
        . 'so whatever a method added here answers, a screen will read it as severity. Put '
        . 'it on `Severity`, which comes from a check that decided something. A name gone '
        . 'from the list is the other direction, and the two rules above this one are where '
        . 'that shows up as something an operator would notice.',
        implode(', ', $surface),
    ));
});
