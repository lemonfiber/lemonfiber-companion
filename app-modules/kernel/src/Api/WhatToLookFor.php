<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strlen;
use function trim;

/**
 * The words a member searches for, trimmed.
 *
 * Fewer than {@see self::FEWEST} characters is a search the core refuses, so
 * it is not sent: a member still typing is not a refusal. Longer than the core
 * allows is the core's to refuse, in its own words.
 */
final readonly class WhatToLookFor
{
    /** The fewest characters the core searches for. */
    public const int FEWEST = 2;

    private function __construct(private string $words) {}

    /** What the member typed, trimmed. */
    public static function typed(string $typed): self
    {
        return new self(trim($typed));
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
