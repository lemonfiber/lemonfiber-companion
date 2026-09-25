<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use BackedEnum;

/**
 * An enum whose cases are names on the wire, each case's value the name itself.
 *
 * {@see WireField} for the words several envelopes share, and one enum per
 * envelope under `Fields` for the rest. A reader takes any of them where it
 * takes a field, so a helper does not care which enum a name lives in.
 *
 * @property-read string $value the name on the wire
 */
interface NamesAWireField extends BackedEnum
{
    /** This field's name as a path, where it is read off another field's value. */
    public function under(self $parent): string;
}
