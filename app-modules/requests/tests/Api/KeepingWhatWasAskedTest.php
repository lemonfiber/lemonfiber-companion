<?php

declare(strict_types=1);

namespace Modules\Requests\Tests\Api;

use function expect;
use function implode;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowARequestStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealedStack;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Modules\Requests\Api\WhatWasKeptOfWhatWasAsked;

use function sprintf;
use function str_repeat;

use Tests\Support\Fakes\ASealInMemory;
use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\ReadingsInMemory;
use Tests\Support\WhatIsKeptOfRequests;

/** The moment a kept reading in these tests was read at. */
const WHEN_THE_REQUESTS_WERE_READ = 1_790_000_000;

/** The stack a reading is kept for. */
function theStackTheRequestsAreKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('a', Nonce::SHORTEST)));
}

/** A second stack, whose reading must never answer for the first's. */
function theOtherStackTheRequestsAreKeptFor(): StackId
{
    return StackId::of(Nonce::of(str_repeat('b', Nonce::SHORTEST)));
}

/** A phone that seals, its clock this many seconds after the reading was read. */
function aPhoneKeepingRequests(int $seconds = 0): WhatIsKeptOfRequests
{
    return WhatIsKeptOfRequests::onAPhoneThatSealsAt(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ + $seconds));
}

/** One request as one phrase: its number, who and what, how big, where it stands, and why it was refused. */
function everythingTheRequestSays(Wanted $wanted): string
{
    return sprintf(
        '#%d %s for %s, %s, %s, %s',
        $wanted->number(),
        $wanted->by(),
        $wanted->forWhat(),
        $wanted->size()->either(
            measured: static fn(int $bytes): Code => Code::of(sprintf('measured %d', $bytes)),
            guessed: static fn(int $bytes): Code => Code::of(sprintf('guessed %d', $bytes)),
            unknown: static fn(): Code => Code::of('size unknown'),
        )->shown(),
        $wanted->standing()->either(
            said: static fn(Waiting $said): Code => Code::of($said->value),
            unnamed: static fn(): Code => Code::of('unnamed'),
        )->shown(),
        $wanted->refusal(
            was: static fn(TurnedDown $why): Code => Code::of(sprintf('refused "%s" %s', $why->reason(), $why->when(
                then: static fn(string $when): Code => Code::of(sprintf('at %s', $when)),
                unstated: static fn(): Code => Code::of('at no stated time'),
            )->shown())),
            wasNot: static fn(): Code => Code::of('not refused'),
        )->shown(),
    );
}

/** Everything a reading says, as one line, so two readings can be compared whole. */
function everythingTheRequestsSay(Requested $requested): string
{
    $said = [];

    foreach ($requested as $wanted) {
        $said[] = everythingTheRequestSays($wanted);
    }

    return $said === [] ? 'nothing asked' : implode(' | ', $said);
}

/** What the screen opens on, as one line: nothing, or the reading as of when, and how long ago. */
function whatTheRequestsScreenOpensOn(WhatWasKeptOfWhatWasAsked $kept): string
{
    return $kept->either(
        kept: static fn(Requested $requested, Instant $readAt, Instant $now): Code
            => Code::of(sprintf('as of %d, %d ago: %s', $readAt->epochSeconds() - WHEN_THE_REQUESTS_WERE_READ, $now->epochSeconds() - $readAt->epochSeconds(), everythingTheRequestsSay($requested))),
        nothing: static fn(): Code => Code::of('nothing'),
    )->shown();
}

/** Whether a keep was noted as written, as one word. */
function whetherTheRequestsWereKept(Noted $noted): string
{
    return $noted->either(
        down: static fn(): Code => Code::of('kept'),
        notKept: static fn(): Code => Code::of('not kept'),
    )->shown();
}

it('keeps a reading sealed, read now, and hands it back whole with when it was read', function (): void {
    $kept = aPhoneKeepingRequests();

    expect(whetherTheRequestsWereKept($kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart())))->toBe('kept');

    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ + 7_200));

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe(
        'as of 0, 7200 ago: #1 Mira for Dune, measured 4200000000, waiting-for-approval, not refused'
        . ' | #2 Joost for Severance, guessed 30000000000, getting, not refused'
        . ' | #3 Mira for Cats, size unknown, declined, refused "We have it already" at 2026-09-30 21:04'
        . ' | #4 Joost for The Room, measured 700000000, declined, refused "Not in this house" at no stated time'
        . ' | #5 Joost for Twin Peaks, size unknown, unnamed, not refused',
    );
});

it('keeps a household that asked for nothing as one that asked for nothing', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingOfNothing());

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('as of 0, 0 ago: nothing asked');
});

it('keeps nothing the store could read, and names the stack only by its keyed hash', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart());

    $payload = $kept->store->newest($kept->seal->stack(theStackTheRequestsAreKeptFor()))->either(
        found: static fn(SealedPayload $payload): Code => Code::of($payload->forTheStore()),
        none: static fn(): Code => Code::of('none'),
        unreadable: static fn(): Code => Code::of('unreadable'),
    )->shown();

    expect($payload)->toStartWith('sealed-by-')
        ->and($payload)->not->toContain('Dune')
        ->and($kept->store->newest(SealedStack::of(theStackTheRequestsAreKeptFor()->stored()))->holdsARow())->toBeFalse();
});

it('keeps the newest reading of each stack, and each stack\'s apart', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ + 30));
    $kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingOfNothing());
    $kept->clock->moveTo(Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ + 60));
    $kept->keeping->keep(theOtherStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart());

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('as of 30, 30 ago: nothing asked')
        ->and(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theOtherStackTheRequestsAreKeptFor())))->toStartWith('as of 60, 0 ago: #1 Mira for Dune');
});

it('opens on nothing where nothing was kept', function (): void {
    expect(whatTheRequestsScreenOpensOn(aPhoneKeepingRequests()->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('nothing');
});

it('keeps nothing where nothing can be sealed, or where the store will not keep it', function (): void {
    foreach ([
        'no secure storage' => new WhatIsKeptOfRequests(ASealInMemory::withNoSecureStorage(), ReadingsInMemory::empty(), FrozenClock::at(Instant::atEpochSeconds(0))),
        'a store that will not answer' => new WhatIsKeptOfRequests(ASealInMemory::working(), ReadingsInMemory::unreachable(), FrozenClock::at(Instant::atEpochSeconds(0))),
    ] as $which => $kept) {
        expect(whetherTheRequestsWereKept($kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart())))->toBe('not kept', $which)
            ->and($kept->store->forgetEverything()->howMany())->toBe(0, $which);
    }
});

it('keeps nothing where a household\'s words are not text it can write', function (): void {
    $kept = aPhoneKeepingRequests();
    $garbled = Requested::of(Wanted::of(1, "\xB1\x31", 'Dune', Size::unknown(), HowARequestStands::said(Waiting::ForApproval)));

    expect(whetherTheRequestsWereKept($kept->keeping->keep(theStackTheRequestsAreKeptFor(), $garbled)))->toBe('not kept')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading a later build wrote, and opens on nothing', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheRequestsAreKeptFor()));

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that does not open, and opens on nothing', function (): void {
    // Sealed by another phone's seal, which is what a payload sealed under a
    // key this one does not hold looks like from here.
    $kept = aPhoneKeepingRequests();
    $elsewhere = ASealInMemory::working();
    $kept->seal->standing();

    $elsewhere->seal(Unsealed::of('{}'))->either(
        sealed: static fn(SealedPayload $payload): Noted => $kept->store->keep($kept->seal->stack(theStackTheRequestsAreKeptFor()), $payload, Shape::One, Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ)),
        refused: static fn(): Noted => Noted::notKept(),
    );

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
});

it('lets go of a kept reading that opens to something that is not a reading of what was asked', function (string $written): void {
    $kept = aPhoneKeepingRequests()->holdsSealed($written, theStackTheRequestsAreKeptFor(), Instant::atEpochSeconds(WHEN_THE_REQUESTS_WERE_READ));

    expect(whatTheRequestsScreenOpensOn($kept->keeping->lastKept(theStackTheRequestsAreKeptFor())))->toBe('nothing')
        ->and($kept->store->forgetEverything()->howMany())->toBe(0);
})->with([
    'not written as fields at all' => ['not a reading'],
    'a word rather than fields' => ['"requests"'],
    'no requests field' => ['{}'],
    'requests that are not a list' => ['{"requests":"none"}'],
    'a request that is not fields' => ['{"requests":["Dune"]}'],
    'a request with no number' => ['{"requests":[{"by":"Mira","for":"Dune","size":{},"standing":["getting"],"refused":[]}]}'],
    'a number that is not a number' => ['{"requests":[{"number":"one","by":"Mira","for":"Dune","size":{},"standing":["getting"],"refused":[]}]}'],
    'nobody behind it' => ['{"requests":[{"number":1,"by":" ","for":"Dune","size":{},"standing":["getting"],"refused":[]}]}'],
    'asked for nothing' => ['{"requests":[{"number":1,"by":"Mira","for":" ","size":{},"standing":["getting"],"refused":[]}]}'],
    'who asked that is not text' => ['{"requests":[{"number":1,"by":7,"for":"Dune","size":{},"standing":["getting"],"refused":[]}]}'],
    'a size that is not fields' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":"big","standing":["getting"],"refused":[]}]}'],
    'a measured size that is not a number' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{"measured":"4 GB"},"standing":["getting"],"refused":[]}]}'],
    'a guessed size that is not a number' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{"guessed":"4 GB"},"standing":["getting"],"refused":[]}]}'],
    'a standing this build does not know' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":["lost"],"refused":[]}]}'],
    'a standing that is not a word' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":[1],"refused":[]}]}'],
    'no standing' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"refused":[]}]}'],
    'a refusal that is not fields' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":["declined"],"refused":["no"]}]}'],
    'a refusal for no reason' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":["declined"],"refused":[{"reason":" ","at":[]}]}]}'],
    'a refusal at a moment that is not text' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":["declined"],"refused":[{"reason":"No","at":[7]}]}]}'],
    'no refused field' => ['{"requests":[{"number":1,"by":"Mira","for":"Dune","size":{},"standing":["getting"]}]}'],
]);

it('lets go of the reading kept for one stack, and of no other\'s', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->keeping->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingOfNothing());
    $kept->keeping->keep(theOtherStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingOfNothing());

    expect($kept->keeping->keepsAnythingOf(theStackTheRequestsAreKeptFor()))->toBeTrue()
        ->and($kept->keeping->forgetTheStack(theStackTheRequestsAreKeptFor())->howMany())->toBe(1)
        ->and($kept->keeping->keepsAnythingOf(theStackTheRequestsAreKeptFor()))->toBeFalse()
        ->and($kept->keeping->keepsAnythingOf(theOtherStackTheRequestsAreKeptFor()))->toBeTrue();
});

it('finds nothing, lets go of nothing, and says it may keep something where the seal\'s keys cannot be read', function (): void {
    $store = ReadingsInMemory::empty();
    new KeepingWhatWasAsked(ASealInMemory::working(), $store, FrozenClock::at(Instant::atEpochSeconds(0)))->keep(theStackTheRequestsAreKeptFor(), WhatIsKeptOfRequests::aReadingWithEveryPart());

    $locked = new KeepingWhatWasAsked(ASealInMemory::thatWillNotOpen(), $store, FrozenClock::at(Instant::atEpochSeconds(0)));

    expect(whatTheRequestsScreenOpensOn($locked->lastKept(theStackTheRequestsAreKeptFor())))->toBe('nothing')
        ->and($locked->keepsAnythingOf(theStackTheRequestsAreKeptFor()))->toBeTrue()
        ->and($store->forgetEverything()->howMany())->toBe(1);
});

it('says it keeps a reading of a stack it cannot read until that reading is let go of', function (): void {
    $kept = aPhoneKeepingRequests();
    $kept->store->holdsOneALaterBuildWrote($kept->seal->stack(theStackTheRequestsAreKeptFor()));

    expect($kept->keeping->keepsAnythingOf(theStackTheRequestsAreKeptFor()))->toBeTrue();
});
