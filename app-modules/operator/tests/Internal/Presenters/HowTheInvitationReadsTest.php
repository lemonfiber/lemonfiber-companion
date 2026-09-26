<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Operator\Internal\Presenters\HowTheInvitationReads;

/** An invitation for anna the stack carried out, with an address to hand over. */
function annasInvitationCarriedOut(): AnInvitation
{
    return AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
    );
}

it('names nobody and says nothing is wrong before anything is asked', function (): void {
    $read = new HowTheInvitationReads()->notAsked();

    expect([$read->askedFor, $read->notAskable, $read->invitation])->toBe(['', '', null]);
});

it('names nobody where what was typed could not be asked, since nothing was', function (): void {
    $read = new HowTheInvitationReads()->notAskable('stacks.invitation.needs_a_name');

    expect([$read->askedFor, $read->notAskable])->toBe(['', 'stacks.invitation.needs_a_name']);
});

it('draws the code square for square, dark where the code is dark', function (): void {
    $read = new HowTheInvitationReads()->answered(annasInvitationCarriedOut(), AScannableCode::drawn('10', '01'));

    expect($read->invitation?->toHand->code)->toBe([[true, false], [false, true]])
        ->and($read->notAskable)->toBe('')
        ->and($read->askedFor)->toBe('anna');
});
