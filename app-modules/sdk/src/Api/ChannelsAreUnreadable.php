<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

final class ChannelsAreUnreadable extends InvalidArgumentException
{
    /** A field arrived missing, or as another shape. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf('The channels payload carried no readable `%s`.', $field->value));
    }

    /** One channel could not be read, named by where it sat. */
    public static function channel(int $at): self
    {
        return new self(sprintf('Channel %d in the channels payload could not be read.', $at));
    }
}
