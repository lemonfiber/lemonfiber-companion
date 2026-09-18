<?php

declare(strict_types=1);

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Overall;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Verdicts;
use Modules\Vault\Api\PlatformVerdicts;
use Tests\Support\Fakes\APlatformStore;
use Tests\Support\Fakes\VerdictsInMemory;

// The Verdicts contract, run against the adapter and against the fake.
//
// G2's shape, and the promise here is narrower than it looks. What both must
// agree on is not *where* a verdict is kept but *what kind of value comes back*:
// a retained reading, never a live one, whatever the store underneath is. That
// is what lets the opening screen show a remembered verdict at all: nothing
// that comes out of this port can pass as one read just now.
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
function aStackWhoseVerdictIsKept(string $seed = 'a'): StackId
{
    return StackId::of(Nonce::of(str_repeat($seed, Nonce::SHORTEST)));
}

/** Which arm answered, as a word. Named for this file, for the same reason (G10). */
function howTheVerdictWentDown(Noted $noted): string
{
    return $noted->either(
        down: static fn(Instant $at): Code => Code::of(sprintf('down-at-%d', $at->epochSeconds())),
        notKept: static fn(): Code => Code::of('not-kept'),
    )->shown();
}

/** What a stack was last seen as, and when, flattened to one string. */
function whatIsHeldFor(Verdicts $verdicts, StackId $stack): string
{
    return $verdicts->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => $reading->either(
            live: static fn(): Code => Code::of('live-which-cannot-happen'),
            retained: static fn(object $overall, Instant $at): Code => Code::of(sprintf(
                '%s|%d',
                $overall instanceof Overall ? $overall->value : 'not-a-verdict',
                $at->epochSeconds(),
            )),
        ),
    )->shown();
}

/** @return array<string, array{Verdicts}> */
dataset('every verdicts implementation', [
    'the platform store' => [fn(): Verdicts => new PlatformVerdicts(APlatformStore::working())],
    'the fake' => [fn(): Verdicts => VerdictsInMemory::working()],
]);

it('N1-R28 — holds nothing for a stack that has never been asked', function (Verdicts $verdicts): void {
    // The one case a progress indicator may answer, and an ordinary one here:
    // pairing and asking are separate screens and an operator can leave between
    // them.
    expect(whatIsHeldFor($verdicts, aStackWhoseVerdictIsKept()))->toBe('nothing-held');
})->with('every verdicts implementation');

it('gives back the verdict it was asked to keep, with when it was read', function (Verdicts $verdicts): void {
    $stack = aStackWhoseVerdictIsKept();

    expect(howTheVerdictWentDown(
        $verdicts->remember($stack, Overall::Degraded, Instant::atEpochSeconds(1_770_000_000)),
    ))->toBe('down-at-1770000000');

    expect(whatIsHeldFor($verdicts, $stack))->toBe('degraded|1770000000');
})->with('every verdicts implementation');

it('N1-R9 — answers a retained reading, never a live one', function (Verdicts $verdicts): void {
    // The clause the opening screen rests on. Everything here came out of a
    // store rather than off a stack, so it carries an age by construction — and
    // `whatIsHeldFor` renders the live arm as a word that must never appear.
    $stack = aStackWhoseVerdictIsKept();
    $verdicts->remember($stack, Overall::Healthy, Instant::atEpochSeconds(1_770_000_000));

    expect(whatIsHeldFor($verdicts, $stack))->not->toContain('live-which-cannot-happen');
})->with('every verdicts implementation');

it('N1-R24 — nothing it answers may stand as the confirmation of an action', function (Verdicts $verdicts): void {
    // The other half of the same clause, asserted on the value rather than on
    // the word: a remembered *healthy* shown after a restart that failed is the
    // app lying at the one moment the operator was watching.
    $stack = aStackWhoseVerdictIsKept();
    $verdicts->remember($stack, Overall::Healthy, Instant::atEpochSeconds(1_770_000_000));

    $mayConfirm = $verdicts->lastKnownOf($stack)->either(
        waiting: static fn(): Code => Code::of('nothing-held'),
        holding: static fn(Reading $reading): Code => Code::of(
            $reading->mayConfirmAnAction() ? 'may-confirm' : 'may-not-confirm',
        ),
    )->shown();

    expect($mayConfirm)->toBe('may-not-confirm');
})->with('every verdicts implementation');

it('keeps one stack apart from another', function (Verdicts $verdicts): void {
    $one = aStackWhoseVerdictIsKept('a');
    $other = aStackWhoseVerdictIsKept('b');

    $verdicts->remember($one, Overall::Broken, Instant::atEpochSeconds(1_770_000_000));
    $verdicts->remember($other, Overall::Healthy, Instant::atEpochSeconds(1_770_000_001));

    expect(whatIsHeldFor($verdicts, $one))->toBe('broken|1770000000')
        ->and(whatIsHeldFor($verdicts, $other))->toBe('healthy|1770000001');
})->with('every verdicts implementation');

it('replaces what it held for a stack rather than keeping both', function (Verdicts $verdicts): void {
    // A verdict is the *last* word, not a history. Keeping two would be this
    // app deciding which to open on, which is a decision nobody asked it to
    // make and the wrong one exactly when a machine has just been fixed.
    $stack = aStackWhoseVerdictIsKept();

    $verdicts->remember($stack, Overall::Broken, Instant::atEpochSeconds(1_770_000_000));
    $verdicts->remember($stack, Overall::Healthy, Instant::atEpochSeconds(1_770_000_060));

    expect(whatIsHeldFor($verdicts, $stack))->toBe('healthy|1770000060');
})->with('every verdicts implementation');
