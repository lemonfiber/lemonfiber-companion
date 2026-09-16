<?php

declare(strict_types=1);

use Modules\Dx\Internal\TheStandIns;

// Q-R72 — what a stand-in may take the place of, and what it may not.
//
// The registry is a list somebody appends to, and two things can go wrong when
// they do. Both are silent.
//
// A second stand-in naming a port the first already names is the worse of the
// two: the provider binds them in order, the container keeps the last, and the
// first simply never happens. Nothing raises, nothing logs, and what somebody
// sees on the device is one affordance that works and one that appears not to
// have been written.
//
// A stand-in naming something that is not a port is the other. The whole design
// rests on a stand-in replacing a seam the application already has — that is
// what makes what is being looked at the same code that ships, with one
// implementation swapped. A stand-in bound over a concrete class would be
// reaching past the seams, and what is on the screen would stop being evidence
// about anything.
//
// Written over the registry rather than over the one entry in it, so the day a
// second arrives this covers it without being edited.

it('Q-R72 — no two stand-ins claim the same port', function (): void {
    $claimed = [];
    $twice = [];

    foreach (TheStandIns::all() as $standIn) {
        $port = $standIn->insteadOf();

        if (in_array($port, $claimed, strict: true)) {
            $twice[] = $port;
        }

        $claimed[] = $port;
    }

    expect($twice)->toBe([], sprintf(
        "These ports are claimed by more than one stand-in:\n  %s\n\n"
        . 'The provider binds them in the order the registry lists them and the container '
        . "keeps the last, so one of the two silently never happens.\n"
        . 'Neither raises and neither logs: on the device it reads as an affordance that '
        . 'was never written. Give each stand-in its own port, or fold the two into one '
        . 'class that answers for both (Q-R72).',
        implode("\n  ", $twice),
    ));
});

it('Q-R72 — a stand-in replaces a port, and answers with something that is one', function (): void {
    $wrong = [];

    foreach (TheStandIns::all() as $standIn) {
        $port = $standIn->insteadOf();

        // An interface and not a class, because the point is to replace a seam
        // the application already has. Binding over a concrete class would be
        // reaching past the seams, and then what is on the screen is not
        // evidence about the code that ships.
        if (! interface_exists($port)) {
            $wrong[] = sprintf('%s replaces %s, which is not a port', $standIn::class, $port);

            continue;
        }

        if (! $standIn->which() instanceof $port) {
            $wrong[] = sprintf(
                '%s replaces %s and answers with %s, which is not one',
                $standIn::class,
                $port,
                $standIn->which()::class,
            );
        }
    }

    expect($wrong)->toBe([], sprintf(
        "These stand-ins do not answer for what they claim:\n  %s\n\n"
        . 'A stand-in names the port it replaces and the provider binds what it names, '
        . 'without checking — so a mismatch is a container that hands a screen something '
        . "it cannot call, at the moment the screen asks.\n"
        . 'The two halves are one class\'s promise to itself, which is exactly the kind '
        . 'nothing else can keep (Q-R72).',
        implode("\n  ", $wrong),
    ));
});

it('finds stand-ins to check', function (): void {
    // The floor. An empty registry passes both rules above with no iterations,
    // which is the shape of silence every rule in this repository is written
    // against — and an empty registry is also a module that does nothing,
    // which is the state `StandsIn` says this one must not sit in.
    expect(TheStandIns::all())->not->toBeEmpty();
});
