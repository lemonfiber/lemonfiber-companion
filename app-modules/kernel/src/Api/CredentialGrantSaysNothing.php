<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A grant arrived with a blank where the credential or the capability it fills belongs.
 *
 * Refused rather than shown: a grant is a credential crossing to somebody
 * else's plugin, and a row that does not say which credential, or what for,
 * reads as accounted for and is not.
 */
final class CredentialGrantSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named. */
    public static function about(string $field): self
    {
        return new self(sprintf('A grant arrived with its `%s` blank, and a credential crossing to a plugin that will not say which, or what for, is not one to show.', $field));
    }
}
