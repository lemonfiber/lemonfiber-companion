<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A search of the indexers the stack says would settle a trace: what it spends, and what it covers, in the stack's words.
 *
 * Offered only where the trace stopped at *monitored* with nothing at the
 * quality and nothing at all still to be told apart, and a search would spend
 * something to tell them.
 */
final readonly class ASearchThatSettles
{
    private function __construct(private string $spends, private string $covers) {}

    /** What it spends and what it covers; a blank one is refused. */
    public static function spending(string $spends, string $covers): self
    {
        return new self(self::said($spends, 'spends'), self::said($covers, 'covers'));
    }

    /** What the search spends, in the stack's words. */
    public function spends(): string
    {
        return $this->spends;
    }

    /** What the search covers: the curator's wanted list at the quality in force. */
    public function covers(): string
    {
        return $this->covers;
    }

    private static function said(string $word, string $field): string
    {
        $trimmed = trim($word);

        if ($trimmed === '') {
            throw TheTraceSaysNothing::about($field);
        }

        return $trimmed;
    }
}
