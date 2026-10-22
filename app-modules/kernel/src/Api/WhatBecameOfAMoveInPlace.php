<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A move in place an update tried: taken, or not taken and why, in the stack's words. */
final readonly class WhatBecameOfAMoveInPlace
{
    private function __construct(private AMoveInPlace $move, private bool $taken, private string $why) {}

    /** The plugin now runs in the service's place. */
    public static function taken(AMoveInPlace $move): self
    {
        return new self($move, taken: true, why: '');
    }

    /** The service runs as it did, for the reason the stack gave, or for none it gave. */
    public static function notTaken(AMoveInPlace $move, string $because): self
    {
        return new self($move, taken: false, why: $because);
    }

    /** The move this was. */
    public function move(): AMoveInPlace
    {
        return $this->move;
    }

    /** Whether the plugin now runs in the service's place. */
    public function wasTaken(): bool
    {
        return $this->taken;
    }

    /** Why it was not taken, in the stack's words, or empty where it was taken or the stack gave no reason. */
    public function why(): string
    {
        return $this->why;
    }
}
