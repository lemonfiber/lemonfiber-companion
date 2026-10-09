<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use Lemonfiber\Sdk\Contract\Api;

use function rawurldecode;

/**
 * The read of one title on a shelf, as a stack declares it among what it serves.
 *
 * The path is the SDK's own, with the contract's placeholder where the
 * title's id goes: a stack declares the read once for every title, and this
 * is what a request for any one of them is asked about.
 */
final readonly class OneTitle
{
    /** The contract's placeholder for the id a title is read by. */
    private const string ANY = '{id}';

    public static function asDeclared(): string
    {
        return rawurldecode(Api::title(self::ANY));
    }
}
