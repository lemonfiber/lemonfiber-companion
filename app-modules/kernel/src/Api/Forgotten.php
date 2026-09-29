<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How much a store let go of when it was asked to forget.
 *
 * An answer rather than nothing, which `C1` asks of every method that changes
 * something: a caller that cannot see what forgetting did cannot test it. A
 * store that could not be reached has forgotten nothing and says so, which is
 * the same answer as a store that held nothing, because there is nothing
 * different for a caller to do about either.
 */
final readonly class Forgotten
{
    private function __construct(private int $rows) {}

    /** The one place a count becomes an answer: as many as the store said it removed. */
    public static function rows(int $rows): self
    {
        return new self($rows);
    }

    /** Nothing was let go of. */
    public static function nothing(): self
    {
        return new self(0);
    }

    /** What two stores forgot between them. */
    public function beside(self $other): self
    {
        return new self($this->rows + $other->rows);
    }

    public function howMany(): int
    {
        return $this->rows;
    }
}
