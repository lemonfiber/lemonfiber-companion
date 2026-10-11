<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Health\Api\WhatAStreamSaidSoFar;
use Modules\Kernel\Api\Capture;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\FollowingWhatIsPlaying;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheHouseholdIsPlaying;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowWhatIsPlayingReads;
use Modules\Operator\Internal\ViewModels\WhatIsPlayingAsShown;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Every session the media server is playing, for the operator: asked on the first frame, then kept current from the stack's `playing` event.
 *
 * The stream is held only while the screen is in front of somebody, and let go
 * of past the contract's silence; what was last heard is then shown with when.
 * A member's session never asks.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatIsPlayingNow extends NativeComponent
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;
    use AsksAgain;

    public const string TEMPLATE = 'operator::what-is-playing-now';

    /**
     * What the core and the stream have said so far, and whether it still stands; nothing until the first frame asks.
     *
     * `public` because it is what the component's property syncing writes and
     * what the view reads.
     *
     * @var WhatAStreamSaidSoFar<WhatTheHouseholdIsPlaying>|null
     */
    public ?WhatAStreamSaidSoFar $playingHeard = null;

    /** What stood in the way of asking, until the stream next says what is playing. */
    public ?Obstacle $stopped = null;

    public bool $isSignedIn = true;

    public bool $isTheOperator = true;

    public function __construct(
        private readonly FollowingWhatIsPlaying $following,
        private readonly SecureStorage $storage,
        private readonly Clock $clock,
        private readonly Capture $capture,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** Take what the stream has said since the last look, opening it where it is not open. */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function follow(): void
    {
        $held = $this->playingHeardSoFar();
        $now = $this->clock->now();

        if (! $this->capture->isInFront()) {
            $this->playingHeard = $held->after($this->following->letGo(), $now)->wentAway();

            return;
        }

        if (! $this->isSignedIn || ! $this->isTheOperator || ! $held->mayListen($now)) {
            return;
        }

        $held = $held->after($this->fromTheStream($this->stack()), $now);

        $this->playingHeard = $held->hasGoneQuiet($now) ? $held->after($this->following->letGo(), $now) : $held;
    }

    public function shown(): WhatIsPlayingAsShown
    {
        $heard = $this->playingHeardSoFar();

        return match (true) {
            ! $this->isSignedIn => new HowWhatIsPlayingReads()->signedOut(),
            ! $this->isTheOperator => new HowWhatIsPlayingReads()->forAMember(),
            $this->stopped instanceof Obstacle => new HowWhatIsPlayingReads()->met($this->stopped),
            default => new HowWhatIsPlayingReads()->of($heard, $this->clock->now()),
        };
    }

    public function again(): void
    {
        $this->playingHeard = null;
    }

    protected function letGoOfWhatElseItHears(): void
    {
        $this->playingHeard = $this->playingHeardSoFar()->after($this->following->letGo(), $this->clock->now())->wentAway();
    }

    /**
     * What has been heard so far, asking the core first where nothing has been asked yet.
     *
     * @return WhatAStreamSaidSoFar<WhatTheHouseholdIsPlaying>
     */
    private function playingHeardSoFar(): WhatAStreamSaidSoFar
    {
        return $this->playingHeard ??= $this->askedNow();
    }

    /**
     * Ask the core what is playing, as a fresh start for what the stream says after it.
     *
     * @return WhatAStreamSaidSoFar<WhatTheHouseholdIsPlaying>
     */
    private function askedNow(): WhatAStreamSaidSoFar
    {
        $stack = $this->stack();
        $this->isSignedIn = true;
        $this->isTheOperator = true;
        $this->stopped = null;

        $answer = $this->asTheOperator($stack, fn(Session $session): WhatAStreamSaid => $this->following->now($stack, $session)->either(
            said: static fn(WhatTheHouseholdIsPlaying $playing): WhatAStreamSaid => WhatAStreamSaid::said($playing),
            refused: function (Obstacle $why) use ($stack): WhatAStreamSaid {
                $this->letGoOfARefusal($why, $stack);
                $this->stopped = $why;

                return WhatAStreamSaid::met($why);
            },
        ));

        return WhatAStreamSaidSoFar::nothingYet()->after($answer, $this->clock->now());
    }

    /**
     * What the stream says, with the operator's session.
     *
     * @return WhatAStreamSaid<WhatTheHouseholdIsPlaying>
     */
    private function fromTheStream(Stack $stack): WhatAStreamSaid
    {
        return $this->asTheOperator($stack, function (Session $session) use ($stack): WhatAStreamSaid {
            $said = $this->following->heard($stack, $session);

            return $said->either(
                nothing: static fn(): WhatAStreamSaid => $said,
                alive: static fn(): WhatAStreamSaid => $said,
                said: function () use ($said): WhatAStreamSaid {
                    $this->stopped = null;

                    return $said;
                },
                closed: static fn(): WhatAStreamSaid => $said,
                met: function (Obstacle $why) use ($said, $stack): WhatAStreamSaid {
                    $this->letGoOfARefusal($why, $stack);

                    return $said;
                },
            );
        });
    }

    /**
     * What `$asking` says with the operator's session; a member's is never used to ask.
     *
     * @param Closure(Session): WhatAStreamSaid<WhatTheHouseholdIsPlaying> $asking
     *
     * @return WhatAStreamSaid<WhatTheHouseholdIsPlaying>
     */
    private function asTheOperator(Stack $stack, Closure $asking): WhatAStreamSaid
    {
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: fn(Session $session): WhatAStreamSaid => $resumed->whoseItIs(
                nobody: fn(): WhatAStreamSaid => $this->signedOut(),
                theirs: fn(Whose $whose): WhatAStreamSaid => $whose->either(
                    operator: static fn(): WhatAStreamSaid => $asking($session),
                    member: fn(): WhatAStreamSaid => $this->aMembersSession(),
                ),
            ),
            notHeld: fn(): WhatAStreamSaid => $this->signedOut(),
        );
    }

    /** Let go of a session the stack refused, which signs this device out of it. */
    private function letGoOfARefusal(Obstacle $why, Stack $stack): void
    {
        $this->letGoOfTheSession($why, $stack);
        $this->isSignedIn = ! $why->meansWeAreSignedOut();
    }

    /** @return WhatAStreamSaid<never> */
    private function signedOut(): WhatAStreamSaid
    {
        $this->isSignedIn = false;

        return WhatAStreamSaid::closed();
    }

    /** @return WhatAStreamSaid<never> */
    private function aMembersSession(): WhatAStreamSaid
    {
        $this->isTheOperator = false;

        return WhatAStreamSaid::closed();
    }
}
