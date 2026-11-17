<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

final class TunedIsUnreadable extends InvalidArgumentException
{
    /** A field arrived missing, or as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The tuned payload carried no readable `%s`.', $field->value));
    }

    /** It named a stream that is not one a player could be handed: nowhere to play it, an address off the door, or no door to pin. */
    public static function notAStream(): self
    {
        return new self('The tuned payload named a live stream no player could be handed.');
    }
}
