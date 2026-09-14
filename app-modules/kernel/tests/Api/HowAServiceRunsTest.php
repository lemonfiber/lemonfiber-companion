<?php

declare(strict_types=1);

use Modules\Kernel\Api\HowAServiceRuns;

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
