<?php

declare(strict_types=1);

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Standings;
use Modules\Vault\Api\PlatformStandings;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\StandingsInMemory;

// The Standings contract, run against the adapter and against the fake.
//
// G2's shape, and the promise here is narrower than it looks. What both must
// agree on is not *where* a word is kept but *what kind of value comes back*:
// a retained reading, never a live one, whatever the store underneath is. That
// is what lets the list show a remembered word at all: nothing that comes out
// of this port can pass as one heard just now.
//
// The adapter is driven against a hand-written stand-in for the platform's own
// store, as every adapter here is: there is no Keychain behind a PHP process on
// a laptop, and without one the adapter is a file nothing executes.
//
// What is not asserted is survival across a launch. The platform's store
// survives one and the fake does not, so that belongs to the adapter's own
// tests — a contract asserting it would either fail on the fake or be weakened
// to pass, and a weakened contract is how a fake drifts.

/** Named for this file: the root suites share one namespace (G10). */
function aStackWhoseWordIsKept(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** Which arm answered, as a word. Named for this file, for the same reason (G10). */
function howTheWordWentDown(Noted $noted): string
{
    return $noted->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down-at-%d', $at->epochSeconds())),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

/** The word a stack's one line last said, and when, flattened to one string. */
function whatIsHeldFor(Standings $standings, StackId $stack): string
{
    return $standings->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(): Code => Code::of('live-which-cannot-happen'),
            retained: static fn(object $standing, Instant $at): Code => Code::of(sprintf(
                '%s|%d',
                $standing instanceof HowItStands ? $standing->value : 'not-a-word',
                $at->epochSeconds(),
            )),
        ),
    )->shown();
}

/** @return array<string, array{Standings}> */
dataset('every standings implementation', [
    'the platform store' => [fn(): Standings => new PlatformStandings(APlatformStore::working())],
    'the fake' => [fn(): Standings => StandingsInMemory::working()],
]);

it('holds nothing for a stack whose one line has never been heard', function (Standings $standings): void {
    // An ordinary case: pairing and opening a stack are separate screens, and
    // an operator can leave between them.
    expect(whatIsHeldFor($standings, aStackWhoseWordIsKept()))->toBe('nothing-held');
})->with('every standings implementation');

it('gives back the word it was asked to keep, with when it was heard', function (Standings $standings): void {
    $stack = aStackWhoseWordIsKept();

    expect(howTheWordWentDown(
        $standings->remember($stack, HowItStands::Degraded, Instant::atEpochSeconds(1_770_000_000)),
    ))->toBe('down-at-1770000000');

    expect(whatIsHeldFor($standings, $stack))->toBe('degraded|1770000000');
})->with('every standings implementation');

it('answers a retained reading, never a live one', function (Standings $standings): void {
    // The clause the list rests on. Everything here came out of a store rather
    // than off a stack, so it carries an age by construction — and
    // `whatIsHeldFor` renders the live arm as a word that must never appear.
    $stack = aStackWhoseWordIsKept();
    $standings->remember($stack, HowItStands::Healthy, Instant::atEpochSeconds(1_770_000_000));

    expect(whatIsHeldFor($standings, $stack))->not->toContain('live-which-cannot-happen');
})->with('every standings implementation');

it('nothing it answers may stand as the confirmation of an action', function (Standings $standings): void {
    // The other half of the same clause, asserted on the value rather than on
    // the word: a remembered *healthy* shown after a restart that failed is the
    // app lying at the one moment the operator was watching.
    $stack = aStackWhoseWordIsKept();
    $standings->remember($stack, HowItStands::Healthy, Instant::atEpochSeconds(1_770_000_000));

    $mayConfirm = $standings->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => Code::of(
            $reading->mayConfirmAnAction() ? 'may-confirm' : 'may-not-confirm',
        ),
    )->shown();

    expect($mayConfirm)->toBe('may-not-confirm');
})->with('every standings implementation');

it('keeps one stack apart from another', function (Standings $standings): void {
    $one = aStackWhoseWordIsKept('a');
    $other = aStackWhoseWordIsKept('b');

    $standings->remember($one, HowItStands::Broken, Instant::atEpochSeconds(1_770_000_000));
    $standings->remember($other, HowItStands::Healthy, Instant::atEpochSeconds(1_770_000_001));

    expect(whatIsHeldFor($standings, $one))->toBe('broken|1770000000')
        ->and(whatIsHeldFor($standings, $other))->toBe('healthy|1770000001');
})->with('every standings implementation');

it('replaces what it held for a stack rather than keeping both', function (Standings $standings): void {
    // The *last* word, not a history. Keeping two would be this app deciding
    // which to open on, which is a decision nobody asked it to make and the
    // wrong one exactly when a machine has just been fixed.
    $stack = aStackWhoseWordIsKept();

    $standings->remember($stack, HowItStands::Broken, Instant::atEpochSeconds(1_770_000_000));
    $standings->remember($stack, HowItStands::Healthy, Instant::atEpochSeconds(1_770_000_060));

    expect(whatIsHeldFor($standings, $stack))->toBe('healthy|1770000060');
})->with('every standings implementation');
