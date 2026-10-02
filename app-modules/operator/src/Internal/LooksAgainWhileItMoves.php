<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen showing what moves second by second, which reads its stack again every few seconds while it is open.
 *
 * What is read again is the screen's own reading, which is what `again()`
 * lets go of; anything else the screen holds it keeps.
 *
 * @phpstan-require-extends NativeComponent
 */
trait LooksAgainWhileItMoves
{
    /** Let go of the screen's reading, so the next frame reads it again. */
    abstract public function again(): void;

    /** Look again, on the cadence of what moves. */
    #[Poll(HowOftenAScreenLooks::WHILE_IT_MOVES_MS)]
    public function whileItMoves(): void
    {
        $this->again();
    }
}
