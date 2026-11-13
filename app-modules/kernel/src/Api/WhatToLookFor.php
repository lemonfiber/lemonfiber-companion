<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strlen;
use function trim;

/**
 * The words a member searches for, trimmed, and what they are looked for among.
 *
 * Fewer than {@see self::FEWEST} characters is a search the core refuses, so
 * it is not sent: a member still typing is not a refusal. Longer than the core
 * allows is the core's to refuse, in its own words.
 */
final readonly class WhatToLookFor
{
    /** The fewest characters the core searches for. */
    public const int FEWEST = 2;

    private function __construct(private string $words, private WhichTitles $among) {}

    /** What the member typed, trimmed, looked for among films and series. */
    public static function typed(string $typed): self
    {
        return new self(trim($typed), WhichTitles::filmsAndSeries());
    }

    /** The same words, looked for among these titles instead. */
    public function among(WhichTitles $among): self
    {
        return new self($this->words, $among);
    }

    /** What the words are looked for among, which every page of the search asks again. */
    public function which(): WhichTitles
    {
        return $this->among;
    }

    /** Whether there is enough here to search for. */
    public function isEnough(): bool
    {
        return mb_strlen($this->words) >= self::FEWEST;
    }

    /** The words, as sent. */
    public function said(): string
    {
        return $this->words;
    }
}
