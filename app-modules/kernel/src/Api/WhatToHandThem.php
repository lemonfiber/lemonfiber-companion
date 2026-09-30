<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a hand-off gives the person: the address the code carries, the steps on their device, and the apps to use.
 *
 * Held together because they are one thing handed over, and read together:
 * the steps name the address, and each app's code carries it.
 */
final readonly class WhatToHandThem
{
    private function __construct(
        private AnAddressToHand $address,
        private TheStepsOnTheirDevice $steps,
        private TheClientsToHandOver $clients,
    ) {}

    /** These, as the stack gave them. */
    public static function of(AnAddressToHand $address, TheStepsOnTheirDevice $steps, TheClientsToHandOver $clients): self
    {
        return new self($address, $steps, $clients);
    }

    /** The address the code carries, or none. */
    public function address(): AnAddressToHand
    {
        return $this->address;
    }

    /** How they sign in on their device, in order. */
    public function steps(): TheStepsOnTheirDevice
    {
        return $this->steps;
    }

    /** Every app a device can be pointed at the server with. */
    public function clients(): TheClientsToHandOver
    {
        return $this->clients;
    }
}
