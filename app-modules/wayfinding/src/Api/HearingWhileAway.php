<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use function count;
use function min;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Connection\Api\TurningNotificationsOn;
use Modules\Kernel\Api\Hearing;
use Modules\Kernel\Api\HowLongEachReadWaits;
use Modules\Kernel\Api\Notifying;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Whose;
use Modules\Wayfinding\Internal\HowFarItGot;
use Modules\Wayfinding\Internal\TheStacksToHear;

/**
 * What one wake while the app is away hears, and tells.
 *
 * The platform woke the app with nothing to say; what is told is read here,
 * over the app's own pinned connection to each stack the operator is signed
 * in to, the way the list of stacks hears them while the app is open.
 * {@see WhatItListensWith::kept()} tells each alert a stack raised and keeps
 * what its summary said, so the next opening draws it.
 *
 * **Each stream opens where hearing last left off**, so what a stack raised
 * while nobody listened arrives first, before anything else it says. Its
 * first summary therefore ends what this wake waits for from it; a stream
 * that ended, or a stack that could not be heard, ends it too.
 *
 * **Only while notifications are on, and only while the app is away.** A wake
 * with them off, or with the operator's choice unreadable on a locked phone,
 * reaches no stack; nor does one that comes while the app is in front of
 * somebody, which hears its stacks itself.
 *
 * **Bounded.** Every stack is asked in turn, each read waiting at most
 * {@see self::EACH_READ_WAITS_MS} for what has not arrived, until each has said
 * its first summary or {@see self::AT_MOST_READS} reads have been made among
 * them, well inside the half-minute iOS allows a wake. Then every stream is
 * let go of.
 */
final readonly class HearingWhileAway
{
    use LetsGoOfARefusedSession;

    /** How many reads one wake makes at most, among every stack it hears. */
    public const int AT_MOST_READS = 40;

    /** How long one read waits at most for what a stack has not yet sent, in milliseconds. */
    public const int EACH_READ_WAITS_MS = 250;

    /** What each stack is heard on, each read waiting {@see self::EACH_READ_WAITS_MS}. */
    private Hearing $hearing;

    public function __construct(
        private WhatItListensWith $with,
        private SecureStorage $storage,
        private Stacks $stacks,
        private TurningNotificationsOn $notifications,
    ) {
        $this->hearing = $with->hearing->eachReadWaitingAtMost(HowLongEachReadWaits::ofMilliseconds(self::EACH_READ_WAITS_MS));
    }

    /** Hear each stack the operator is signed in to until it has said what it raised meanwhile, let go, and say how far it got. */
    public function hear(): HowTheWakeWent
    {
        if ($this->notifications->current() !== Notifying::On) {
            return HowTheWakeWent::NothingToTell;
        }

        if ($this->with->capture->isInFront()) {
            return HowTheWakeWent::TheAppIsOpen;
        }

        $pending = $this->theOperatorsStacks();
        $left = self::AT_MOST_READS;

        while (! $pending->isEmpty() && $left > 0) {
            $asking = min($left, count($pending));
            $pending = $this->stillToHear($pending, $asking);
            $left -= $asking;
        }

        $this->hearing->letGo();

        return $pending->isEmpty() ? HowTheWakeWent::HeardEveryStack : HowTheWakeWent::GaveUpOnSome;
    }

    /** Each stack this phone holds an operator's session for, with that session. */
    private function theOperatorsStacks(): TheStacksToHear
    {
        $toHear = TheStacksToHear::none();

        foreach ($this->stacks->configured() as $stack) {
            $toHear = $this->storage->resume($stack->id())->either(
                held: static fn(Session $session, Whose $whose): TheStacksToHear => $whose->either(
                    operator: static fn(): TheStacksToHear => $toHear->with($stack, $session),
                    member: static fn(): TheStacksToHear => $toHear,
                ),
                notHeld: static fn(): TheStacksToHear => $toHear,
            );
        }

        return $toHear;
    }

    /** Ask the first so many stacks once, in turn, and answer those not yet heard out, the ones not asked among them. */
    private function stillToHear(TheStacksToHear $stacks, int $asking): TheStacksToHear
    {
        $still = TheStacksToHear::none();

        foreach ($stacks as $position => [$stack, $session]) {
            if ($position >= $asking || ! $this->heardOut($stack, $session)) {
                $still = $still->with($stack, $session);
            }
        }

        return $still;
    }

    /** Ask one stack what it has sent, tell and keep it, and answer whether that ends this wake's listening to it. */
    private function heardOut(Stack $stack, Session $session): bool
    {
        $heard = $this->with->kept($this->hearing->howItIs($stack, $session), $stack, $this->with->clock->now());

        return $heard->either(
            nothing: static fn(): HowFarItGot => HowFarItGot::StillSpeaking,
            alive: static fn(): HowFarItGot => HowFarItGot::StillSpeaking,
            said: static fn(): HowFarItGot => HowFarItGot::HeardOut,
            closed: static fn(): HowFarItGot => HowFarItGot::HeardOut,
            met: function (Obstacle $why) use ($stack): HowFarItGot {
                $this->letGoOfTheSession($why, $stack);

                return HowFarItGot::HeardOut;
            },
        ) === HowFarItGot::HeardOut;
    }
}
