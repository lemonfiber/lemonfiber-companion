<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One programme in a channel's guide: its title, when it starts and ends, and its certificate where it carries one.
 *
 * The core leaves out each programme outside the member's limits, so nothing
 * here is checked against an age limit again.
 */
final readonly class AnAiring
{
    private function __construct(private string $title, private Instant $starts, private Instant $ends, private string $certificate) {}

    public static function of(string $title, Instant $starts, Instant $ends, string $certificate): self
    {
        return new self($title, $starts, $ends, $certificate);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function starts(): Instant
    {
        return $this->starts;
    }

    public function ends(): Instant
    {
        return $this->ends;
    }

    /** The certificate it carries, or empty where it carries none. */
    public function certificate(): string
    {
        return $this->certificate;
    }

    /** Whether it is on at this moment: started, and not yet ended. */
    public function isOnAt(Instant $moment): bool
    {
        return ! $moment->isBefore($this->starts) && $moment->isBefore($this->ends);
    }
}
