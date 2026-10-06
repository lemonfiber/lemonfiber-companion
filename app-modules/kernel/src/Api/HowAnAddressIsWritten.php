<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which kind of name an address reaches its machine by, and nothing of the
 * name itself.
 *
 * What a developer reading a failed reach needs and an address never
 * leaves the process to say: a numeric address, a name only the local network
 * answers for, or a name the wider world's name service does. The three fail
 * in different ways, and saying which is not saying where.
 */
enum HowAnAddressIsWritten: string
{
    /** A numeric address, which needs no name looked up. */
    case Numeric = 'numeric';

    /** A name ending in `.local`, which only the local network answers for. */
    case LocalName = 'local_name';

    /** Any other name, looked up through the name service. */
    case Named = 'named';
}
