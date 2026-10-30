<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** The address handed over as a code, with how they sign in on their device and the apps that reach the server. */
final readonly class ACodeToHandOver
{
    /**
     * @param list<list<bool>>     $squares the address as a code, rows of squares dark where true, or none where it could not be drawn
     * @param string               $address the address the code carries
     * @param string               $caution what is worth knowing about that address, or empty
     * @param list<string>         $steps   how they sign in on their device, in order
     * @param list<AClientAsShown> $clients every app a device can be pointed at the server with
     */
    public function __construct(
        public array $squares,
        public string $address,
        public string $caution,
        public array $steps,
        public array $clients,
    ) {}
}
