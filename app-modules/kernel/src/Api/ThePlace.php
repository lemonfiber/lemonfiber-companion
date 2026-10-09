<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a member is in one title or episode, as the core is told it.
 *
 * Reaching the end is said once, when the player reached it; any other place
 * says nothing about finishing.
 */
final readonly class ThePlace
{
    private function __construct(private HoldingId $in, private HowFarIn $howFarIn, private bool $isTheEnd) {}

    /** So far into it. */
    public static function in(HoldingId $in, HowFarIn $howFarIn): self
    {
        return new self($in, $howFarIn, isTheEnd: false);
    }

    /** At its end, having played to it. */
    public static function atTheEndOf(HoldingId $in, HowFarIn $howFarIn): self
    {
        return new self($in, $howFarIn, isTheEnd: true);
    }

    public function holding(): HoldingId
    {
        return $this->in;
    }

    public function howFarIn(): HowFarIn
    {
        return $this->howFarIn;
    }

    /** Whether the member played it to the end. */
    public function isTheEnd(): bool
    {
        return $this->isTheEnd;
    }
}
