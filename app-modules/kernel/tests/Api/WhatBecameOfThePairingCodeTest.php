<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PairingIsNotReadable;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;

use function sprintf;

/** One word carried out of an `either()` arm. */
final readonly class WhatThePairingCodeCameTo
{
    public function __construct(public string $said) {}
}

/** Which arm an answer takes. */
function whichArmThePairingCodeTook(WhatBecameOfThePairingCode $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhatThePairingCodeCameTo => new WhatThePairingCodeCameTo(sprintf('underway %s', $job->shown())),
        made: static fn(APairingCode $code): WhatThePairingCodeCameTo => new WhatThePairingCodeCameTo(sprintf('made %s', $code->compare())),
        ended: static fn(): WhatThePairingCodeCameTo => new WhatThePairingCodeCameTo('ended'),
        refused: static fn(string $because): WhatThePairingCodeCameTo => new WhatThePairingCodeCameTo(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): WhatThePairingCodeCameTo => new WhatThePairingCodeCameTo(sprintf('met %s', $why->kind()->name)),
    )->said;
}

it('takes the arm it was made on, and no other', function (): void {
    $code = APairingCode::made(APairingLine::asWritten('the line'), 'ABCD', Instant::atEpochSeconds(1), 'https://den.local:8443', '');

    expect(whichArmThePairingCodeTook(WhatBecameOfThePairingCode::underway(Job::named('pair-1'))))->toBe('underway pair-1')
        ->and(whichArmThePairingCodeTook(WhatBecameOfThePairingCode::made($code)))->toBe('made ABCD')
        ->and(whichArmThePairingCodeTook(WhatBecameOfThePairingCode::ended()))->toBe('ended')
        ->and(whichArmThePairingCodeTook(WhatBecameOfThePairingCode::refused('not served')))->toBe('refused not served')
        ->and(whichArmThePairingCodeTook(WhatBecameOfThePairingCode::met(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe('met StackDidNotAnswer');
});

it('refuses a refusal with nothing in it', function (): void {
    expect(static fn(): WhatBecameOfThePairingCode => WhatBecameOfThePairingCode::refused('  '))
        ->toThrow(PairingIsNotReadable::class, 'pairing code whose `reason` is blank');
});
