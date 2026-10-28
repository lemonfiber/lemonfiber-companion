<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/**
 * Where work put to a stack stands, as every screen that follows such work draws it.
 *
 * `went` says whether the stack could be asked at all; only where it could
 * is the work not asked, running, ended or answered. What it answered is the
 * screen's own, beside this.
 */
final readonly class WhereTheWorkStands
{
    private function __construct(public HowTheReadingWent $went, private ?TheWorkIs $is) {}

    /** This phone holds no session for the stack. */
    public static function signedOut(): self
    {
        return new self(HowTheReadingWent::theSessionEnded(), null);
    }

    /** Asking met this instead. */
    public static function met(Obstacle $why): self
    {
        return new self(HowTheReadingWent::somethingStopped($why), null);
    }

    /** Nothing has been put to the stack yet. */
    public static function notAsked(): self
    {
        return new self(HowTheReadingWent::itCameBack(), TheWorkIs::NotAsked);
    }

    /** The stack is at it. */
    public static function running(): self
    {
        return new self(HowTheReadingWent::itCameBack(), TheWorkIs::Running);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self(HowTheReadingWent::itCameBack(), TheWorkIs::Ended);
    }

    /** The stack answered. */
    public static function answered(): self
    {
        return new self(HowTheReadingWent::itCameBack(), TheWorkIs::Answered);
    }

    /** Whether anything was put to the stack from this screen. */
    public function wasAsked(): bool
    {
        return $this->is !== TheWorkIs::NotAsked;
    }

    /** Whether the stack is at it. */
    public function isWorking(): bool
    {
        return $this->is === TheWorkIs::Running;
    }

    /** Whether the stack has no outcome for it any more. */
    public function hasEnded(): bool
    {
        return $this->is === TheWorkIs::Ended;
    }
}
