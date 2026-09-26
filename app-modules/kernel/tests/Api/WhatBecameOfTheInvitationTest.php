<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\InvitationSaysNothing;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatBecameOfTheInvitation;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;

use function sprintf;

/** One line carried out of an arm. */
final readonly class WhichArmTheInvitationTook
{
    public function __construct(public string $said) {}
}

/** Which arm a reading took, and what it carried. */
function whichArmItTook(WhatBecameOfTheInvitation $became): string
{
    return $became->either(
        underway: static fn(Job $job): WhichArmTheInvitationTook => new WhichArmTheInvitationTook(sprintf('underway:%s', $job->shown())),
        answered: static fn(AnInvitation $invitation): WhichArmTheInvitationTook => new WhichArmTheInvitationTook(sprintf('answered:%s', $invitation->toHand()->name())),
        ended: static fn(): WhichArmTheInvitationTook => new WhichArmTheInvitationTook('ended'),
        refused: static fn(string $because): WhichArmTheInvitationTook => new WhichArmTheInvitationTook(sprintf('refused:%s', $because)),
        met: static fn(Obstacle $why): WhichArmTheInvitationTook => new WhichArmTheInvitationTook(sprintf('met:%s', $why->value)),
    )->said;
}

it('takes the arm it was made on, and carries what it was made with', function (): void {
    $invitation = AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
    );

    expect(whichArmItTook(WhatBecameOfTheInvitation::underway(Job::named('j-1'))))->toBe('underway:j-1')
        ->and(whichArmItTook(WhatBecameOfTheInvitation::answered($invitation)))->toBe('answered:anna')
        ->and(whichArmItTook(WhatBecameOfTheInvitation::ended()))->toBe('ended')
        ->and(whichArmItTook(WhatBecameOfTheInvitation::refused('There is no library called Cartoons')))->toBe('refused:There is no library called Cartoons')
        ->and(whichArmItTook(WhatBecameOfTheInvitation::met(Obstacle::StackDidNotAnswer)))->toBe(sprintf('met:%s', Obstacle::StackDidNotAnswer->value));
});

it('refuses a refusal with nothing said', function (): void {
    expect(fn(): WhatBecameOfTheInvitation => WhatBecameOfTheInvitation::refused(' '))->toThrow(InvitationSaysNothing::class, 'its `reason` blank');
});
