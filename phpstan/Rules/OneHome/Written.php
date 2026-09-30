<?php

declare(strict_types=1);

namespace Lemonfiber\Companion\PHPStan\Rules\OneHome;

use function in_array;
use function mb_strlen;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function str_starts_with;

/**
 * A constant value as the analyser describes it, the same for every constant
 * that holds it, and whether it is too plain to have a home.
 *
 * Nothing, zero, one, two, a boolean, a single character and an empty array
 * say nothing a reader would look up, for the reason `D6` permits 0, 1 and 2:
 * their names would be the value.
 */
final readonly class Written
{
    /** The values too plain to have a home, as they are written. */
    private const array PLAIN = ["''", '0', '1', '2', '0.0', '1.0', 'true', 'false', 'null', 'array{}'];

    /** One character between its quotes. */
    private const int ONE_CHARACTER = 3;

    public static function of(Type $type): string
    {
        return $type->describe(VerbosityLevel::precise());
    }

    public static function isPlain(string $written): bool
    {
        return in_array($written, self::PLAIN, strict: true)
            || (mb_strlen($written) === self::ONE_CHARACTER && str_starts_with($written, "'"));
    }
}
