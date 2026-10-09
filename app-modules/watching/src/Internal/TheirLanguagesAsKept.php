<?php

declare(strict_types=1);

namespace Modules\Watching\Internal;

use function array_key_exists;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;

use Modules\Kernel\Api\HearIn;
use Modules\Kernel\Api\ReadIn;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\TheirLanguages;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\Whose;

/**
 * A member's choice of languages, as the phone writes it before sealing it and reads it back after.
 *
 * **Written in {@see Shape::One}**: whose choice it is, by the identifier the
 * keychain keeps them under, and the word each language is kept under.
 *
 * **Read back only for the member it was written for.** Somebody else signed
 * in to the same house chose nothing yet, and is not handed another member's
 * choice. Anything that does not read is nothing, and a title then plays as
 * it comes.
 */
final readonly class TheirLanguagesAsKept
{
    private const string WHOSE = 'chosen_by';

    private const string HEAR = 'hears_in';

    private const string READ = 'reads_in';

    /** The choice as a value to seal, or nothing where it cannot be written. */
    public static function written(Whose $whose, TheirLanguages $chosen): ?Unsealed
    {
        $written = json_encode([
            self::WHOSE => $whose->forTheStore(),
            self::HEAR => $chosen->hear()->value,
            self::READ => $chosen->read()->value,
        ]);

        return $written === false ? null : Unsealed::of($written);
    }

    /** The choice a value written in this shape holds for this member, or nothing where it holds none for them. */
    public static function read(Shape $shape, Unsealed $value, Whose $whose): ?TheirLanguages
    {
        return match ($shape) {
            Shape::One => self::inShapeOne(json_decode($value->inTheClear(), associative: true), $whose),
        };
    }

    private static function inShapeOne(mixed $written, Whose $whose): ?TheirLanguages
    {
        if (! is_array($written) || self::textAt($written, self::WHOSE) !== $whose->forTheStore()) {
            return null;
        }

        $hear = HearIn::tryFrom(self::textAt($written, self::HEAR));
        $read = ReadIn::tryFrom(self::textAt($written, self::READ));

        return $hear instanceof HearIn && $read instanceof ReadIn ? TheirLanguages::of($hear, $read) : null;
    }

    /** @param array<array-key, mixed> $written */
    private static function textAt(array $written, string $field): string
    {
        return array_key_exists($field, $written) && is_string($written[$field]) ? $written[$field] : '';
    }
}
