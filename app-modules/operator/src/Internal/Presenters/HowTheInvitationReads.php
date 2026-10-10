<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Operator\Internal\TheCodesDrawn;
use Modules\Operator\Internal\ViewModels\AnInvitationAsShown;
use Modules\Operator\Internal\ViewModels\AnInvitationToHandAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheInvitationTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatWasGrantedAsShown;

/**
 * Where asking somebody in has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. One method per state, each saying only its
 * own, so a template never reads an address off a rehearsal or off somebody
 * who has already joined.
 */
final readonly class HowTheInvitationReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), '');
    }

    /** What was typed is not something to ask with, and nothing was sent. */
    public function notAskable(string $why): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), '', notAskable: $why);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(string $name): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::theSessionEnded(), $name);
    }

    /** The stack is still carrying it out. */
    public function running(string $name): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, isWorking: true);
    }

    /** The stack has no outcome for it any more, which is not the same as refusing it. */
    public function ended(string $name): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, hasEnded: true);
    }

    /** The stack refused, and this is its reason. */
    public function refused(string $because, string $name): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::itCameBack(), $name, refusal: $because);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why, string $name): TheInvitationTurnedOutToBe
    {
        return $this->without(HowTheReadingWent::somethingStopped($why), $name);
    }

    /**
     * The invitation the stack answered, with each address drawn as a code where there is one to hand over.
     *
     * The codes are passed in rather than made here, because making one is a
     * port's work and a presenter asks nothing of anybody.
     */
    public function answered(AnInvitation $invitation, TheCodesDrawn $drawn): TheInvitationTurnedOutToBe
    {
        $toHand = $invitation->toHand();
        $somethingToHand = $invitation->standing()->leavesSomethingToHandOver();
        $handsOver = ! $invitation->wasRehearsed() && $somethingToHand;
        $withdrawn = [];

        foreach ($invitation->withdrawn() as $name) {
            $withdrawn[] = $name;
        }

        $suspended = [];

        foreach ($invitation->suspended() as $name) {
            $suspended[] = $name;
        }

        return new TheInvitationTurnedOutToBe(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            hasEnded: false,
            refusal: '',
            askedFor: $toHand->name(),
            notAskable: '',
            invitation: new AnInvitationAsShown(
                rehearsed: $invitation->wasRehearsed(),
                mayBeSent: $invitation->wasRehearsed() && $somethingToHand,
                standingSaid: $invitation->standing()->saidOnTheScreen(),
                askingSaid: $invitation->linked()->saidOnTheScreen(),
                toHand: $handsOver ? $this->handedOver($toHand, $drawn) : $this->notHandedOver($toHand, $somethingToHand),
                granted: $invitation->granted(
                    these: $this->granted(...),
                    nothing: WhatWasGrantedAsShown::nothing(...),
                ),
                withdrawn: $withdrawn,
                withdrawnSaid: $invitation->wasRehearsed() ? 'stacks.invitation.would_withdraw' : 'stacks.invitation.withdrew',
                suspended: $suspended,
                suspendedSaid: $invitation->wasRehearsed() ? 'stacks.invitation.would_switch_off' : 'stacks.invitation.switched_off',
            ),
        );
    }

    /** What was written on the account, as the rows that draw it. */
    /** What is handed over, with each address and the code of it; it lapses, since anybody it is handed to has not joined. */
    private function handedOver(AnInvitationToHand $toHand, TheCodesDrawn $drawn): AnInvitationToHandAsShown
    {
        return new AnInvitationToHandAsShown(
            name: $toHand->name(),
            url: $toHand->address()->url(),
            caution: $toHand->address()->caution(),
            hours: $toHand->hours(),
            code: new HowACodeReads()->squares($drawn->for($toHand->address())),
            declines: $toHand->address()->decline(),
            declineCode: new HowACodeReads()->squares($drawn->for($toHand->address()->declining())),
            handsOver: true,
            lapses: true,
        );
    }

    /** What would be handed over, on a rehearsal or for somebody who has joined: no address to hand and no code of one. */
    private function notHandedOver(AnInvitationToHand $toHand, bool $lapses): AnInvitationToHandAsShown
    {
        return new AnInvitationToHandAsShown(
            name: $toHand->name(),
            url: $toHand->address()->url(),
            caution: $toHand->address()->caution(),
            hours: $toHand->hours(),
            code: [],
            declines: '',
            declineCode: [],
            handsOver: false,
            lapses: $lapses,
        );
    }

    private function granted(WhatWasGranted $granted): WhatWasGrantedAsShown
    {
        $libraries = [];

        foreach ($granted->libraries() as $library) {
            $libraries[] = $library;
        }

        return new WhatWasGrantedAsShown(
            wroteNothing: false,
            libraries: $libraries,
            limit: $granted->limit(),
            unratedSaid: $granted->unrated()->saidOnTheScreen(),
            requestingSaid: $granted->requesting()->saidOnTheScreen(),
            filtering: $granted->filtering(),
        );
    }

    /** A state with no invitation in it. */
    private function without(
        HowTheReadingWent $went,
        string $name,
        bool $isWorking = false,
        bool $hasEnded = false,
        string $refusal = '',
        string $notAskable = '',
    ): TheInvitationTurnedOutToBe {
        return new TheInvitationTurnedOutToBe(
            went: $went,
            isWorking: $isWorking,
            hasEnded: $hasEnded,
            refusal: $refusal,
            askedFor: $name,
            notAskable: $notAskable,
            invitation: null,
        );
    }
}
