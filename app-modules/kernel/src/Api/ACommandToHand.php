<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A command a stack gave, under the name the share sheet shows it by, for the operator to copy out.
 */
final readonly class ACommandToHand
{
    private function __construct(private TheCommandLine $line, private string $named) {}

    /** This command line, under this name. */
    public static function of(TheCommandLine $line, string $named): self
    {
        return new self($line, $named);
    }

    /** What the sheet shows it as. */
    public function named(): string
    {
        return $this->named;
    }

    /** The whole of what is handed over: the line as somebody would type it. */
    public function text(): string
    {
        return $this->line->asTyped();
    }
}
