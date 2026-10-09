<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\KnowingThisDevice;
use Modules\Kernel\Api\ThisDevice;

/** An install that has already drawn its id, and answers it every time. */
final readonly class ADeviceWithAnId implements KnowingThisDevice
{
    private function __construct(private ThisDevice $device) {}

    public static function named(string $id): self
    {
        return new self(ThisDevice::named($id));
    }

    public function thisDevice(): ThisDevice
    {
        return $this->device;
    }
}
