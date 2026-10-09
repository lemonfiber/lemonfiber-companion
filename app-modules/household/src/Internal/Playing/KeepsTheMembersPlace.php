<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use function in_array;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\KeepingThePlace;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\ThePlace;
use Modules\Kernel\Api\Whose;

/**
 * What the player moving means: where the member is, told to the core, and
 * what to do where playback stopped.
 *
 * The device says the player moved every ten seconds while it plays and at
 * once on a pause, a seek, the end or a close, so each move is told as it is
 * heard. Nothing is queued: a place the core could not be told is dropped,
 * and the next says the same, later.
 *
 * Told under the session the member holds on that stack now, and only while
 * it is still theirs: somebody else signing in there while it played is not
 * told what the last member watched.
 *
 * **Where playback stopped and cannot go on, the player is closed and the
 * title's page says why**, rather than leaving a member in front of a player
 * that will not play. A grant the door refused is asked for again once, and
 * the title put back where it stopped; refused again, it stops.
 */
final readonly class KeepsTheMembersPlace
{
    use LetsGoOfARefusedSession;

    /** Where nothing has moved for the core to keep: the player has not begun, or is waiting for the door. */
    private const array NOTHING_TO_TELL = [PlaybackIs::Opening, PlaybackIs::Stalled];

    /** Where it stopped and cannot go on, with nothing to try again. */
    private const array STOPPED_FOR_GOOD = [PlaybackIs::StoppedOutOfReach, PlaybackIs::StoppedByThePin, PlaybackIs::StoppedOnTheFormat];

    public function __construct(
        private Playing $player,
        private WhatIsPlaying $playing,
        private KeepingThePlace $places,
        private SecureStorage $storage,
        private PutsATitleOnScreen $screen,
    ) {}

    /** The player moved: read where it stands, and settle what that means. */
    public function heard(): void
    {
        $playback = $this->playing->now();

        if (! $playback instanceof APlayback) {
            return;
        }

        $stands = $this->player->whereItStands();
        $is = $stands->is();
        $at = $stands->howFarIn();

        if (in_array($is, self::NOTHING_TO_TELL, strict: true)) {
            return;
        }

        if ($is === PlaybackIs::Ended) {
            $this->tell($playback, ThePlace::atTheEndOf($playback->played(), $at));
            $this->playing->over();

            return;
        }

        // A closed player that stands at nothing says nothing of the member's
        // place: it is also how a device with no player answers.
        if ($is !== PlaybackIs::Closed || ! $at->isTheStart()) {
            $this->tell($playback, ThePlace::in($playback->played(), $at));
        }

        $this->settle($playback, $is, $at);
    }

    /** What the move means beyond the place: nothing while it plays, an end where it closed or stopped. */
    private function settle(APlayback $playback, PlaybackIs $is, HowFarIn $at): void
    {
        if ($is === PlaybackIs::Closed) {
            $this->playing->over();
        }

        if (in_array($is, self::STOPPED_FOR_GOOD, strict: true)) {
            $this->stop($playback, $is);
        }

        if ($is === PlaybackIs::StoppedAtTheDoor) {
            $this->grantAgain($playback, $at);
        }
    }

    /** Ask for a grant again, once, and put the title back where it stopped; or stop. */
    private function grantAgain(APlayback $playback, HowFarIn $at): void
    {
        if ($playback->wasGrantedAgain()) {
            $this->stop($playback, PlaybackIs::StoppedAtTheDoor);

            return;
        }

        $this->theirSession($playback)->either(
            held: fn(Session $session): WhatPressingPlayCameTo => $this->screen->again($playback, $session, $at),
            notHeld: static fn(): WhatPressingPlayCameTo => WhatPressingPlayCameTo::nothingToPlay(),
        )->either(
            onScreen: static fn(): APlayback => $playback,
            met: fn(): APlayback => $this->stop($playback, PlaybackIs::StoppedAtTheDoor),
            notStarted: fn(): APlayback => $this->stop($playback, PlaybackIs::StoppedAtTheDoor),
            nothingToPlay: fn(): APlayback => $this->stop($playback, PlaybackIs::StoppedAtTheDoor),
        );
    }

    /** Close the player and keep why it stopped for the title's page; answers the playback it stopped. */
    private function stop(APlayback $playback, PlaybackIs $as): APlayback
    {
        $this->playing->stopped($as);
        $this->player->close();

        return $playback;
    }

    /**
     * Tell the core where the member is, where they are still the one signed in.
     *
     * What the core answered is dropped, but for a session it refused, which is let go of.
     */
    private function tell(APlayback $playback, ThePlace $place): void
    {
        $this->theirSession($playback)->either(
            held: fn(Session $session): object => $this->places->keep($playback->stack(), $session, $place)->either(
                kept: static fn(ThePlace $kept): ThePlace => $kept,
                refused: $this->lettingGoIfRefused($playback->stack(), static fn(): ThePlace => $place),
            ),
            notHeld: static fn(): ThePlace => $place,
        );
    }

    /** The session held on the playback's stack, where it is still the member's who pressed Play. */
    private function theirSession(APlayback $playback): Resumed
    {
        $resumed = $this->storage->resume($playback->stack()->id());

        return $resumed->whoseItIs(
            nobody: static fn(): Resumed => $resumed,
            theirs: static fn(Whose $whose): Resumed => $whose->is($playback->whose()) ? $resumed : Resumed::notHeld(),
        );
    }
}
