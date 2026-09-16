<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowAServiceRuns;
use Modules\Kernel\Api\WhatToDoWithIt;

it('N2-R7 — says where a service stands, as a key', function (): void {
    foreach (HowAServiceRuns::cases() as $runs) {
        expect($runs->saidOnTheScreen())->toBe(sprintf('health.service.%s', $runs->value));
    }
});

it('N2-R7 — a service the host runs is not this stack\'s to start or stop', function (): void {
    // Not the same as a control temporarily out of reach, which `N1-R3` says is
    // offered and reported on. This control does not exist.
    expect(HowAServiceRuns::HostManaged->isThisStacksToRun())->toBeFalse();

    foreach (HowAServiceRuns::cases() as $runs) {
        if ($runs === HowAServiceRuns::HostManaged) {
            continue;
        }

        expect($runs->isThisStacksToRun())->toBeTrue($runs->value);
    }
});

it('N2-R8 — restarting something already being restarted is the case worth saying', function (): void {
    // A crash loop is *starting*, repeatedly. Another restart adds a start to a
    // queue of starts, and the honest statement before confirming is that it
    // will not help.
    expect(HowAServiceRuns::CrashLooping->isAlreadyBeingRestarted())->toBeTrue()
        ->and(HowAServiceRuns::Starting->isAlreadyBeingRestarted())->toBeFalse()
        ->and(HowAServiceRuns::Failed->isAlreadyBeingRestarted())->toBeFalse();
});

it('stopped and failed are two different things', function (): void {
    // The pair an operator most needs told apart: one was turned off and the
    // other fell over. A screen drawing them the same way has somebody
    // restarting a service that is off on purpose.
    expect(HowAServiceRuns::Stopped)->not->toBe(HowAServiceRuns::Failed)
        ->and(HowAServiceRuns::Stopped->saidOnTheScreen())
        ->not->toBe(HowAServiceRuns::Failed->saidOnTheScreen());
});

it('the order is the contract\'s, worst first', function (): void {
    // The position is meaning: a screen sorting alphabetically puts a crashed
    // service under a healthy one, and the operator scrolls past the row they
    // opened the app for.
    expect(array_map(static fn(HowAServiceRuns $runs): string => $runs->value, HowAServiceRuns::cases()))
        ->toBe([
            'failed',
            'crash-looping',
            'unhealthy',
            'absent',
            'stopped',
            'starting',
            'running',
            'healthy',
            'host-managed',
        ]);
});

it('N1-R27 — only a starting service becomes something else on its own', function (): void {
    // The one state that resolves without anybody touching the phone, which is
    // what a stated cadence is for. Every other case is a standing answer, so a
    // screen polling on any of them would be polling on a listing that cannot
    // change — the thing `N1-R17` refuses.
    expect(HowAServiceRuns::Starting->isSettling())->toBeTrue();

    foreach (HowAServiceRuns::cases() as $runs) {
        if ($runs === HowAServiceRuns::Starting) {
            continue;
        }

        expect($runs->isSettling())->toBeFalse($runs->value);
    }
});

it('N2-R7 — a state takes only the verbs that mean something to it', function (): void {
    // The whole table, written out, because that is what it is. A screen used
    // to offer all three on every row, which put *start* on a service that is
    // running and *stop* on one that has crashed — both refused by the machine,
    // and both teaching an operator that the buttons there are a guess.
    //
    // Read as *which verbs* rather than asserted one call at a time, so a state
    // that quietly gained one is a row in this table that stopped matching
    // rather than an assertion nobody wrote.
    $offered = [];

    foreach (HowAServiceRuns::cases() as $runs) {
        $taken = [];

        foreach (WhatToDoWithIt::cases() as $verb) {
            if ($runs->mayTake($verb)) {
                $taken[] = $verb->value;
            }
        }

        $offered[$runs->value] = $taken;
    }

    expect($offered)->toBe([
        // Down, for a fault or by decision: starting is the only one of the
        // three that means anything.
        'failed' => ['start'],
        // Already being started over and over, so stopping is the way out.
        'crash-looping' => ['stop'],
        // Up and answering badly, which both of the acting verbs address.
        'unhealthy' => ['stop', 'restart'],
        'absent' => ['start'],
        'stopped' => ['start'],
        // On its way up: stopping is how somebody changes their mind, and a
        // restart is a start queued behind a start.
        'starting' => ['stop'],
        'running' => ['stop', 'restart'],
        'healthy' => ['stop', 'restart'],
        // The host's, so none of them.
        'host-managed' => [],
    ]);
});

it('N2-R7 — nothing this stack does not run can be told to do anything', function (): void {
    // The same line `isThisStacksToRun()` draws, asked from the other side: a
    // state that is not this stack's to run offers no verb, and one that is
    // offers at least one. A row that is ours and offers nothing would draw an
    // empty group of controls, which reads as buttons that failed to appear.
    foreach (HowAServiceRuns::cases() as $runs) {
        $any = false;

        foreach (WhatToDoWithIt::cases() as $verb) {
            $any = $any || $runs->mayTake($verb);
        }

        expect($any)->toBe($runs->isThisStacksToRun(), $runs->value);
    }
});
