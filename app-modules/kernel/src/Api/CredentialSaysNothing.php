<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A reading of the credentials a stack holds arrived with a word it owes left blank.
 *
 * Refused rather than shown: a credential with no name, or a consumer with no
 * name, is a row that says something depends on it without saying what.
 */
final class CredentialSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because the list can be long. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'A reading of the credentials a stack holds arrived with its `%s` blank, and a credential that will not say it cannot be told apart from the next one.',
            $field,
        ));
    }
}
