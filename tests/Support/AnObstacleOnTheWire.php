<?php

declare(strict_types=1);

namespace Tests\Support;

use Modules\Kernel\Api\AnObstacleNamed;
use Modules\Kernel\Api\Code;
use Modules\Kernel\Api\Remedy;
use Modules\Kernel\Api\Sentence;

/**
 * Something in the way of one part of an answer, as a stack writes it and as the app reads it: a code, a sentence and a remedy.
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

    /** The same obstacle, as the app reads it. */
    public static function read(string $said): AnObstacleNamed
    {
        return AnObstacleNamed::of(Code::of(self::A_CODE), Sentence::of($said), Remedy::of(self::A_REMEDY));
    }
}
