<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What stands between one long-running command and the machine.
 *
 * The words are the stack's own, from the `hosting` envelope, so a value this
 * app cannot read is refused where the payload is read rather than guessed at —
 * {@see Waiting}'s argument, for the same reason.
 *
 * **Three of these are not two.** A screen showing *comes back* and *does not*
 * would have to put four of these six on one side and two on the other, and
 * every way of doing that is wrong. `Unsupported` is the platform having no
 * manager this product configures, which reads as *off* and invites switching
 * it on — a thing nobody can do here. `InstalledUnverified` is the manager
 * declining to say, which reads as *running* and is the one an operator most
 * needs to see. So the questions are asked separately and none of them answers
 * for another: {@see comesBackOnItsOwn()}, {@see didNotComeBack()} and
 * {@see cannotBePromisedHere()} are each false about the case that is not
 * theirs, and a case none of them claims is a case a screen has to say
 * something about rather than let fall through.
 */
enum HowItIsHosted: string
{
    /** Nothing is installed; it runs only while a terminal holds it. */
    case NotHosted = 'not-hosted';

    /** Installed, and the manager confirms it is running. */
    case Hosted = 'hosted';

    /** Installed, and the manager would not say whether it is running. */
    case InstalledUnverified = 'installed-unverified';

    /** Installed, and the manager says it is not running. */
    case Stopped = 'stopped';

    /** Installed against a program that is no longer there. */
    case Orphaned = 'orphaned';

    /** This platform has no service manager lemonfiber configures. */
    case Unsupported = 'unsupported';

    /**
     * Whether the machine brings this back without anybody being there.
     *
     * Only the one case, and that is the point. `InstalledUnverified` is
     * installed and unconfirmed, which is the answer that looks most like this
     * one and is not it — a screen treating *the manager would not say* as
     * *yes* is the silence this whole area exists to refuse.
     */
    public function comesBackOnItsOwn(): bool
    {
        return $this === self::Hosted;
    }

    /**
     * Whether this is installed and is not running.
     *
     * `Orphaned` counts because it cannot run: the definition is installed and
     * names a program that is not there any more. Naming what did not come back
     * means naming that one too, and a screen listing only `Stopped` would show
     * an operator a shorter list than the truth.
     *
     * `NotHosted` does not count. Nothing was installed, so nothing failed to
     * come back — it is a command that only ever ran while a terminal held it,
     * and reporting it beside a service that died would be inventing a failure.
     */
    public function didNotComeBack(): bool
    {
        return $this === self::Stopped || $this === self::Orphaned;
    }

    /**
     * Whether this machine cannot be made to do it at all.
     *
     * Asked separately so a screen can say *not available here* instead of
     * drawing the same empty box it draws for *off*. Off invites switching on;
     * there is nothing here to switch.
     */
    public function cannotBePromisedHere(): bool
    {
        return $this === self::Unsupported;
    }

    /**
     * What this is called on a screen, as a key.
     *
     * Built from the case, which is how every word in this app reaches the
     * catalogue — see {@see Conclusion::saidOnTheScreen()} for the argument.
     */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.hosting.%s', $this->value);
    }
}
