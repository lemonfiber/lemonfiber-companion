<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\KindOfObstacle;
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
        unpaired: static fn(): Code => Code::of('unpaired'),
        blocked: static fn(Obstacle $obstacle): Code => Code::of($obstacle->kind()->value),
        ready: static fn(StackId $stack): Code => Code::of($stack->stored()),
    )->shown();
}

it('no network, no answer and a refused credential stay three things', function (): void {
    expect(whichArm(Launch::blockedBy(Obstacle::of(KindOfObstacle::DeviceHasNoNetwork))))->toBe('no_network');
    expect(whichArm(Launch::blockedBy(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('no_answer');
    expect(whichArm(Launch::blockedBy(Obstacle::of(KindOfObstacle::CredentialWasRefused))))->toBe('credential_refused');
});

it('a first run is not a failure to reach', function (): void {
    // Nothing is wrong. Reporting it as an obstacle would be the app describing
    // its own first run as a fault, and the first-run screen offers
    // pairing rather than a remedy.
    expect(whichArm(Launch::unpaired()))->toBe('unpaired');
});

it('a paired stack that was reached names the stack it reached', function (): void {
    $stack = StackId::of(Nonce::of(A_LAUNCHED_STACK));

    expect(whichArm(Launch::ready($stack)))->toBe(A_LAUNCHED_STACK);
});

it('every arm is required, so two cases cannot quietly become one', function (): void {
    // Not a test of behaviour but of the signature, and it is the one that
    // keeps the others honest: an optional arm is a default, and a default
    // is where two of these silently share an answer.
    $signature = new ReflectionMethod(Launch::class, 'either');

    expect($signature->getNumberOfParameters())->toBe(3);
    expect($signature->getNumberOfRequiredParameters())->toBe(3);
});
