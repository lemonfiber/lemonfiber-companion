<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function sprintf;

/**
 * An address a stand-in hands out, under a name reserved so that it resolves
 * nowhere.
 *
 * `.invalid` is reserved by RFC 2606. Pairing material that could reach a real
 * stack is refused, and an address that cannot be resolved is the strongest
 * form of that: were a request ever to escape the stand-in, the failure would
 * be a name that does not exist rather than a connection to somebody's actual
 * machine. The port is the one a stack serves on, so the address reads as a
 * stack's would.
 */
final readonly class AnAddressThatResolvesNowhere
{
    /** The address, the machine's name going in at `%s`. */
    private const string WRITTEN_AS = 'https://%s.invalid:8443';

    /** The address of the machine by this name. */
    public static function of(string $machine): string
    {
        return sprintf(self::WRITTEN_AS, $machine);
    }
}
