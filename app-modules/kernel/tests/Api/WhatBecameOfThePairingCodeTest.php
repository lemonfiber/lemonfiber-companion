<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\APairingLine;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PairingIsNotReadable;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;

use function sprintf;
use function str_repeat;

use Tests\Support\TheWordCarriedOut;

/** Which arm an answer takes. */
function whichArmThePairingCodeTook(WhatBecameOfThePairingCode $became): string
{
    return $became->either(
        underway: static fn(Job $job): TheWordCarriedOut => new TheWordCarriedOut(sprintf('underway %s', $job->shown())),
        made: static fn(APairingCode $code): TheWordCarriedOut => new TheWordCarriedOut(sprintf('made %s', $code->compare())),
        ended: static fn(): TheWordCarriedOut => new TheWordCarriedOut('ended'),
        refused: static fn(string $because): TheWordCarriedOut => new TheWordCarriedOut(sprintf('refused %s', $because)),
        met: static fn(Obstacle $why): TheWordCarriedOut => new TheWordCarriedOut(sprintf('met %s', $why->kind()->name)),
    )->said;
}

it('takes the arm it was made on, and no other', function (): void {
    $code = APairingCode::made(APairingLine::asWritten('the line'), Fingerprint::of(str_repeat('0', Fingerprint::CHARACTERS)), 'ABCD', Instant::atEpochSeconds(1), 'https://den.local:8443', '', 'Every paired phone refuses it until it is paired again.');

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
