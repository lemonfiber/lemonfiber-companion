<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Something in the way of one part of an answer, as a stack writes it: a code, a sentence and a remedy.
 */
final readonly class AnObstacleOnTheWire
{
    /** The code a stand-in obstacle is named by. */
    public const string A_CODE = 'LF-MEDIA-001';

    /** What a stand-in obstacle says to do about it. */
    public const string A_REMEDY = 'Look again once the media server is back.';

    /** @return array{code: string, said: string, remedy: string} */
    public static function saying(string $said): array
    {
        return ['code' => self::A_CODE, 'said' => $said, 'remedy' => self::A_REMEDY];
    }
}
