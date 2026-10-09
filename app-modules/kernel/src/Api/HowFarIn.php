<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function max;

/**
 * How far into a title a member is, in whole seconds.
 *
 * Whole because the core keeps a member's place in whole seconds. A position
 * before the start is the start: a player that has not begun answers nothing
 * further in than that.
 */
final readonly class HowFarIn
{
    /** @param int<0, max> $seconds */
    private function __construct(private int $seconds) {}

    /** So many seconds in. */
    public static function at(int $seconds): self
    {
        return new self(max(0, $seconds));
    }

    /** The very start. */
    public static function theStart(): self
    {
        return new self(0);
    }

    /** @return int<0, max> */
    public function seconds(): int
    {
        return $this->seconds;
    }

    /** Whether this is the very start. */
    public function isTheStart(): bool
    {
        return $this->seconds === 0;
    }
}
