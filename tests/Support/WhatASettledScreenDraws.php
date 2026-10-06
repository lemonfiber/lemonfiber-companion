<?php

declare(strict_types=1);

namespace Tests\Support;

use function array_filter;
use function array_keys;
use function array_values;

use Closure;

use function is_int;

use Native\Mobile\Edge\NativeComponent;

/**
 * What a screen draws once it has settled, the way a person sees it after one round trip.
 *
 * A frame that asked the stack what it serves draws only the platform's
 * indicator, says nothing, and asks for the next frame at once; the frame after
 * it is what is drawn here. A screen that never waits is drawn as its first
 * frame, by {@see WhatTheDeviceWouldDraw::by()}.
 */
final class WhatASettledScreenDraws
{
    public static function of(NativeComponent $screen): WhatTheDeviceWouldDraw
    {
        $drawn = WhatTheDeviceWouldDraw::by($screen);

        // Sixteen milliseconds is the one frame `the-next-frame` asks for.
        foreach ([2, 3] as $frame) {
            if ($drawn->said() === [] && self::howSoonItAskedForTheNextFrame($screen) === [16]) {
                $drawn = WhatTheDeviceWouldDraw::by($screen);
            }
        }

        return $drawn;
    }

    /**
     * How soon the frame drawn last asked to be drawn again, in milliseconds, if it asked.
     *
     * Read off the package's own record of the intervals a template declared,
     * which it keeps to itself, because that record is what wakes the next frame.
     *
     * @return list<int>
     */
    private static function howSoonItAskedForTheNextFrame(NativeComponent $screen): array
    {
        $asked = Closure::bind(static fn(NativeComponent $drawn): array => array_keys($drawn->bladePollDeadlines), null, NativeComponent::class)($screen);

        return array_values(array_filter($asked, is_int(...)));
    }
}
