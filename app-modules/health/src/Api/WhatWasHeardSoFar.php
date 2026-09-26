<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use Closure;
use Modules\Health\Internal\ASummaryHeard;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheHealthSummary;
use Modules\Kernel\Api\WhatWasHeard;

/**
 * What a screen holding a subscription has heard, and whether it still stands.
 *
 * A value rather than a connection: the screen holds the subscription, and this
 * holds what the screen may say about what came down it. Every answer the
 * subscription gives makes a new one, so the screen keeps one field and every
 * question below is asked of the same moment.
 *
 * **A summary is current only while the subscription that carried it is open
 * and speaking.** The contract has the core break silence at least every
 * fifteen seconds and a client call twice that in silence a broken stream, so
 * past thirty seconds with nothing heard the subscription is let go of, and
 * from then on what it carried is shown with its age and reads as unknown. A
 * subscription that closed, or that could not be opened, leaves the last
 * summary in the same position.
 *
 * **Opened again on a stated cadence, never sooner.** A subscription that broke
 * waits {@see HowOften::AfterABreak} before it is opened again. A screen the
 * operator left and came back to has nothing to wait for: it opens at once, and
 * shows what it held as not current until something new arrives.
 */
final readonly class WhatWasHeardSoFar
{
    /** How often the core breaks silence at the least, in seconds. */
    private const int HEARTBEAT_SECONDS = 15;

    /** How many heartbeats may go missing before a stream is broken rather than quiet. */
    private const int BEATS_OF_SILENCE = 2;

    private function __construct(
        private ?ASummaryHeard $heard,
        private ?Instant $lastSignOfLife,
        private ?Instant $closedAt,
        private ?Obstacle $stoppedBy,
    ) {}

    /** A screen that has not listened yet, and so holds nothing. */
    public static function nothingYet(): self
    {
        return new self(null, null, null, null);
    }

    /** What the screen holds once the subscription has answered at `$now`. */
    public function after(WhatWasHeard $heard, Instant $now): self
    {
        return $heard->either(
            nothing: fn(): self => $this->listening($this->lastSignOfLife ?? $now),
            alive: fn(): self => $this->listening($now),
            said: static fn(TheHealthSummary $summary): self => new self(ASummaryHeard::at($summary, $now), $now, null, null),
            closed: fn(): self => new self($this->heldNoLongerCurrent(), null, $now, null),
            met: fn(Obstacle $why): self => new self($this->heldNoLongerCurrent(), null, $now, $why),
        );
    }

    /**
     * What the screen holds once the operator can no longer see it.
     *
     * Nothing held is current any more, and there is no break to wait out: the
     * screen opens again the moment it is back in front of somebody.
     */
    public function wentAway(): self
    {
        return new self($this->heldNoLongerCurrent(), null, null, $this->stoppedBy);
    }

    /** Whether the screen may ask the subscription at `$now`, which opens it where it is not open. */
    public function mayListen(Instant $now): bool
    {
        return ! $this->closedAt instanceof Instant
            || $now->epochSeconds() - $this->closedAt->epochSeconds() >= HowOften::AfterABreak->seconds();
    }

    /** Whether an open subscription has been silent past the contract's bound at `$now`. */
    public function hasGoneQuiet(Instant $now): bool
    {
        return $this->lastSignOfLife instanceof Instant
            && $now->epochSeconds() - $this->lastSignOfLife->epochSeconds() > self::HEARTBEAT_SECONDS * self::BEATS_OF_SILENCE;
    }

    /** Whether a subscription is open, so the screen says how often it looks at it. */
    public function isListening(): bool
    {
        return $this->lastSignOfLife instanceof Instant;
    }

    /**
     * The summary held, by whether it still stands.
     *
     * @template T of object
     *
     * @param Closure(): T $none
     * @param Closure(TheHealthSummary): T $current
     * @param Closure(TheHealthSummary, Instant): T $asOf
     *
     * @return T
     */
    public function summary(Closure $none, Closure $current, Closure $asOf): object
    {
        if (! $this->heard instanceof ASummaryHeard) {
            return $none();
        }

        return $this->heard->current ? $current($this->heard->summary) : $asOf($this->heard->summary, $this->heard->at);
    }

    /**
     * What stopped the subscription last, where something did.
     *
     * @template T of object
     *
     * @param Closure(): T $nothing
     * @param Closure(Obstacle): T $met
     *
     * @return T
     */
    public function stoppedBy(Closure $nothing, Closure $met): object
    {
        return $this->stoppedBy instanceof Obstacle ? $met($this->stoppedBy) : $nothing();
    }

    /** What is held, once the subscription that carried it is no longer open. */
    private function heldNoLongerCurrent(): ?ASummaryHeard
    {
        return $this->heard instanceof ASummaryHeard ? $this->heard->noLongerCurrent() : null;
    }

    private function listening(Instant $since): self
    {
        return new self($this->heard, $since, null, null);
    }
}
