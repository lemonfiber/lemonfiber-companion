<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A place lemonfiber keeps its files that removing everything could not take, and why.
 */
final readonly class APathLeft
{
    private function __construct(private string $at, private string $why) {}

    /** Where it is, and why the stack could not remove it. */
    public static function at(string $at, string $why): self
    {
        return new self($at, $why);
    }

    public function where(): string
    {
        return $this->at;
    }

    public function why(): string
    {
        return $this->why;
    }
}
