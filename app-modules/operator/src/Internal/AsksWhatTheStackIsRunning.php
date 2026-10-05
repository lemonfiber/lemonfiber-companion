<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Operator\Internal\Presenters\HowAListingReads;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

/**
 * The one reading two screens are both about, written once.
 *
 * There are two frames now — the list of what a machine runs, and the one
 * thing behind a row of it — and both are about the same listing. Two copies of
 * *resume, ask, fold* is two chances for them to come to different answers
 * about what a stack said, and an operator moving between the two frames is
 * exactly who would meet the difference.
 *
 * **A trait rather than a collaborator**, for {@see LetsGoOfARefusedSession}'s
 * reason: there is no state here. What it needs is the screen's own store and
 * its own port, both of which the screen already holds, and a type handed out
 * to be called back would mean two constructors holding something that answers
 * one question.
 *
 * **It is handed the screen's own store and port rather than reaching for
 * them.** A trait that read `$this->storage` would be a coupling a trait cannot
 * declare, invisible to a reader looking at either end — and invisible to an
 * analyser too, which reads the property as one nothing uses. So the screen
 * keeps its own `answer()`, three lines of it, and hands over what it holds.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AsksWhatTheStackIsRunning
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatThisStackRunsTurnedOutToBe $answered = null;

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Named for the act rather than for the field it fills, because the two
     * screens hold it under the same name and mean the same thing by it.
     */
    private function askWhatIsRunning(
        Stack $stack,
        SecureStorage $storage,
        Supervising $supervising,
    ): WhatThisStackRunsTurnedOutToBe {
        return $storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackRunsTurnedOutToBe
                => $this->whatItSaid($stack, $session, $supervising),
            notHeld: static fn(): WhatThisStackRunsTurnedOutToBe
                => new HowAListingReads()->signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function whatItSaid(
        Stack $stack,
        Session $session,
        Supervising $supervising,
    ): WhatThisStackRunsTurnedOutToBe {
        return $supervising->running($stack, $session)->either(
            these: static fn(Daemons $daemons): WhatThisStackRunsTurnedOutToBe
                => new HowAListingReads()->these($daemons),
            met: $this->lettingGoIfRefused($stack, new HowAListingReads()->met(...)),
        );
    }
}
