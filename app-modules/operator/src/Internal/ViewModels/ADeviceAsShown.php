<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One kind of device and the app recommended for it, flattened for a template.
 */
final readonly class ADeviceAsShown
{
    /**
     * @param string $device      what somebody would call the device
     * @param string $client      what to use on it
     * @param string $supportSaid the catalogue key for how well it is served
     * @param string $caution     what is worth knowing before starting, or empty
     * @param string $instead     what to use instead, or empty
     */
    public function __construct(
        public string $device,
        public string $client,
        public string $supportSaid,
        public string $caution,
        public string $instead,
    ) {}
}
