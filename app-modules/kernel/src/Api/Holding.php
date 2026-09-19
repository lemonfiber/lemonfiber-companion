<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One thing a member may watch.
 *
 * What the core says is on their shelf, carried as the core said it. Whether it
 * is there at all is a decision already made — by the member's entitlements,
 * their age limit and the libraries they reach — and made where those live.
 * This holds the answer and no part of the reasoning, which is the whole of
 * what a player may not do twice.
 *
 * **The year carries its own absence.** A holding the core could not date is
 * shown undated rather than guessed at, and {@see WhenItCameOut} is what makes
 * a screen say which — a number that might be missing is a number somebody
 * eventually prints as zero.
 */
final readonly class Holding
{
    private function __construct(
        private HoldingId $id,
        private string $titled,
        private Medium $medium,
        private WhenItCameOut $year,
    ) {}

    /** One row of a shelf, as the core listed it. */
    public static function of(HoldingId $id, string $titled, Medium $medium, WhenItCameOut $year): self
    {
        return new self($id, $titled, $medium, $year);
    }

    public function id(): HoldingId
    {
        return $this->id;
    }

    /** What it is called, in the words the core holds it under. */
    public function titled(): string
    {
        return $this->titled;
    }

    public function medium(): Medium
    {
        return $this->medium;
    }

    /**
     * When it came out, where the core said.
     *
     * A type rather than a nullable number, so a holding nobody dated and one
     * dated in year zero cannot arrive at a screen as the same thing.
     */
    public function year(): WhenItCameOut
    {
        return $this->year;
    }
}
