<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowOftenAScreenLooks;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen showing what changes on its own but slowly, which reads its stack again once a minute while it is open.
 *
 * What is read again is the screen's own reading, which is what `again()`
 * lets go of; anything else the screen holds it keeps.
 *
 * @phpstan-require-extends NativeComponent
 */
trait LooksAgainWhileOpen
{
    /** Let go of the screen's reading, so the next frame reads it again. */
    abstract public function again(): void;

    /** Look again, on the cadence of what changes slowly. */
    #[Poll(HowOftenAScreenLooks::WHILE_OPEN_MS)]
    public function whileOpen(): void
    {
        $this->again();
    }
}
