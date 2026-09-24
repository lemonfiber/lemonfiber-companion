<?php

declare(strict_types=1);

namespace Modules\Sdk\Api\Fields;

use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SaysWhereItSits;

/**
 * What the wire calls each field of the `provenance` envelope that no other envelope this module reads carries.
 *
 * {@see \Modules\Sdk\Api\WireField} holds the words more than one envelope
 * carries; this holds the rest of this one's.
 */
enum ProvenanceField: string implements NamesAWireField
{
    use SaysWhereItSits;

    /** The image a service runs, without a version. */
    case Image = 'image';

    /**
     * The exact version a stack pins a service at.
     *
     * Its own field rather than part of the image reference, so a caller
     * comparing versions is not parsing them out of a string.
     */
    case Pinned = 'pinned';

    /** The project a service is built from, where its licence is checked. */
    case Upstream = 'upstream';

    /**
     * The licence a service is published under, as an SPDX identifier.
     *
     * Spelled as the wire spells it, which is the American way; the kernel's
     * word is `licence` and this case is the one place the two meet.
     */
    case License = 'license';
}
