<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A verb that is done only to a whole form was agreed to for one service.
 *
 * Refused where the values become an {@see AgreedTo}, for
 * {@see RequestWasRefusedForNothing}'s reason: this is a value that cannot be
 * constructed rather than a refusal crossing a boundary. The stack fetches
 * images by form, and the action that asks for it has no field a service could
 * travel under.
 */
final class OnlyAFormIsFetched extends InvalidArgumentException
{
    public static function named(WhatToDoWithIt $doing, ServiceId $service): self
    {
        return new self(sprintf(
            'The operator agreed to %s for the service %s, and that is done only to a whole form.',
            $doing->value,
            $service->named(),
        ));
    }
}
