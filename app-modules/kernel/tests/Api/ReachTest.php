<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Reach;

use function sprintf;

/** What a reach that worked hands back, as a named type rather than a stub. */
function opened(): Code
{
    return Code::of('a-session');
}

/**
 * Both branches, each naming itself and what it was handed.
 *
 * Written once rather than per test so that every case reads the same fold,
 * and so that neither arm can quietly stop using what it was given — an arm
 * that ignores its argument would still pass a test that only asked which side
 * ran.
 */
function foldReach(Reach $reach): Code
{
    return $reach->either(
        made: static fn(object $reached): Code => Code::of(sprintf('made:%s', $reached::class)),
        blocked: static fn(Obstacle $obstacle): Code => Code::of(sprintf('blocked:%s', $obstacle->value)),
    );
}

it('takes the made branch when the stack answered', function (): void {
    expect(foldReach(Reach::made(opened()))->shown())->toBe(sprintf('made:%s', Code::class));
});

it('hands the blocked arm the obstacle itself, not the fact of one', function (): void {
    // The whole point: there is no moment at which "it did not work" exists as
    // a value on its own, so there is nothing for a caller to render one
    // sentence from — which is the collapse N1-R10 forbids.
    expect(foldReach(Reach::blockedBy(Obstacle::CredentialWasRefused))->shown())
        ->toBe('blocked:credential_refused');

    expect(foldReach(Reach::blockedBy(Obstacle::DeviceHasNoNetwork))->shown())
        ->toBe('blocked:no_network');
});

it('hands the made arm what came back', function (): void {
    $reached = opened();

    $answer = Reach::made($reached)->either(
        made: static fn(object $given): object => $given,
        blocked: static fn(Obstacle $obstacle): Code => Code::of($obstacle->value),
    );

    // Identity rather than equality: an `either` that rebuilt the value would
    // satisfy a comparison and lose whatever the caller was holding.
    expect($answer)->toBe($reached);
});
