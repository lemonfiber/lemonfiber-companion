<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * One connection a wiring run attempted, how it turned out, and how serious that is.
 */
final readonly class AConnection
{
    private function __construct(
        private string $connection,
        private WhatItWouldBreak $breaks,
        private HowAConnectionEnded $ended,
    ) {}

    /** What the stack said of one connection; what it connects is required. */
    public static function of(string $connection, WhatItWouldBreak $breaks, HowAConnectionEnded $ended): self
    {
        if (trim($connection) === '') {
            throw TheWiringSaysNothing::about('connection');
        }

        return new self($connection, $breaks, $ended);
    }

    /** What was being connected, in the stack's words, such as `SABnzbd into Sonarr`. */
    public function connection(): string
    {
        return $this->connection;
    }

    /** How serious its outcome is. */
    public function breaks(): WhatItWouldBreak
    {
        return $this->breaks;
    }

    /** How it turned out. */
    public function ended(): HowAConnectionEnded
    {
        return $this->ended;
    }
}
