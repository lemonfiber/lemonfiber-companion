<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/** A privileged shape lemonfiber writes for a plugin's service, beyond the entry every plugin's service gets. */
enum APrivilegedShape: string
{
    case EgressGuard = 'egress-guard';

    /** Why a service takes it, as a catalogue key. */
    public function neededFor(): string
    {
        return sprintf('plugins.shape.%s', $this->value);
    }
}
