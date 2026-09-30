<?php

declare(strict_types=1);

namespace Modules\Design\View;

/**
 * Dark squares side by side in a row of a code, drawn as one and placed where they belong.
 *
 * Placed by its offset from where the code's middle would put it rather than
 * after the run before it: a phone rounds every element it lays out to its own
 * pixels, and a row laid out run after run gathers those roundings into a drift
 * no camera reads, where each run placed on its own is out by less than a pixel.
 * The middle, because that is where a stack lays each child it overlays.
 */
final readonly class ADarkRun
{
    /**
     * @param float $across how far right of the middle it is moved, in points
     * @param float $down   how far below the middle it is moved, in points
     * @param int   $width  how wide it is drawn, in points
     */
    public function __construct(public float $across, public float $down, public int $width) {}
}
