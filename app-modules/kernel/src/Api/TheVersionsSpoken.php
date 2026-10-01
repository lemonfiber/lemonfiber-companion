<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The version of the API a stack answered in, and the one this app reads, where the two differ.
 *
 * Carried by {@see Obstacle::versionsDisagree()} so that what the operator
 * reads can name both, and so the remedy can say which side to update: the one
 * that is older.
 */
final readonly class TheVersionsSpoken
{
    private function __construct(private int $answered, private int $spoken) {}

    /** What the stack answered in, and what this app reads; the two must differ. */
    public static function between(int $answered, int $spoken): self
    {
        if ($answered === $spoken || $answered < 0 || $spoken < 0) {
            throw ObstacleIsNotOne::becauseTheVersionsAgree($answered, $spoken);
        }

        return new self($answered, $spoken);
    }

    /** The version the stack answered in. */
    public function answered(): int
    {
        return $this->answered;
    }

    /** The version this app reads. */
    public function spoken(): int
    {
        return $this->spoken;
    }

    /** Whether the stack is the newer of the two, which makes this app the one to update. */
    public function isTheStackNewer(): bool
    {
        return $this->answered > $this->spoken;
    }
}
