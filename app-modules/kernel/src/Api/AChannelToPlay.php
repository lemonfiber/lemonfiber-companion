<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One channel as the player is handed it: its live stream, as the core opened it, and what the lock screen calls it.
 *
 * It carries the stream, so it goes nowhere the stream may not: {@see ALiveStream} refuses to be written down.
 */
final readonly class AChannelToPlay
{
    private function __construct(private ALiveStream $stream, private string $named) {}

    public static function of(ALiveStream $stream, string $named): self
    {
        return new self($stream, $named);
    }

    public function stream(): ALiveStream
    {
        return $this->stream;
    }

    /** What the lock screen and picture-in-picture call it. */
    public function named(): string
    {
        return $this->named;
    }
}
