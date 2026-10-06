<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Override;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

/**
 * A log that keeps what it is told, for a test asking what was noted.
 *
 * Only the words are kept: what a note may carry is the question, and the
 * level it is noted at is the composition root's to decide.
 */
final class NotesKeptInMemory implements LoggerInterface
{
    use LoggerTrait;

    /** @var list<string> */
    private array $noted = [];

    /** @param array<array-key, mixed> $context */
    #[Override]
    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        $this->noted[] = (string) $message;
    }

    /** @return list<string> */
    public function noted(): array
    {
        return $this->noted;
    }
}
