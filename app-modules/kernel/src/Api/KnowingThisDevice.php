<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The id this install plays under, drawn the first time it is asked for and kept.
 *
 * One for the whole install rather than one per stack: it names the device a
 * member plays on, and every stack is told the same device. A phone that can
 * keep nothing draws a new one each launch, and the sessions left behind lapse.
 */
interface KnowingThisDevice
{
    public function thisDevice(): ThisDevice;
}
