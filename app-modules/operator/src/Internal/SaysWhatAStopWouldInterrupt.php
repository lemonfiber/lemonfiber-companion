<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\AskingWhatAStopWouldInterrupt;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheDownloadsComingDown;
use Modules\Kernel\Api\WhatAStopReaches;
use Modules\Operator\Internal\Presenters\HowWhatIsComingDownReads;
use Modules\Operator\Internal\ViewModels\WhatIsComingDownAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen that asks, as an action that stops services is put to the operator, what is still coming down inside it.
 *
 * @phpstan-require-extends NativeComponent
 */
trait SaysWhatAStopWouldInterrupt
{
    /** What the action asked about would interrupt, held until the question is put away. Public, as a screen's state is. */
    public ?WhatIsComingDownAsShown $comingDown = null;

    abstract public function stack(): Stack;

    /** Ask what stopping that would interrupt, and hold the answer beside the question. */
    private function askWhatComesDownInside(WhatAStopReaches $reach, SecureStorage $storage, AskingWhatAStopWouldInterrupt $asking): void
    {
        $stack = $this->stack();

        $this->comingDown = $storage->resume($stack->id())->either(
            held: fn(Session $session): WhatIsComingDownAsShown => $asking->inside($stack, $session, $reach)->either(
                said: static fn(TheDownloadsComingDown $coming): WhatIsComingDownAsShown => new HowWhatIsComingDownReads()->this($coming),
                met: $this->lettingGoIfRefused($stack, static fn(): WhatIsComingDownAsShown => new HowWhatIsComingDownReads()->unknown()),
            ),
            notHeld: static fn(): WhatIsComingDownAsShown => new HowWhatIsComingDownReads()->unknown(),
        );
    }
}
