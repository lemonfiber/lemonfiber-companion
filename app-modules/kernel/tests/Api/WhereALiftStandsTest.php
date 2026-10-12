<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\LineSaysNothing;
use Modules\Kernel\Api\WhereALiftStands;

use function sprintf;

use Tests\Support\TheWordCarriedOut;

/** Which arm a lift took, and the seconds it carried there. */
function whichArmTheLiftTook(WhereALiftStands $lift): string
{
    return $lift->either(
        none: static fn(): TheWordCarriedOut => new TheWordCarriedOut('none'),
        inForce: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('in force:%d', $seconds)),
        expired: static fn(int $seconds): TheWordCarriedOut => new TheWordCarriedOut(sprintf('expired:%d', $seconds)),
    )->said;
}

it('takes the arm for each standing, with the seconds and the stack\'s words', function (): void {
    expect([whichArmTheLiftTook(WhereALiftStands::none()), WhereALiftStands::none()->says()])->toBe(['none', ''])
        ->and([whichArmTheLiftTook(WhereALiftStands::inForce(600, ' Lifted until 21:10 ')), WhereALiftStands::inForce(600, ' Lifted until 21:10 ')->says()])->toBe(['in force:600', 'Lifted until 21:10'])
        ->and(whichArmTheLiftTook(WhereALiftStands::expired(0, '')))->toBe('expired:0');
});

it('refuses seconds below nothing', function (): void {
    expect(static fn(): WhereALiftStands => WhereALiftStands::inForce(-1, ''))
        ->toThrow(LineSaysNothing::class, '`respite.seconds`')
        ->and(static fn(): WhereALiftStands => WhereALiftStands::expired(-1, ''))
        ->toThrow(LineSaysNothing::class, '`respite.seconds`');
});
