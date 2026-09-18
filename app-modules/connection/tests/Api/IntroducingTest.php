<?php

declare(strict_types=1);

namespace Modules\Connection\Tests\Api;

use function expect;
use function it;
use function json_encode;

use Modules\Connection\Api\FingerprintWasConfirmed;
use Modules\Connection\Api\Introducing;
use Modules\Connection\Api\PairingWasNotConfirmed;
use Modules\Kernel\Api\AtAGlance;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HowItWasRead;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Pairing;
use Modules\Kernel\Api\StackName;

use function str_repeat;

use Tests\Support\Fakes\FrozenClock;
use Tests\Support\Fakes\SequencedEntropy;

/** Material read by the route named, carrying the digest named. */
function material(HowItWasRead $how, string $character = 'a'): Pairing
{
    return Pairing::read(
        (string) json_encode([
            'address' => 'https://192.168.1.42',
            'fingerprint' => str_repeat($character, Fingerprint::CHARACTERS),
            'expires' => 2_000,
        ]),
        $how,
        FrozenClock::at(Instant::atEpochSeconds(1_000)),
    );
}

/** The operator's answer about the digest named. */
function confirmationOf(string $character): FingerprintWasConfirmed
{
    return FingerprintWasConfirmed::byTheOperator(
        AtAGlance::of(Fingerprint::of(str_repeat($character, Fingerprint::CHARACTERS))),
    );
}

/** The step, with a nonce nobody has to guess at. */
function introductions(): Introducing
{
    return new Introducing(SequencedEntropy::counting());
}

it('carries the address and the certificate across unchanged', function (): void {
    // The fingerprint comes from the material and never from the
    // network. Nothing in this step reads anything.
    $said = material(HowItWasRead::Scanned);

    $stack = introductions()->stack($said, StackName::of('The loft'));

    expect($stack->at()->is($said->at()))->toBeTrue()
        ->and($stack->presents()->is($said->presenting()))->toBeTrue()
        ->and($stack->name()->shown())->toBe('The loft');
});

it('gives two stacks paired from the same material two identities', function (): void {
    // N1-R11's last clause depends on this: an identity derived from the
    // address or the digest would make one machine out of two, and a reading
    // from either would be attributed to whichever row won.
    $said = material(HowItWasRead::Scanned);
    $introducing = introductions();

    $first = $introducing->stack($said, StackName::of('The loft'));
    $second = $introducing->stack($said, StackName::of('The shed'));

    expect($first->id()->is($second->id()))->toBeFalse();
});

it('refuses to pair typed material on the scanned road', function (): void {
    // Typed entry has no software comparison in it, so this road has
    // nothing to go on and says so rather than assuming.
    expect(static fn(): mixed => introductions()->stack(material(HowItWasRead::Typed), StackName::of('The loft')))
        ->toThrow(PairingWasNotConfirmed::class);
});

it('pairs typed material the operator has confirmed', function (): void {
    $said = material(HowItWasRead::Typed);

    $stack = introductions()->confirmed($said, StackName::of('The loft'), confirmationOf('a'));

    expect($stack->presents()->is($said->presenting()))->toBeTrue();
});

it('refuses a confirmation the operator gave about another certificate', function (): void {
    // The screen that re-parses after an edit and still holds the answer given
    // about what it read a moment ago. Without this it would pair the new
    // material on the strength of the old confirmation.
    expect(static fn(): mixed => introductions()->confirmed(
        material(HowItWasRead::Typed, 'a'),
        StackName::of('The loft'),
        confirmationOf('b'),
    ))->toThrow(PairingWasNotConfirmed::class);
});

it('accepts a confirmation about scanned material as well', function (): void {
    // A surface that both scanned and showed the fingerprint has done more than
    // N1-R50 asks rather than less.
    $said = material(HowItWasRead::Scanned);

    expect(introductions()->confirmed($said, StackName::of('The loft'), confirmationOf('a'))->at()->is($said->at()))
        ->toBeTrue();
});
