<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

/**
 * A paired stack written down, and the session held for it settled.
 *
 * Pairing a machine this device already holds replaces what is held for it:
 * its address, its pinned certificate and its name. What happens to the
 * session depends on one of those. A session was granted over the pinned
 * certificate's key, and a different certificate is a different key, so a
 * re-pairing that changes the pin lets go of the session and the operator signs
 * in again. One that keeps the pin keeps the session, because nothing it was
 * agreed under has changed.
 *
 * **Both pairing roads come through here.** Deciding it on each screen would be
 * the same rule written twice, and the copy nobody tests is the one that keeps
 * a session across a new certificate.
 *
 * **The session goes only once the new pin is written.** Were the write
 * refused, the device would still hold the old certificate, the session agreed
 * under it would still be good, and letting it go would sign the operator out
 * of a machine whose pairing never changed.
 */
final readonly class Remembering
{
    public function __construct(
        private Stacks $stacks,
        private SecureStorage $sessions,
    ) {}

    /** Write the stack down, letting go of its session where its certificate changed. */
    public function stack(Stack $stack): Remembered
    {
        $repinned = $this->stacks->configured()->wouldRepin($stack);

        return $this->stacks->remember($stack)->either(
            remembered: function () use ($stack, $repinned): Remembered {
                if ($repinned) {
                    $this->sessions->forget($stack->id());
                }

                return Remembered::safely();
            },
            refused: static fn(WhyAStackCannotBeRemembered $why): Remembered => Remembered::refused($why),
        );
    }
}
