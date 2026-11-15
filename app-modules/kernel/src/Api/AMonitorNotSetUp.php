<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A monitor that could not be set up to watch what it should, in the stack's words.
 */
final readonly class AMonitorNotSetUp
{
    private function __construct(private string $wanted, private string $watches, private string $why) {}

    /** What it was meant to watch, what it watches instead, and why. */
    public static function said(string $wanted, string $watches, string $why): self
    {
        return new self($wanted, $watches, $why);
    }

    /** What it was meant to watch. */
    public function wanted(): string
    {
        return $this->wanted;
    }

    /** What it watches instead. */
    public function watches(): string
    {
        return $this->watches;
    }

    /** Why. */
    public function why(): string
    {
        return $this->why;
    }
}
