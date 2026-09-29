<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A catalogue entry arrived with a word blank.
 *
 * Refused rather than drawn, because the entry exists to say what a service
 * is for and what the house goes without; one that leaves either out is a
 * name and a gap, which is the list of software this is meant not to be.
 */
final class CatalogueSaysNothing extends InvalidArgumentException
{
    /** The field is named, because a catalogue is long and a refusal naming none is no help. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A catalogue entry arrived with its `%s` blank, and an entry that will not say it reads as complete to somebody deciding whether it matters.',
            $field,
        ));
    }
}
