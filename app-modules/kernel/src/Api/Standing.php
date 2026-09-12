<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a problem stands with respect to being fixed.
 *
 * Named for what it holds rather than `State`, which is a suffix the
 * architecture rules refuse by name: a closed set called `State` is the one
 * most likely to grow into a bag of unrelated flags.
 *
 * The five are the server's own. The distinction that earns its keep is
 * `Actionable` against `Guided`: one puts a button on the screen and the other
 * puts instructions on it, and a screen that confuses them offers to do
 * something it cannot do.
 */
enum Standing: string
{
    /** A remedy is available here. */
    case Actionable = 'actionable';

    /** The operator must act, somewhere else. */
    case Guided = 'guided';

    /** lemonfiber can fix this itself. */
    case Remediable = 'remediable';

    /** No known remedy; escalation is offered instead. */
    case Unknown = 'unknown';

    /** Acknowledged, and not re-shown until it recurs. */
    case Suppressed = 'suppressed';

    /**
     * Whether a screen may offer to act on this itself.
     *
     * `Guided` is the case this exists to keep out. A remedy that reads as an
     * instruction — check the router, plug the drive back in — is not one this
     * application can carry out, and a button that claims otherwise fails in
     * front of somebody who then has to work out what actually happened.
     */
    public function offersAButton(): bool
    {
        return match ($this) {
            self::Actionable, self::Remediable => true,
            self::Guided, self::Unknown, self::Suppressed => false,
        };
    }
}
