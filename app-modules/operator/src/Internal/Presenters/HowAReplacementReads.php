<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Design\View\Tone;
use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\HowPlayingWasRead;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Operator\Internal\ViewModels\AHolderAsShown;
use Modules\Operator\Internal\ViewModels\AReplacementAsShown;
use Modules\Operator\Internal\ViewModels\TheReplacementTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\WhoIsWatchingAsShown;

/**
 * Where replacing the internal certificate has got to, and who is watching while it is read, as the template draws them.
 */
final readonly class HowAReplacementReads
{
    /** Nothing has been asked yet. */
    public function notAsked(): TheReplacementTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::notAsked());
    }

    /** This phone holds no session for the stack. */
    public function signedOut(): TheReplacementTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::signedOut());
    }

    /** The stack is still at it. */
    public function running(): TheReplacementTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::running());
    }

    /** The stack has no outcome for it any more. */
    public function ended(): TheReplacementTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::ended());
    }

    /** The stack refused it, in its own words. */
    public function refused(ARefusalInItsWords $why): TheReplacementTurnedOutToBe
    {
        return new TheReplacementTurnedOutToBe(stands: WhereTheWorkStands::answered(), refused: new HowARefusalReads()->inItsWords($why), replacement: null);
    }

    /** What stood in the way of asking. */
    public function met(Obstacle $why): TheReplacementTurnedOutToBe
    {
        return $this->without(WhereTheWorkStands::met($why));
    }

    /** What the stack read of replacing it, or what replacing it came to, with the certificate in force after. */
    public function answered(WhatAReplacementCameTo $came): TheReplacementTurnedOutToBe
    {
        $replacement = $came->replacement();

        return new TheReplacementTurnedOutToBe(
            stands: WhereTheWorkStands::answered(),
            refused: null,
            replacement: new AReplacementAsShown(
                reading: $replacement->isAReading(),
                landed: $replacement->landed(),
                settledSaid: $replacement->settledAt()->saidOnTheScreen(),
                said: $replacement->said(),
                holders: $this->holders($replacement),
                inForce: $came->held()->theInternalCertificateInForce()->atAGlance(),
                tone: $this->tone($replacement)->value,
            ),
        );
    }

    /** Who is watching on the media server now, or that it could not be read. */
    public function watching(HowPlayingWasRead $playing): WhoIsWatchingAsShown
    {
        return $playing->either(
            said: static function (WhatTheHouseholdIsPlaying $playing): WhoIsWatchingAsShown {
                $sessions = [];

                foreach ($playing as $session) {
                    $sessions[] = ['member' => $session->member(), 'device' => $session->device()];
                }

                return new WhoIsWatchingAsShown(read: $playing->wasRead(), sessions: $sessions);
            },
            refused: static fn(): WhoIsWatchingAsShown => new WhoIsWatchingAsShown(read: false, sessions: []),
        );
    }

    /**
     * Each holder, and how far the replacement reached it.
     *
     * @return list<AHolderAsShown>
     */
    private function holders(ACertificateReplacement $replacement): array
    {
        $shown = [];

        foreach ($replacement->holders() as $holder) {
            $shown[] = new AHolderAsShown(holder: $holder->holder(), reachSaid: $holder->reach()->saidOnTheScreen(), detail: $holder->detail());
        }

        return $shown;
    }

    /** A reading wants looking at, a landed replacement is fine, and anything else is not known to have landed. */
    private function tone(ACertificateReplacement $replacement): Tone
    {
        return match (true) {
            $replacement->isAReading() => Tone::Attention,
            $replacement->landed() => Tone::Fine,
            default => Tone::Unknown,
        };
    }

    private function without(WhereTheWorkStands $stands): TheReplacementTurnedOutToBe
    {
        return new TheReplacementTurnedOutToBe(stands: $stands, refused: null, replacement: null);
    }
}
