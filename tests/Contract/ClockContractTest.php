<?php

declare(strict_types=1);

use Modules\Device\Api\SystemClock;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Instant;
use Tests\Support\Fakes\FrozenClock;

// The Clock contract, run against the adapter and against the fake.
//
// The same assertions both times, which is the whole of G2. A fake is only
// worth having if it is held to what it replaces: every other test in this
// suite hands its subject a FrozenClock and never sees SystemClock at all, so
// this file is the one place the two are compared.
//
// What is asserted here is deliberately only what both must promise. A frozen
// clock does not advance and the platform's does, so "time moves" belongs to
// the adapter's own tests rather than to the contract — a contract that
// asserted it would either fail on the fake or be quietly weakened to pass,
// and a weakened contract is how a fake drifts.
//
// It does not ask whether `now()` answers an `Instant`. That is the signature:
// the analyser refuses a clock answering anything else at rest and the runtime
// refuses it on the way out, so an expectation here is not what stands between
// a clock and that mistake. What one can be wrong about is the moment.

const A_MOMENT = 1_757_000_000;

/** How far the platform's clock may be from PHP's own reading of it. */
const A_FEW_SECONDS = 5;

const AN_HOUR = 3600;

/** @return array<string, callable(): Clock> */
function clocks(): array
{
    return [
        'SystemClock' => static fn(): Clock => new SystemClock(),
        'FrozenClock' => static fn(): Clock => FrozenClock::at(Instant::atEpochSeconds(A_MOMENT)),
    ];
}

foreach (clocks() as $name => $build) {
    it(sprintf('%s does not go backwards', $name), function () use ($build): void {
        // The one promise every clock makes and the only one a test can check
        // without knowing which clock it has. Both read at second resolution,
        // so two reads in a row are the same moment or a later one — never an
        // earlier one, which is what an expiry check would get wrong.
        $clock = $build();
        $first = $clock->now();
        $second = $clock->now();

        expect($second->isBefore($first))->toBeFalse();
    });

    it(sprintf('%s answers the same moment when asked twice in a row', $name), function () use ($build): void {
        $clock = $build();

        expect($clock->now()->is($clock->now()))->toBeTrue();
    });

    it(sprintf('%s answers a moment after the epoch', $name), function () use ($build): void {
        expect($build()->now()->epochSeconds())->toBeGreaterThan(0);
    });
}

// Beyond the contract: what each one promises that the other does not.

it('SystemClock reads the platform clock rather than a fixed moment', function (): void {
    // Not a tautology against `time()`: what it refuses is a clock that has
    // been quietly turned into a constant, which is the failure a contract
    // shared with a frozen clock cannot see.
    $clock = new SystemClock();

    expect(abs($clock->now()->epochSeconds() - time()))->toBeLessThan(A_FEW_SECONDS);
});

it('FrozenClock answers exactly what the test told it', function (): void {
    expect(FrozenClock::at(Instant::atEpochSeconds(A_MOMENT))->now()->epochSeconds())->toBe(A_MOMENT);
});

it('FrozenClock moves when the test moves it', function (): void {
    // The reason the fake is mutable where nothing else here is: a test about
    // a session expiring has to move time between two calls, and building a
    // second subject halfway through would assert on something it did not set
    // up.
    $clock = FrozenClock::at(Instant::atEpochSeconds(A_MOMENT));
    $later = Instant::atEpochSeconds(A_MOMENT + AN_HOUR);

    $clock->moveTo($later);

    expect($clock->now()->is($later))->toBeTrue();
});
