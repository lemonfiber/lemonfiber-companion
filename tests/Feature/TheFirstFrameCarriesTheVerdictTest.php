<?php

declare(strict_types=1);

use Modules\Kernel\Api\Address;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StackName;
use Modules\Operator\Internal\Screens\YourStacks;
use Tests\Support\Fakes\AKeychainInMemory;
use Tests\Support\Fakes\AShareSheetThatWasOffered;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\StacksInMemory;
use Tests\Support\Fakes\VerdictsInMemory;

// `N2-R1`, `N1-R9`, `N2-R13` — the app opens on the verdict, and says how old it is.
//
// `N2` calls its ordering the whole design: *is anything wrong*, then *what*,
// then *may I fix it from here*. Until this existed the first screen answered
// none of them — a list of machines and the names their owner gave them, with
// the verdict two taps and a network round trip away behind whichever stack
// they guessed at first.
//
// What makes it allowed is that it is held rather than asked. `N1-R17` says a
// screen is not a poller and opening an app is not a reason to talk to four
// machines, so the word comes out of a store — which makes every one of these a
// retained reading, which is exactly the case `N1-R24` permits on opening and
// `N1-R9` requires carry its age.

/** The moment every case below is read at, so an age is a thing a test states. */
const NOW = 1_770_000_000;

/** Named for this file: the root suites share one namespace (`G10`). */
function aStackToOpenOn(string $called = 'The loft', string $seed = 'a'): Stack
{
    return Stack::of(
        StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST))),
        StackName::of($called),
        Address::of('https://192.168.1.42'),
        Fingerprint::of(str_repeat($seed, Fingerprint::CHARACTERS)),
    );
}

/** The launch screen, over stacks and verdicts a test states. */
function theOpeningScreen(Stack $stack, ?VerdictsInMemory $verdicts = null, int $now = NOW): YourStacks
{
    return new YourStacks(
        StacksInMemory::holding($stack),
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $verdicts ?? VerdictsInMemory::working(),
        FrozenClock::at(Instant::atEpochSeconds($now)),
    );
}

it('N2-R1 — opens on what the stack last came to', function (): void {
    $stack = aStackToOpenOn();
    $verdicts = VerdictsInMemory::working()
        ->lastSeen($stack->id(), Overall::Broken, Instant::atEpochSeconds(NOW - 7_200));

    $shown = theOpeningScreen($stack, $verdicts)->lastKnownOf($stack);

    expect($shown->isKnown)->toBeTrue()
        ->and($shown->said)->toBe(Overall::Broken->saidOnTheScreen())
        ->and(__($shown->said))->not->toBe($shown->said);
});

it('N1-R28 — a stack paired and never asked opens on no verdict at all', function (): void {
    // An ordinary state rather than a defensive one: pairing and asking are
    // separate screens and an operator can leave between them. The row shows
    // the machine and offers the way in, which is what it did before.
    $stack = aStackToOpenOn();

    expect(theOpeningScreen($stack)->lastKnownOf($stack)->isKnown)->toBeFalse();
});

it('N2-R13 — a held verdict carries how long ago it was read', function (): void {
    $stack = aStackToOpenOn();
    $verdicts = VerdictsInMemory::working()
        ->lastSeen($stack->id(), Overall::Degraded, Instant::atEpochSeconds(NOW - 7_200));

    $shown = theOpeningScreen($stack, $verdicts)->lastKnownOf($stack);

    expect($shown->agoSaid)->toBe(HowLongAgo::Hours->saidOnTheScreen())
        ->and($shown->agoCount)->toBe(2);
});

it('N2-R13 — the age is said in whichever unit it fills', function (): void {
    // Three bands, and the boundaries are the point: one second under an hour
    // is still minutes, and one second over is an hour. A band chosen by `>=`
    // that should be `>` moves every reading on the screen by one unit, which
    // nothing but a case at the boundary can catch.
    $stack = aStackToOpenOn();

    $said = static function (int $ago) use ($stack): string {
        $verdicts = VerdictsInMemory::working()
            ->lastSeen($stack->id(), Overall::Healthy, Instant::atEpochSeconds(NOW - $ago));
        $shown = theOpeningScreen($stack, $verdicts)->lastKnownOf($stack);

        return sprintf('%s|%d', $shown->agoSaid, $shown->agoCount);
    };

    expect($said(0))->toBe('health.ago.minutes|0')
        ->and($said(59))->toBe('health.ago.minutes|0')
        ->and($said(60))->toBe('health.ago.minutes|1')
        ->and($said(3_599))->toBe('health.ago.minutes|59')
        ->and($said(3_600))->toBe('health.ago.hours|1')
        ->and($said(86_399))->toBe('health.ago.hours|23')
        ->and($said(86_400))->toBe('health.ago.days|1')
        ->and($said(864_000))->toBe('health.ago.days|10');
});

it('N2-R13 — a reading from the future is moments ago, not a time to come', function (): void {
    // A device whose clock moved backwards, or a stack whose clock is ahead.
    // This app knows the reading is not old and does not know enough to say
    // anything else; the raw subtraction would put *in three hours* on the
    // screen.
    $stack = aStackToOpenOn();
    $verdicts = VerdictsInMemory::working()
        ->lastSeen($stack->id(), Overall::Healthy, Instant::atEpochSeconds(NOW + 10_800));

    $shown = theOpeningScreen($stack, $verdicts)->lastKnownOf($stack);

    expect($shown->agoSaid)->toBe(HowLongAgo::Minutes->saidOnTheScreen())
        ->and($shown->agoCount)->toBe(0);
});

it('L1 — every band names a line, and it counts on the number beside it', function (): void {
    // `trans_choice` is what the template calls, because *a minute ago* and
    // *two minutes ago* are not the same sentence in either language this app
    // speaks. A line written without the plural forms renders the same words
    // for one and for many, which reads as a bug in the clock.
    foreach (HowLongAgo::cases() as $unit) {
        expect(trans_choice($unit->saidOnTheScreen(), 1))
            ->not->toBe($unit->saidOnTheScreen(), $unit->name)
            ->and(trans_choice($unit->saidOnTheScreen(), 2))
            ->not->toBe(trans_choice($unit->saidOnTheScreen(), 1), $unit->name);
    }
});

it('N1-R11 — one stack\'s verdict is not another\'s', function (): void {
    $loft = aStackToOpenOn('The loft', 'a');
    $shed = aStackToOpenOn('The shed', 'b');

    $verdicts = VerdictsInMemory::working()
        ->lastSeen($loft->id(), Overall::Broken, Instant::atEpochSeconds(NOW - 60))
        ->lastSeen($shed->id(), Overall::Healthy, Instant::atEpochSeconds(NOW - 60));

    $screen = new YourStacks(
        StacksInMemory::holding($loft, $shed),
        AKeychainInMemory::working(),
        AShareSheetThatWasOffered::working(),
        $verdicts,
        FrozenClock::at(Instant::atEpochSeconds(NOW)),
    );

    expect($screen->lastKnownOf($loft)->said)->toBe(Overall::Broken->saidOnTheScreen())
        ->and($screen->lastKnownOf($shed)->said)->toBe(Overall::Healthy->saidOnTheScreen());
});

it('N2-R13 — a reading from the future counts as nothing, not as a negative', function (): void {
    // The rendered phrase cannot prove this: nought seconds and one second land
    // in the same band and read the same. Only the number separates a floor
    // that holds from one that is off by one, and being off by one here means
    // the screen says a reading was taken before it was — which is the shape
    // `HowLongAgo::secondsBetween()` exists to refuse.
    $stack = aStackToOpenOn();
    $verdicts = VerdictsInMemory::working()
        ->lastSeen($stack->id(), Overall::Healthy, Instant::atEpochSeconds(NOW + 10_800));

    expect(theOpeningScreen($stack, $verdicts)->lastKnownOf($stack)->agoCount)->toBe(0);
});

it('a stack with nothing held carries no words at all, not merely no flag', function (): void {
    // The row is drawn from these three, so asserting only `isKnown` leaves the
    // template free to render whatever the others hold. A verdict key here
    // would put a word on a stack that has never been asked.
    $stack = aStackToOpenOn();

    $nothing = theOpeningScreen($stack)->lastKnownOf($stack);

    expect($nothing->isKnown)->toBeFalse()
        ->and($nothing->said)->toBe('')
        ->and($nothing->agoSaid)->toBe('')
        ->and($nothing->agoCount)->toBe(0);
});

it('N1-R33 — a held value that is not a verdict opens on no verdict', function (): void {
    // The port answers `Reading`, whose retained arm is typed `object` — it
    // carries whatever was put in it, which for a store is whatever the last
    // build wrote. A screen that took it on trust would call
    // `saidOnTheScreen()` on something that has no such method, at launch, in
    // front of the operator. The guard is what makes that a row with no
    // verdict instead.
    $stack = aStackToOpenOn();
    $verdicts = VerdictsInMemory::working()
        ->lastSeenAsSomethingElse($stack->id(), Instant::atEpochSeconds(NOW - 60));

    expect(theOpeningScreen($stack, $verdicts)->lastKnownOf($stack)->isKnown)->toBeFalse();
});
