<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Throwable;

/**
 * How far along a download is, from none of it to all of it.
 */
final readonly class HowFarADownloadIs
{
    /** The furthest a download can be along, which is finished. */
    public const int ALL_OF_IT = 100;

    private function __construct(public int $share) {}

    /** That share of a download; one outside none of it to all of it is refused with what the caller says of it. */
    public static function at(int $share, Throwable $refused): self
    {
        if ($share < 0 || $share > self::ALL_OF_IT) {
            throw $refused;
        }

        return new self($share);
    }
}
