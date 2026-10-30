<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** How long one read of a stack's stream waits at most for what the stack has not yet sent. */
final readonly class HowLongEachReadWaits
{
    private function __construct(private int $milliseconds) {}

    public static function ofMilliseconds(int $milliseconds): self
    {
        return new self($milliseconds);
    }

    /** The wait, in milliseconds. */
    public function milliseconds(): int
    {
        return $this->milliseconds;
    }
}
