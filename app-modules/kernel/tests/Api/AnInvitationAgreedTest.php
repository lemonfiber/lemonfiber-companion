<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationAgreed;
use Modules\Kernel\Api\AnInvitationAskedFor;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\InvitationWasNotOffered;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;

/** A rehearsal for this name, finding what is given. */
function aRehearsalFor(string $name, WhereTheInvitationStands $standing): AnInvitation
{
    return AnInvitation::rehearsed(
        AnInvitationToHand::to($name, AnAddressToHand::at('http://loft.local:8096', ''), 72),
        $standing,
        WhetherTheyCanAsk::NotTried,
        WhoWasTakenBack::of(),
    );
}

it('carries what the rehearsal was asked with, for every standing that leaves something to hand over, under whatever name it answered', function (): void {
    $asked = AnInvitationAskedFor::for('anna', TheLibraries::of('Films'));

    foreach ([WhereTheInvitationStands::Made, WhereTheInvitationStands::Waiting, WhereTheInvitationStands::Reset] as $standing) {
        expect(AnInvitationAgreed::after($asked, aRehearsalFor('anna', $standing))->asked())->toBe($asked, $standing->value);
    }

    expect(AnInvitationAgreed::after($asked, aRehearsalFor('Anna', WhereTheInvitationStands::Waiting))->asked())->toBe($asked);
});

it('refuses an answer that was carried out, and somebody already in', function (): void {
    $asked = AnInvitationAskedFor::for('anna', TheLibraries::of());
    $carriedOut = AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
    );

    expect(fn(): AnInvitationAgreed => AnInvitationAgreed::after($asked, $carriedOut))->toThrow(InvitationWasNotOffered::class, 'already been carried out')
        ->and(fn(): AnInvitationAgreed => AnInvitationAgreed::after($asked, aRehearsalFor('anna', WhereTheInvitationStands::Joined)))->toThrow(InvitationWasNotOffered::class, 'already in the household');
});
