<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function hash;
use function is_array;

use Modules\Sdk\Api\Fields\GrantField;

/**
 * A grant a stand-in hands out, as a reader takes it and as a door would.
 *
 * The `grant` envelope's own declaration, corrected in the two fields it types
 * only as text: a synthesised token is the word `Token`, which no door takes,
 * and a synthesised day is the words `Lasts until`. The token becomes a
 * digest and the day {@see LongAfterAnyRun}'s, so a stand-in's grant has never
 * lapsed.
 */
final readonly class AGrantAsItReads
{
    /** What the token is drawn from: an XXH128 digest is thirty-two hexadecimal digits, as a door's token is. */
    private const string DRAWN_FROM = 'a stand-in grant';

    /**
     * The envelope, corrected.
     *
     * @param  array<string, mixed> $envelope the envelope as synthesised from the declaration
     * @return array<string, mixed>
     */
    public static function from(array $envelope): array
    {
        $data = $envelope['data'];
        // One line: `data` is always an array.
        $grant = is_array($data) ? $data : [];
        $envelope['data'] = [
            ...$grant,
            GrantField::Token->value => hash('xxh128', self::DRAWN_FROM),
            GrantField::LastsUntil->value => LongAfterAnyRun::DAY,
        ];

        return $envelope;
    }
}
