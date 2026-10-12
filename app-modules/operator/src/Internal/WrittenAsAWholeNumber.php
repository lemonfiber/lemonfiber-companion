<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function preg_match;

/**
 * Whether a field was typed as a whole number: digits and nothing else.
 */
final readonly class WrittenAsAWholeNumber
{
    /** Digits and nothing else. */
    private const string DIGITS = '/\A[0-9]+\z/';

    /** Whether this text is a whole number and nothing else. */
    public static function is(string $typed): bool
    {
        return preg_match(self::DIGITS, $typed) === 1;
    }
}
