<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which of the two keys the seal is asking for.
 *
 * Two keys rather than one used twice, because they answer different
 * questions and must not be able to answer each other's: one seals what is
 * kept, and one hashes which stack it is kept for.
 */
enum SealKey
{
    /** The key every kept value is encrypted under. */
    case TheDataKey;

    /** The key a stack's identity is hashed under, so a row names it without saying it. */
    case TheStackKey;
}
