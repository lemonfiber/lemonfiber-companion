<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function mb_strtolower;
use function trim;

/**
 * A word a screen draws, as the stack wrote it, to be looked up in the glossary.
 *
 * Its own type rather than text, so a word and a service name cannot be
 * handed to each other's routes.
 */
final readonly class AWordInUse
{
    private function __construct(private string $word) {}

    /** The word as it was drawn; a blank one is refused. */
    public static function named(string $word): self
    {
        if (trim($word) === '') {
            throw WordsSayNothing::about('word');
        }

        return new self($word);
    }

    /** The word itself. */
    public function said(): string
    {
        return $this->word;
    }

    /** Whether another word is this one, whatever the case. */
    public function is(self $other): bool
    {
        return mb_strtolower($other->word) === mb_strtolower($this->word);
    }
}
