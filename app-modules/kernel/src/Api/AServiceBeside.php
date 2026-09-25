<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * A service the household can reach that is not the front door, what it is to them, and why it is not the door.
 */
final readonly class AServiceBeside
{
    private function __construct(
        private string $service,
        private WhatItFaces $facing,
        private string $because,
        private AnAddressToHand $address,
    ) {}

    /** What the stack said of one service beside the door; the service and the reason are required. */
    public static function said(string $service, WhatItFaces $facing, string $because, AnAddressToHand $address): self
    {
        foreach (['service' => $service, 'because' => $because] as $field => $said) {
            if (trim($said) === '') {
                throw TheDoorSaysNothing::about($field);
            }
        }

        return new self($service, $facing, $because, $address);
    }

    /** The service, by the name it shows itself under. */
    public function service(): string
    {
        return $this->service;
    }

    /** What it is to the household. */
    public function facing(): WhatItFaces
    {
        return $this->facing;
    }

    /** Why it is not somewhere to begin. */
    public function because(): string
    {
        return $this->because;
    }

    /** Where it is reached, or none. */
    public function address(): AnAddressToHand
    {
        return $this->address;
    }
}
