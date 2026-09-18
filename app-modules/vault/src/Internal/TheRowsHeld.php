<?php

declare(strict_types=1);

namespace Modules\Vault\Internal;

/**
 * The rows a record held, carried out of an `either()` arm.
 *
 * {@see \Lemonfiber\Native\WasRead::either()} answers with an object, so an
 * array cannot come straight out of an arm. This is what the arms are allowed
 * to build, and it exists for the same reason {@see WhetherAnythingIsHeld}
 * does — the outcome type is what makes a caller say what happens in all three
 * cases, and carrying the result across is the price of that.
 *
 * `Internal` because it is a detail of how this module reads an outcome.
 */
final readonly class TheRowsHeld
{
    /** @param array<mixed> $rows */
    private function __construct(public array $rows) {}

    /**
     * The rows a record this build wrote was holding.
     *
     * @param array<mixed> $rows
     */
    public static function of(array $rows): self
    {
        return new self(rows: $rows);
    }

    /** No record, or none this build is willing to read. */
    public static function none(): self
    {
        return new self(rows: []);
    }
}
