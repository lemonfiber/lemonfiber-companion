<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\AClientToHandOver;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\AScannableCode;
use Modules\Kernel\Api\ASignedInDevice;
use Modules\Kernel\Api\HowLongAgo;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheHandoffNeedsNext;
use Modules\Kernel\Api\WhereTheHandoffStands;
use Modules\Operator\Internal\ViewModels\AClientAsShown;
use Modules\Operator\Internal\ViewModels\AHandoffAsShown;
use Modules\Operator\Internal\ViewModels\ASignedInDeviceAsShown;
use Modules\Operator\Internal\ViewModels\HowTheHandoffWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;

use function sprintf;

/**
 * Where handing a device over has got to, as the fields a screen draws.
 *
 * `F2`: data in, view model out. What there is to do next is resolved here to
 * which of this app's ways of doing it the template offers, so it never reads
 * the names the stack gives it.
 */
final readonly class HowTheHandoffReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): HowTheHandoffWent
    {
        return new HowTheHandoffWent(HowTheReadingWent::itCameBack(), isWorking: false, refused: '', handoff: null);
    }

    /** This device no longer holds a session for the stack. */
    public function signedOut(): HowTheHandoffWent
    {
        return new HowTheHandoffWent(HowTheReadingWent::theSessionEnded(), isWorking: false, refused: '', handoff: null);
    }

    /** Asking, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheHandoffWent
    {
        return new HowTheHandoffWent(HowTheReadingWent::somethingStopped($why), isWorking: false, refused: '', handoff: null);
    }

    /** The stack is still working it out. */
    public function working(): HowTheHandoffWent
    {
        return new HowTheHandoffWent(HowTheReadingWent::itCameBack(), isWorking: true, refused: '', handoff: null);
    }

    /** The stack refused, and this is its reason. */
    public function refused(string $because): HowTheHandoffWent
    {
        return new HowTheHandoffWent(HowTheReadingWent::itCameBack(), isWorking: false, refused: $because, handoff: null);
    }

    /**
     * Where the stack said it stands, with the address drawn as a code where
     * there is one to hand over, and every moment counted back from `$now`.
     *
     * The squares are passed in rather than made here, because making them is
     * a port's work and a presenter asks nothing of anybody.
     */
    public function answered(AHandoff $handoff, AScannableCode $drawn, Instant $now): HowTheHandoffWent
    {
        $next = $handoff->next();
        $handsOver = $handoff->stands()->handsACodeOver() && $handoff->handed()->address()->url() !== '';
        $steps = [];
        $clients = [];
        $signedIn = [];

        foreach ($handoff->handed()->steps() as $step) {
            $steps[] = $step;
        }

        foreach ($handoff->handed()->clients() as $client) {
            $clients[] = $this->client($client);
        }

        foreach ($handoff->signedIn() as $device) {
            $signedIn[] = $this->signedIn($device, $now);
        }

        return new HowTheHandoffWent(
            went: HowTheReadingWent::itCameBack(),
            isWorking: false,
            refused: '',
            handoff: new AHandoffAsShown(
                heading: $this->heading($handoff->stands()),
                reason: $handoff->reason(),
                // Asking again is offered wherever the stack names nothing else
                // to do, so a hand-off that names nothing is never a dead end.
                asksAgain: $next === WhatTheHandoffNeedsNext::AskAgain || $next === WhatTheHandoffNeedsNext::Nothing,
                invites: $next === WhatTheHandoffNeedsNext::Invite,
                starts: $next === WhatTheHandoffNeedsNext::StartServer,
                records: $next === WhatTheHandoffNeedsNext::RecordAddress,
                handsOver: $handsOver,
                squares: $handsOver ? new HowACodeReads()->squares($drawn) : [],
                address: $handoff->handed()->address()->url(),
                caution: $handoff->handed()->address()->caution(),
                steps: $steps,
                clients: $clients,
                signedIn: $signedIn,
                given: $this->ago($handoff->given(), $now),
            ),
        );
    }

    /** What the state is called, or nothing where the stack's reason is what is said. */
    private function heading(WhereTheHandoffStands $stands): string
    {
        return match ($stands) {
            WhereTheHandoffStands::Unprovisioned => '',
            WhereTheHandoffStands::Ready, WhereTheHandoffStands::Pending, WhereTheHandoffStands::Connected, WhereTheHandoffStands::Failed
                => sprintf('stacks.handoff.stands.%s', $stands->value),
        };
    }


    /** One app, with its link where its code is one. */
    private function client(AClientToHandOver $client): AClientAsShown
    {
        return new AClientAsShown(
            device: $client->device(),
            client: $client->client(),
            openSource: $client->isOpenSource(),
            link: $client->isALink() ? $client->code() : '',
        );
    }

    /** One device signed in, with how long ago it was last heard from. */
    private function signedIn(ASignedInDevice $device, Instant $now): ASignedInDeviceAsShown
    {
        return new ASignedInDeviceAsShown($device->device(), $device->client(), $this->ago($device->lastSeen(), $now));
    }

    /** How long before `$now` a moment was, or nothing where it names none. */
    private function ago(AMomentAsWritten $moment, Instant $now): AgoAsShown
    {
        return $moment->read(
            read: static fn(Instant $at): AgoAsShown => AgoAsShown::from(HowLongAgo::since($at, $now), $at, $now),
            unreadable: AgoAsShown::unsaid(...),
        );
    }
}
