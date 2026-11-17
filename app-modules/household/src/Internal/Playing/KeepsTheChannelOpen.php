<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Playing;

use function in_array;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PlaybackIs;
use Modules\Kernel\Api\Playing;
use Modules\Kernel\Api\Resumed;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Tuning;
use Modules\Kernel\Api\Whose;

/**
 * What the player moving means for a channel on it: its passes renewed before they lapse, and the core told when it stops.
 *
 * The player reports every ten seconds while a channel is open, paused
 * included, so each report is the moment to look at the lapse. Within
 * {@see self::RENEWS_WITHIN} seconds of it the core is asked to renew. A
 * refusal in the core's own words is final, and the channel stops at the
 * lapse saying it. A core that could not be reached is asked again on each
 * report, and where the lapse comes first the channel stops saying what stood
 * in the way. Nothing is tried again once it has stopped.
 */
final readonly class KeepsTheChannelOpen
{
    use LetsGoOfARefusedSession;

    /** How close to the lapse, in seconds, the passes are renewed: well inside the five minutes a pass lasts. */
    public const int RENEWS_WITHIN = 120;

    /** Where it stopped on the player and cannot go on. */
    private const array STOPPED_ON_THE_PLAYER = [PlaybackIs::StoppedOutOfReach, PlaybackIs::StoppedByThePin, PlaybackIs::StoppedOnTheFormat, PlaybackIs::StoppedAtTheDoor];

    /** Where the player is gone or the stream came to an end. */
    private const array OFF_THE_PLAYER = [PlaybackIs::Closed, PlaybackIs::Ended];

    public function __construct(
        private Playing $player,
        private TheChannelOnScreen $channel,
        private Tuning $tuning,
        private SecureStorage $storage,
        private Clock $clock,
    ) {}

    /** The player moved: read where it stands, and settle what that means for the channel on it. */
    public function heard(): void
    {
        $on = $this->channel->now();

        if (! $on instanceof AChannelOnScreen) {
            return;
        }

        $is = $this->player->whereItStands()->is();

        if (in_array($is, self::OFF_THE_PLAYER, strict: true)) {
            $this->stopAtTheCore($on);
            $this->channel->over();

            return;
        }

        if (in_array($is, self::STOPPED_ON_THE_PLAYER, strict: true)) {
            $this->stopAtTheCore($on);
            $this->channel->stoppedOnThePlayer($is);
            $this->player->close();

            return;
        }

        $this->keepOpen($on, $this->clock->now());
    }

    /** Stop at the lapse, renew within reach of it, and otherwise wait for the next report. */
    private function keepOpen(AChannelOnScreen $on, Instant $now): void
    {
        $left = $on->stream()->lapses()->epochSeconds() - $now->epochSeconds();

        if ($left <= 0) {
            $this->stopAtTheCore($on);
            $this->channel->lapsed($on->whyItLapses() ?? Obstacle::of(KindOfObstacle::StackDidNotAnswer));
            $this->player->close();

            return;
        }

        if (! $on->wasRefused() && $left <= self::RENEWS_WITHIN) {
            $this->channel->kept($this->renewed($on));
        }
    }

    /** The channel as renewing it came to: good for longer, refused for good, or not reached this time. */
    private function renewed(AChannelOnScreen $on): AChannelOnScreen
    {
        return $this->theirSession($on)->either(
            held: fn(Session $session): AChannelOnScreen => $this->tuning->renew($on->stack(), $session, $on->whose(), $on->stream())->either(
                renewed: static fn(Instant $lapses): AChannelOnScreen => $on->renewedUntil($lapses),
                refused: $this->lettingGoIfRefused($on->stack(), static fn(Obstacle $why): AChannelOnScreen => $why->whatTheHouseholdWasTold()->wasSaid() ? $on->refused($why) : $on->unreached($why)),
            ),
            notHeld: static fn(): AChannelOnScreen => $on->refused(Obstacle::of(KindOfObstacle::CredentialWasRefused)),
        );
    }

    /** Tell the core the stream stopped, so it closes it now rather than at the lapse; what it answers changes nothing here. */
    private function stopAtTheCore(AChannelOnScreen $on): void
    {
        $this->theirSession($on)->either(
            held: fn(Session $session): object => $this->tuning->stop($on->stack(), $session, $on->whose(), $on->stream()),
            notHeld: static fn(): object => $on,
        );
    }

    /** The session held on the channel's stack, where it is still the member's who tuned to it. */
    private function theirSession(AChannelOnScreen $on): Resumed
    {
        $resumed = $this->storage->resume($on->stack()->id());

        return $resumed->whoseItIs(
            nobody: static fn(): Resumed => $resumed,
            theirs: static fn(Whose $whose): Resumed => $whose->is($on->whose()) ? $resumed : Resumed::notHeld(),
        );
    }
}
