<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use Closure;
use Modules\Health\Internal\AStepHeard;
use Modules\Kernel\Api\ALineItSaid;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhatTheWalkSaid;

/**
 * Which step a running walkthrough last said it was at, and whether that still stands.
 *
 * A value rather than a connection, for {@see WhatWasHeardSoFar}'s reason, and
 * held to the same bound: a step is current only while the subscription that
 * carried it is open and speaking. Past the contract's silence the subscription
 * is let go of, and from then on the step is shown with when it was heard and
 * the stage reads as not known. A subscription that closed, or that could not be
 * opened, leaves the step in the same position.
 *
 * **Not listening yet is not a break.** A screen that has not opened the
 * subscription, or that let go of it because nobody could see it, has had
 * nothing fail: until the next wake opens it, a stage not heard is one not
 * said yet, and a stage held is the last one heard.
 *
 * **A step is replaced, never accumulated.** The screen shows the stage the walk
 * is at, and the lines it said arrive whole with its record once it finishes,
 * so the newest step is all this holds. A later step at an earlier stage is
 * shown as the stage it is at: the stack does not promise a walk only moves
 * forward, and neither does this.
 *
 * **Opened again on a declared cadence, never sooner.** A subscription that broke
 * waits {@see HowOftenAScreenLooks::AfterABreak} before it is opened again. A screen the
 * operator left and came back to opens at once.
 */
final readonly class WhatTheWalkSaidSoFar
{
    private function __construct(
        private ?AStepHeard $heard,
        private ?Instant $lastSignOfLife,
        private ?Instant $closedAt,
    ) {}

    /** A screen that has not listened yet, and so holds nothing. */
    public static function nothingYet(): self
    {
        return new self(null, null, null);
    }

    /** What the screen holds once the subscription has answered at `$now`. */
    public function after(WhatTheWalkSaid $said, Instant $now): self
    {
        return $said->either(
            nothing: fn(): self => new self($this->heard, $this->lastSignOfLife ?? $now, null),
            alive: fn(): self => new self($this->heard, $now, null),
            said: static fn(ALineItSaid $line): self => new self(AStepHeard::at($line, $now), $now, null),
            closed: fn(): self => $this->brokenAt($now),
            met: fn(): self => $this->brokenAt($now),
        );
    }

    /**
     * What the screen holds once the operator can no longer see it.
     *
     * The step held is no longer current, and there is no break to wait out:
     * the screen opens again the moment it is back in front of somebody.
     */
    public function wentAway(): self
    {
        return new self($this->heldNoLongerCurrent(), null, null);
    }

    /** Whether the screen may ask the subscription at `$now`, which opens it where it is not open. */
    public function mayListen(Instant $now): bool
    {
        return ! $this->closedAt instanceof Instant
            || $now->epochSeconds() - $this->closedAt->epochSeconds() >= HowOftenAScreenLooks::AfterABreak->seconds();
    }

    /** Whether an open subscription has been silent past the contract's bound at `$now`. */
    public function hasGoneQuiet(Instant $now): bool
    {
        return $this->lastSignOfLife instanceof Instant && ! WhatWasHeardSoFar::isStillCurrent($this->lastSignOfLife, $now);
    }

    /**
     * Whether the subscription broke, closed or could not be opened, and has not been opened since.
     *
     * Only then could the stage not be heard, and only then is the screen
     * waiting out {@see HowOftenAScreenLooks::AfterABreak}.
     */
    public function hasBroken(): bool
    {
        return $this->closedAt instanceof Instant;
    }

    /** Whether a subscription is open. */
    public function isListening(): bool
    {
        return $this->lastSignOfLife instanceof Instant;
    }

    /**
     * The step held, by whether it still stands.
     *
     * @template T of object
     *
     * @param Closure(): T                     $none
     * @param Closure(ALineItSaid): T          $current
     * @param Closure(ALineItSaid, Instant): T $asOf
     *
     * @return T
     */
    public function step(Closure $none, Closure $current, Closure $asOf): object
    {
        if (! $this->heard instanceof AStepHeard) {
            return $none();
        }

        return $this->heard->current ? $current($this->heard->line) : $asOf($this->heard->line, $this->heard->at);
    }

    private function brokenAt(Instant $now): self
    {
        return new self($this->heldNoLongerCurrent(), null, $now);
    }

    /** What is held, once the subscription that carried it is no longer open. */
    private function heldNoLongerCurrent(): ?AStepHeard
    {
        return $this->heard instanceof AStepHeard ? $this->heard->noLongerCurrent() : null;
    }
}
