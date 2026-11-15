<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One unhealthy download, as the queue's latest pass found it: why, how many strikes it holds, and what the next run would do.
 */
final readonly class AWedgedItem
{
    private function __construct(
        private ADownloadInTheQueue $download,
        private WhyItIsWedged $class,
        private string $why,
        private int $strikes,
        private WhenItSays $addedAt,
        private WhenItSays $inGraceUntil,
        private WhatTheNextRunWouldDo $next,
    ) {}

    /** A download flagged for this reason, in the stack's words, by this many passes in a row. */
    public static function flagged(ADownloadInTheQueue $download, WhyItIsWedged $class, string $why, int $strikes): self
    {
        return new self($download, $class, $why, $strikes, WhenItSays::unsaid(), WhenItSays::unsaid(), WhatTheNextRunWouldDo::these());
    }

    /** The same item, added at this moment, or where nothing says when. */
    public function added(WhenItSays $at): self
    {
        return new self($this->download, $this->class, $this->why, $this->strikes, $at, $this->inGraceUntil, $this->next);
    }

    /** The same item, left alone as newly added until this moment, or no longer. */
    public function leftAloneUntil(WhenItSays $until): self
    {
        return new self($this->download, $this->class, $this->why, $this->strikes, $this->addedAt, $until, $this->next);
    }

    /** The same item, which the next run would take these acts on, in order. */
    public function nextWould(AHealingAct ...$acts): self
    {
        return new self($this->download, $this->class, $this->why, $this->strikes, $this->addedAt, $this->inGraceUntil, WhatTheNextRunWouldDo::these(...$acts));
    }

    /** The download. */
    public function download(): ADownloadInTheQueue
    {
        return $this->download;
    }

    /** Why it is unhealthy. */
    public function class(): WhyItIsWedged
    {
        return $this->class;
    }

    /** Why, in the stack's words. */
    public function why(): string
    {
        return $this->why;
    }

    /** How many passes in a row have flagged it for this reason. */
    public function strikes(): int
    {
        return $this->strikes;
    }

    /** When it was added, or that nothing says. */
    public function whenAdded(): WhenItSays
    {
        return $this->addedAt;
    }

    /** Until when it is left alone as newly added, or that it no longer is. */
    public function graceEnds(): WhenItSays
    {
        return $this->inGraceUntil;
    }

    /** What the next run would do to it, none where healing is off. */
    public function next(): WhatTheNextRunWouldDo
    {
        return $this->next;
    }
}
