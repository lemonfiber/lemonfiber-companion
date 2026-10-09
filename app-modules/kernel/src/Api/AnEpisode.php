<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** One episode of a series, as the member's own session reads it, with where it streams from. */
final readonly class AnEpisode
{
    private function __construct(
        private HoldingId $id,
        private string $titled,
        private NumberedAs $number,
        private HowLongItRuns $runs,
        private string $about,
        private WhereItPlays $plays,
    ) {}

    /** @param string $about what happens in it, or empty where the server holds no description */
    public static function of(HoldingId $id, string $titled, NumberedAs $number, HowLongItRuns $runs, string $about, WhereItPlays $plays): self
    {
        return new self($id, $titled, $number, $runs, $about, $plays);
    }

    public function id(): HoldingId
    {
        return $this->id;
    }

    public function titled(): string
    {
        return $this->titled;
    }

    public function number(): NumberedAs
    {
        return $this->number;
    }

    public function runs(): HowLongItRuns
    {
        return $this->runs;
    }

    /** What happens in it, or empty where the server holds no description. */
    public function about(): string
    {
        return $this->about;
    }

    public function plays(): WhereItPlays
    {
        return $this->plays;
    }
}
