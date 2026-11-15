<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a machine runs the stack this build ships, or the operator's own in its place.
 */
final readonly class WhoseStackItIs
{
    private function __construct(private string $own) {}

    /** The stack this build ships. */
    public static function bundled(): self
    {
        return new self('');
    }

    /** The operator's own stack, by the directory it lives in. */
    public static function theOperators(string $directory): self
    {
        return new self($directory);
    }

    /** Whether it is the operator's own. */
    public function isTheOperators(): bool
    {
        return $this->own !== '';
    }

    /** The directory the operator's own stack lives in, empty where it is the bundled one. */
    public function directory(): string
    {
        return $this->own;
    }
}
