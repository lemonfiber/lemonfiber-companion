<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Operator\Internal\Presenters\HowWhatSetupSettledReads;
use Modules\Operator\Internal\ViewModels\WhatSetupSettledAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen of a stack that asks it what setup settled, and lets go of a session the stack refused.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AsksWhatSetupSettled
{
    /** What setup settled, as the screen draws it. */
    private function whatSetupSettled(): WhatSetupSettledAsShown
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatSetupSettledAsShown => $this->askingSettled->settled($stack, $session)->either(
                said: static fn(WhatSetupSettled $settled): WhatSetupSettledAsShown => new HowWhatSetupSettledReads()->this($settled),
                met: $this->lettingGoIfRefused($stack, new HowWhatSetupSettledReads()->met(...)),
            ),
            notHeld: static fn(): WhatSetupSettledAsShown => new HowWhatSetupSettledReads()->signedOut(),
        );
    }
}
