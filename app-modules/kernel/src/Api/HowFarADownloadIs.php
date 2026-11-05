<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How far along a download can be, from none of it to all of it.
 */
final readonly class HowFarADownloadIs
{
    /** The furthest a download can be along, which is finished. */
    public const int ALL_OF_IT = 100;

    /** Whether a share is between none of a download and all of it. */
    public static function couldBe(int $progress): bool
    {
        return $progress >= 0 && $progress <= self::ALL_OF_IT;
    }
}
