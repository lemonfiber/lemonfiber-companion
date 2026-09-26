<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A port lemonfiber wants for one of its services that something already here holds, and what holds it.
 */
final readonly class APortHeld
{
    private function __construct(
        private int $port,
        private string $wantedBy,
        private string $heldBy,
    ) {}

    /** What the stack said of one conflict; the service that wants it and what holds it are required. */
    public static function of(int $port, string $wantedBy, string $heldBy): self
    {
        foreach (['wanted_by' => $wantedBy, 'held_by' => $heldBy] as $field => $said) {
            if (trim($said) === '') {
                throw TheSurveySaysNothing::about($field);
            }
        }

        return new self($port, $wantedBy, $heldBy);
    }

    /** The host port both want. */
    public function port(): int
    {
        return $this->port;
    }

    /** The lemonfiber service that would publish it. */
    public function wantedBy(): string
    {
        return $this->wantedBy;
    }

    /** The project already holding it. */
    public function heldBy(): string
    {
        return $this->heldBy;
    }
}
