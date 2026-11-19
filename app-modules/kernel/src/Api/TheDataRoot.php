<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a stack keeps its library, or that no place is chosen yet.
 */
final readonly class TheDataRoot
{
    private function __construct(private string $path) {}

    /** The library is kept here, a path on the machine or an NFS export. */
    public static function at(string $path): self
    {
        return new self($path);
    }

    /** No place is chosen yet. */
    public static function notChosen(): self
    {
        return new self('');
    }

    /** Whether a place is chosen. */
    public function isChosen(): bool
    {
        return $this->path !== '';
    }

    /** Where the library is kept, empty where no place is chosen. */
    public function path(): string
    {
        return $this->path;
    }
}
