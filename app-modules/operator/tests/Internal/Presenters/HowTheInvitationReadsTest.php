<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\Presenters;

use function expect;
use function it;

use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasSwitchedOff;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Operator\Internal\Presenters\HowTheInvitationReads;
use Modules\Operator\Internal\TheCodesDrawn;
use Tests\Support\Fakes\ACodeOfWhatItWasGiven;

/** An invitation for anna the stack carried out, with an address to hand over. */
function annasInvitationCarriedOut(): AnInvitation
{
    return AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
        WhoWasSwitchedOff::of(),
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
    $address = annasInvitationCarriedOut()->toHand()->address();
    $read = new HowTheInvitationReads()->answered(annasInvitationCarriedOut(), TheCodesDrawn::of(ACodeOfWhatItWasGiven::working(), $address, $address->declining()));

    expect($read->invitation?->toHand->code)->toBe([[true, false, true], [false, true, false], [true, false, true]])
        ->and($read->invitation?->toHand->declines)->toBe('')
        ->and($read->invitation?->toHand->declineCode)->toBe([])
        ->and($read->notAskable)->toBe('')
        ->and($read->askedFor)->toBe('anna');
});

it('hands over the address that turns it down, with a code of its own', function (): void {
    $address = AnAddressToHand::declinable('http://loft.local:8096', '', 'http://loft.local:5056/decline/abc');
    $invitation = AnInvitation::carriedOut(
        AnInvitationToHand::to('anna', $address, 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::Made,
        WhoWasTakenBack::of(),
        WhoWasSwitchedOff::of(),
    );
    $encoding = ACodeOfWhatItWasGiven::working();

    $read = new HowTheInvitationReads()->answered($invitation, TheCodesDrawn::of($encoding, $address, $address->declining()));

    expect($read->invitation?->toHand->declines)->toBe('http://loft.local:5056/decline/abc')
        ->and($read->invitation?->toHand->declineCode)->not->toBe([])
        ->and($encoding->carried())->toBe(['http://loft.local:8096', 'http://loft.local:5056/decline/abc']);
});

it('names what went on the way past as what would, on a rehearsal, and as what did once carried out', function (): void {
    $rehearsed = new HowTheInvitationReads()->answered(AnInvitation::rehearsed(
        AnInvitationToHand::to('anna', AnAddressToHand::at('http://loft.local:8096', ''), 72),
        WhereTheInvitationStands::Made,
        WhetherTheyCanAsk::NotTried,
        WhoWasTakenBack::of(),
        WhoWasSwitchedOff::of(),
    ), TheCodesDrawn::of(ACodeOfWhatItWasGiven::working()));
    $carriedOut = new HowTheInvitationReads()->answered(annasInvitationCarriedOut(), TheCodesDrawn::of(ACodeOfWhatItWasGiven::working()));

    expect([$rehearsed->invitation?->withdrawnSaid, $rehearsed->invitation?->suspendedSaid])
        ->toBe(['stacks.invitation.would_withdraw', 'stacks.invitation.would_switch_off'])
        ->and([$carriedOut->invitation?->withdrawnSaid, $carriedOut->invitation?->suspendedSaid])
        ->toBe(['stacks.invitation.withdrew', 'stacks.invitation.switched_off']);
});
