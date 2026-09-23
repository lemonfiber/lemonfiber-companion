<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What the line was measured to carry, and how that figure came to be.
 *
 * Down and up apart, because a home connection is asymmetric and one figure
 * for both would make every upload share far larger than meant. How it was
 * measured and whether over the tunnel travel with the figures, because a
 * declared number is a claim and a number measured beside the tunnel is not
 * one about the tunnel.
 */
final readonly class WhatTheLineCarries
{
    private function __construct(
        private int $down,
        private int $up,
        private HowTheLineWasMeasured $measured,
        private Instant $taken,
        private WhetherItGoesThroughTheTunnel $tunnel,
    ) {}

    /** The figures, in bytes a second, and what they rest on. */
    public static function measured(int $down, int $up, HowTheLineWasMeasured $measured, Instant $taken, WhetherItGoesThroughTheTunnel $tunnel): self
    {
        if ($down < 0) {
            throw LineSaysNothing::negative('down', $down);
        }

        if ($up < 0) {
            throw LineSaysNothing::negative('up', $up);
        }

        return new self($down, $up, $measured, $taken, $tunnel);
    }

    /** Bytes a second down. */
    public function down(): int
    {
        return $this->down;
    }

    /** Bytes a second up. */
    public function up(): int
    {
        return $this->up;
    }

    /** Whether the figure was declared or observed. */
    public function measuredAs(): HowTheLineWasMeasured
    {
        return $this->measured;
    }

    /** When it was taken. */
    public function taken(): Instant
    {
        return $this->taken;
    }

    /** Whether the path it was measured over goes through the tunnel. */
    public function tunnel(): WhetherItGoesThroughTheTunnel
    {
        return $this->tunnel;
    }
}
