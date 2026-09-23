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
 * **Six words, not two buckets.** A screen showing *comes back* and *does not*
 * would have to put four of these on one side and two on the other, and every
 * way of doing that is wrong: `Unsupported` is the platform having no manager
 * this product configures, which reads as *off* and invites switching on a
 * thing nobody can switch, and `InstalledUnverified` is the manager declining
 * to say, which reads as *running* and is the one an operator most needs to
 * see. So each case carries its own sentence and a screen draws the word.
 *
 * **One question is asked of it, because one is acted on.**
 * {@see didNotComeBack()} is what a listing counts and a screen says out loud,
 * and it is the only fact here that is not simply the word. *Comes back* and
 * *not available here* were predicates too until a review found that nothing
 * outside their own tests asked them — the screen draws all six words, which
 * is a better answer than three buckets and made them an abstraction over a
 * question nobody had.
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
