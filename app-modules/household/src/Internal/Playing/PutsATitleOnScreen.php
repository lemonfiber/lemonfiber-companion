<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Connection\Api\TheGrantForThisDevice;
use Modules\Kernel\Api\AGrant;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Fingerprint;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\Location;
use Modules\Kernel\Api\PartWays;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatPlayPlays;
use Modules\Kernel\Api\WhereItPlays;
use Modules\Kernel\Api\Whose;
use Modules\Kernel\Api\WhyPlayingDidNotStart;

/**
 * Pressing Play: the one place the player is handed anything.
 *
 * The title is asked of the core again as the member presses, so what plays
 * is the core's answer at that moment rather than what a page drew earlier:
 * a title the member may no longer watch, or one that no longer streams,
 * plays nothing. The location and the door are the ones the core states for
 * it, and the grant is the one it answered for this member on this device.
 *
 * A session the stack refused, asking for the title or for the grant, is let
 * go of here, where the refusal is in hand.
 */
final readonly class PutsATitleOnScreen
{
    use LetsGoOfARefusedSession;

    public function __construct(
        private SecureStorage $storage,
        private Watching $watching,
        private TheGrantForThisDevice $grants,
        private Playing $player,
        private WhatIsPlaying $playing,
    ) {}

    /** Play a title from its start, as whoever is signed in to the stack: itself, or the episode its own Play plays. */
    public function theTitle(Stack $stack, HoldingId $page): WhatPressingPlayCameTo
    {
        return $this->signedIn($stack, fn(Session $session, Whose $whose): WhatPressingPlayCameTo => $this->from(
            $stack,
            $session,
            $whose,
            $page,
            static fn(ATitle $title): WhatPlayPlays => $title->whatPlayPlays(),
        ));
    }

    /** Play one of a title's episodes from its start, as whoever is signed in to the stack. */
    public function theEpisode(Stack $stack, HoldingId $page, HoldingId $episode): WhatPressingPlayCameTo
    {
        return $this->signedIn($stack, fn(Session $session, Whose $whose): WhatPressingPlayCameTo => $this->from(
            $stack,
            $session,
            $whose,
            $page,
            static fn(ATitle $title): WhatPlayPlays => $title->theEpisode($episode),
        ));
    }

    /**
     * Play something the member was part-way through from where they left off,
     * as the core answers it again as Play is pressed.
     */
    public function whereTheyLeftOff(Stack $stack, HoldingId $partWay): WhatPressingPlayCameTo
    {
        return $this->signedIn($stack, fn(Session $session, Whose $whose): WhatPressingPlayCameTo => $this->watching->partWayThrough($stack, $session, $whose)->either(
            told: fn(PartWays $all): WhatPressingPlayCameTo => $this->resumed($stack, $session, $whose, $all, $partWay),
            refused: $this->lettingGoIfRefused($stack, WhatPressingPlayCameTo::met(...)),
        ));
    }

    /** How the last title played from this page stopped where it could not go on, or closed where it did not. */
    public function howItStoppedOn(HoldingId $page): PlaybackIs
    {
        return $this->playing->howItStoppedOn($page);
    }

    /** Put a playback the door refused back where it stopped, under a grant asked for again. */
    public function again(APlayback $playback, Session $session, HowFarIn $from): WhatPressingPlayCameTo
    {
        return $this->grants->afresh($playback->stack(), $session, $playback->whose())->either(
            granted: fn(AGrant $grant): WhatPressingPlayCameTo => $this->open($playback->grantedAgain(), $grant, $from),
            refused: $this->lettingGoIfRefused($playback->stack(), WhatPressingPlayCameTo::met(...)),
        );
    }

    /**
     * Under the session held for the stack; with none, nothing plays, and the screen reads that it was signed out.
     *
     * @param Closure(Session, Whose): WhatPressingPlayCameTo $press
     */
    private function signedIn(Stack $stack, Closure $press): WhatPressingPlayCameTo
    {
        return $this->storage->resume($stack->id())->either(
            held: $press,
            notHeld: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
        );
    }

    /** The one the member was part-way through, from where they left off; nothing where the core no longer lists it or it does not play. */
    private function resumed(Stack $stack, Session $session, Whose $whose, PartWays $all, HoldingId $partWay): WhatPressingPlayCameTo
    {
        foreach ($all as $one) {
            $holding = $one->holding();

            if ($holding->id()->named() === $partWay->named()) {
                return $one->plays()->either(
                    at: fn(Location $location, Fingerprint $door): WhatPressingPlayCameTo => $this->granted(
                        APlayback::of($stack, $whose, $partWay, $partWay, $holding->titled(), $location, $door),
                        $session,
                        $one->reached(),
                    ),
                    cannot: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
                    doesNotStream: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
                );
            }
        }

        return WhatPressingPlayCameTo::nothingToPlay();
    }

    /** @param Closure(ATitle): WhatPlayPlays $which */
    private function from(Stack $stack, Session $session, Whose $whose, HoldingId $page, Closure $which): WhatPressingPlayCameTo
    {
        return $this->watching->theTitle($stack, $session, $whose, $page)->either(
            told: fn(ATitle $title): WhatPressingPlayCameTo => $which($title)->either(
                one: fn(HoldingId $played, string $named, WhereItPlays $plays): WhatPressingPlayCameTo => $plays->either(
                    at: fn(Location $location, Fingerprint $door): WhatPressingPlayCameTo => $this->granted(
                        APlayback::of($stack, $whose, $page, $played, $named, $location, $door),
                        $session,
                        HowFarIn::theStart(),
                    ),
                    cannot: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
                    doesNotStream: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
                ),
                nothing: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
            ),
            absent: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
            refused: $this->lettingGoIfRefused($stack, WhatPressingPlayCameTo::met(...)),
        );
    }

    private function granted(APlayback $playback, Session $session, HowFarIn $from): WhatPressingPlayCameTo
    {
        return $this->grants->on($playback->stack(), $session, $playback->whose())->either(
            granted: fn(AGrant $grant): WhatPressingPlayCameTo => $this->open($playback, $grant, $from),
            refused: $this->lettingGoIfRefused($playback->stack(), WhatPressingPlayCameTo::met(...)),
        );
    }

    private function open(APlayback $playback, AGrant $grant, HowFarIn $from): WhatPressingPlayCameTo
    {
        return $this->player->open($playback->toPlay($grant, $from))->either(
            opened: function () use ($playback): WhatPressingPlayCameTo {
                $this->playing->began($playback);

                return WhatPressingPlayCameTo::onScreen();
            },
            refused: static fn(WhyPlayingDidNotStart $why): WhatPressingPlayCameTo => WhatPressingPlayCameTo::notStarted($why),
        );
    }
}
