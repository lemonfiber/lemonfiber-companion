<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Launch;
use Modules\Kernel\Api\Nonce;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;
use ReflectionMethod;

/**
 * What each arm answers, as a word, so a test can say which one ran.
 *
 * Named for this file rather than `fold`, because a module's test files share
 * one namespace and two `fold`s are a fatal the moment both load (G10).
 */
const A_LAUNCHED_STACK = 'f60718293a4b5c6d';

function whichArm(Launch $launch): string
{
    return $launch->either(
        locked: static fn(): Code => Code::of('locked'),
        unpaired: static fn(): Code => Code::of('unpaired'),
        blocked: static fn(Obstacle $obstacle): Code => Code::of($obstacle->value),
        ready: static fn(StackId $stack): Code => Code::of($stack->stored()),
    )->shown();
}

it('N1-R37 — a locked launch is not a launch that could not reach anything', function (): void {
    // The distinction this type exists for. Locked is not an obstacle: the app
    // has not tried and must not. Given a remedy shaped like the others, an
    // operator would be told to check their router when what they need to do is
    // look at their phone.
    expect(whichArm(Launch::locked()))->toBe('locked');
});

it('N1-R37 — no network, no answer and a refused credential stay three things', function (): void {
    expect(whichArm(Launch::blockedBy(Obstacle::DeviceHasNoNetwork)))->toBe('no_network');
    expect(whichArm(Launch::blockedBy(Obstacle::StackDidNotAnswer)))->toBe('no_answer');
    expect(whichArm(Launch::blockedBy(Obstacle::CredentialWasRefused)))->toBe('credential_refused');
});

it('N1-R35 — a first run is not a failure to reach', function (): void {
    // Nothing is wrong. Reporting it as an obstacle would be the app describing
    // its own first run as a fault, and the screen `N1-R35` asks for offers
    // pairing rather than a remedy.
    expect(whichArm(Launch::unpaired()))->toBe('unpaired');
});

it('N1-R36 — a paired stack that was reached names the stack it reached', function (): void {
    $stack = StackId::of(Nonce::of(A_LAUNCHED_STACK));

    expect(whichArm(Launch::ready($stack)))->toBe(A_LAUNCHED_STACK);
});

it('N1-R37 — every arm is required, so two cases cannot quietly become one', function (): void {
    // Not a test of behaviour but of the signature, and it is the one that
    // keeps the other four honest: an optional arm is a default, and a default
    // is where two of these silently share an answer.
    $signature = new ReflectionMethod(Launch::class, 'either');

    expect($signature->getNumberOfParameters())->toBe(4);
    expect($signature->getNumberOfRequiredParameters())->toBe(4);
});
