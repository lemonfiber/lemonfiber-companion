<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\StackId;
use Modules\Operator\Internal\ViewModels\WhatTheLaunchWas;

/**
 * What the app found when it opened, as the fields a template reads.
 *
 * `F2`: data in, view model out. The four methods are the four arms
 * `N1-R37` insists on, so a test states a launch and reads a screen rather
 * than arranging a device and a network to produce one.
 */
final readonly class HowTheLaunchReads
{
    /** The device would not let the operator in, and nothing was tried (`N4-R19`). */
    public function locked(): WhatTheLaunchWas
    {
        return new WhatTheLaunchWas(isLocked: true, isPaired: false, met: '', remedy: '', opensOn: '');
    }

    /** No machine is paired, which is a first run rather than a fault (`N1-R35`). */
    public function unpaired(): WhatTheLaunchWas
    {
        return new WhatTheLaunchWas(isLocked: false, isPaired: false, met: '', remedy: '', opensOn: '');
    }

    /**
     * Something stood between the app and the machine it is paired with.
     *
     * Both keys come off the obstacle, which owns them: this is a screen's
     * flattening of a value, not a second place the catalogue is named. An
     * obstacle gaining a seventh case therefore needs no edit here, and cannot
     * be given a sentence here that disagrees with the one another screen shows.
     */
    public function blockedBy(Obstacle $why): WhatTheLaunchWas
    {
        return new WhatTheLaunchWas(
            isLocked: false,
            isPaired: true,
            met: $why->said(),
            remedy: $why->remedy(),
            opensOn: '',
        );
    }

    /** A machine is paired and ready to be asked (`N1-R36`). */
    public function readyFor(StackId $stack): WhatTheLaunchWas
    {
        return new WhatTheLaunchWas(isLocked: false, isPaired: true, met: '', remedy: '', opensOn: $stack->stored());
    }
}
