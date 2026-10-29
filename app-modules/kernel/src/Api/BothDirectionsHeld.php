<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** What a download client answered it holds in each direction: down, and up. */
final readonly class BothDirectionsHeld
{
    private function __construct(private ADirectionHeld $down, private ADirectionHeld $up) {}

    public static function of(ADirectionHeld $down, ADirectionHeld $up): self
    {
        return new self($down, $up);
    }

    /** What it holds coming down. */
    public function down(): ADirectionHeld
    {
        return $this->down;
    }

    /** What it holds going up. */
    public function up(): ADirectionHeld
    {
        return $this->up;
    }
}
