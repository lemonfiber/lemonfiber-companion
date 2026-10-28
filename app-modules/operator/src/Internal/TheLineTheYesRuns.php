<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/** What the stack's rehearsal said of the command a yes will run: the line, or that the rehearsal could not be read. */
final readonly class TheLineTheYesRuns
{
    /** @param ?string $command the command exactly as the rehearsal reported it, where it could be read */
    private function __construct(public ?string $command) {}

    public static function command(string $command): self
    {
        return new self($command);
    }

    public static function unread(): self
    {
        return new self(null);
    }
}
