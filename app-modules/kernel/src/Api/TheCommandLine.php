<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function implode;

/**
 * The exact command a stack runs for something it was asked.
 *
 * Built from the words the stack sent, and kept as one line the way the
 * stack's own terminal prints it: the words joined by spaces. Nothing is
 * generated that the operator cannot read, and this is the line to check a
 * run against at a terminal.
 */
final readonly class TheCommandLine
{
    private function __construct(private string $line) {}

    /** The program, then every argument, in the order the stack gave them. */
    public static function of(string $program, string ...$arguments): self
    {
        return new self(implode(' ', [$program, ...$arguments]));
    }

    /** The line as somebody would type it. */
    public function asTyped(): string
    {
        return $this->line;
    }
}
