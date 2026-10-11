<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use Closure;
use Modules\Health\Internal\AThingHeard;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\WhatAStreamSaid;

/**
 * The last thing a screen heard on a stack's event stream, and whether that still stands.
 *
 * A value rather than a connection, for {@see WhatWasHeardSoFar}'s reason, and
 * held to the same bound: what was heard is current only while the
 * subscription that carried it is open and speaking. Past the contract's
 * silence the subscription is let go of, and from then on it is shown with
 * when it was heard. A subscription that closed, or that could not be opened,
 * leaves it in the same position.
 *
 * **Not listening yet is not a break.** A screen that has not opened the
 * subscription, or that let go of it because nobody could see it, has had
 * nothing fail: until the next wake opens it, nothing heard is nothing said
 * yet, and what is held is the last thing heard.
 *
 * **What was heard is replaced, never accumulated.** Each thing a stream says
 * stands for the whole of what it is about, so the newest is all this holds.
 *
 * **Opened again on a declared cadence, never sooner.** A subscription that broke
 * waits {@see HowOftenAScreenLooks::AfterABreak} before it is opened again. A screen the
 * operator left and came back to opens at once.
 *
 * @template-covariant T of object
 */
final readonly class WhatAStreamSaidSoFar
{
    /** @param AThingHeard<T>|null $heard */
    private function __construct(
        private ?AThingHeard $heard,
        private ?Instant $lastSignOfLife,
        private ?Instant $closedAt,
    ) {}

    /**
     * A screen that has not listened yet, and so holds nothing.
     *
     * @return self<never>
     */
    public static function nothingYet(): self
    {
        /** @var self<never> */
        return new self(null, null, null);
    }

    /**
     * What the screen holds once the subscription has answered at `$now`.
     *
     * @template U of object
     *
     * @param WhatAStreamSaid<U> $said
     *
     * @return self<T|U>
     */
    public function after(WhatAStreamSaid $said, Instant $now): self
    {
        return $said->either(
            nothing: fn(): self => new self($this->heard, $this->lastSignOfLife ?? $now, null),
            alive: fn(): self => new self($this->heard, $now, null),
            said: static fn(object $heard): self => new self(AThingHeard::at($heard, $now), $now, null),
            closed: fn(): self => $this->brokenAt($now),
            met: fn(): self => $this->brokenAt($now),
        );
    }

    /**
     * What the screen holds once the operator can no longer see it.
     *
     * What is held is no longer current, and there is no break to wait out:
     * the screen opens again the moment it is back in front of somebody.
     *
     * @return self<T>
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
     * Only then could nothing be heard, and only then is the screen waiting
     * out {@see HowOftenAScreenLooks::AfterABreak}.
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
     * What is held, by whether it still stands.
     *
     * @template R of object
     *
     * @param Closure(): R            $none
     * @param Closure(T): R           $current
     * @param Closure(T, Instant): R  $asOf
     *
     * @return R
     */
    public function heard(Closure $none, Closure $current, Closure $asOf): object
    {
        if (! $this->heard instanceof AThingHeard) {
            return $none();
        }

        return $this->heard->current ? $current($this->heard->said) : $asOf($this->heard->said, $this->heard->at);
    }

    /** @return self<T> */
    private function brokenAt(Instant $now): self
    {
        return new self($this->heldNoLongerCurrent(), null, $now);
    }

    /**
     * What is held, once the subscription that carried it is no longer open.
     *
     * @return AThingHeard<T>|null
     */
    private function heldNoLongerCurrent(): ?AThingHeard
    {
        return $this->heard instanceof AThingHeard ? $this->heard->noLongerCurrent() : null;
    }
}
