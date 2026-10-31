<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;

use function is_string;

use Modules\Kernel\Api\ACertificateReplacement;
use Modules\Kernel\Api\ACertificateReplacementAgreed;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAReplacementCameTo;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\Presenters\HowAReplacementReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\TheReplacementTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhoIsWatchingAsShown;

/**
 * Replacing the media server's internal certificate from the credentials screen: read first, agreed to, and followed.
 *
 * The reading names, in the stack's words, every service handed the new
 * certificate's SHA-256 and whether the media server restarts, beside who is
 * watching on it now. The yes carries the offer the reading named; where that
 * has moved, what the stack said is kept and the replacement is read again
 * for a fresh yes. No value crosses either way: the core makes the
 * certificate and its key on the machine.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\ReplacingTheInternalCertificate}
 * as a protected `$replacingIt`, {@see \Modules\Kernel\Api\FollowingWhatIsPlaying}
 * as a protected `$playing` and {@see \Modules\Kernel\Api\SecureStorage} as a
 * private `$storage`, and holds every credential as a public `$held`.
 */
trait ReplacesTheInternalCertificate
{
    /** Whether the internal certificate is being replaced. */
    public bool $replacing = false;

    /** What the stack read of replacing it, which is what a yes agrees to. */
    public ?ACertificateReplacement $replacement = null;

    /** The handle of the replacement being followed, while there is one. */
    public ?string $followingTheReplacement = null;

    /** Where replacing it has got to, once this frame has asked. Public for {@see Screens\WhatIsRunningHere::$answered}'s reason. */
    public ?TheReplacementTurnedOutToBe $replacementGoing = null;

    /** Who is watching on the media server, as it stood when the replacement was read. */
    public ?WhoIsWatchingAsShown $watching = null;

    /** What the stack said where the reading a yes was given for had moved, until the next yes. */
    public ?ARefusalAsShown $movedOn = null;

    /** Ask what replacing the internal certificate would do, with who is watching now; while work runs, nothing is asked. */
    public function replaceTheCertificate(): void
    {
        if ($this->workRuns()) {
            return;
        }

        $this->backToTheCredentials();
        $this->replacing = true;
        $this->readTheReplacement();
    }

    /** Replace it as the reading on the screen described; anywhere else, nothing is sent. */
    public function agreeToReplace(): void
    {
        $reading = $this->replacement;

        if (! $this->replacing || ! $reading instanceof ACertificateReplacement || ! $reading->isAReading()) {
            return;
        }

        $agreed = ACertificateReplacementAgreed::after($reading);
        $this->replacement = null;
        $this->movedOn = null;
        $this->replacementGoing = $this->putTheReplacement(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->replacingIt->replace($stack, $session, $agreed));
    }

    /** Where replacing it has got to, asked once per frame. */
    public function howTheReplacementGoes(): TheReplacementTurnedOutToBe
    {
        $following = $this->followingTheReplacement;

        return $this->replacementGoing ??= $following === null
            ? new HowAReplacementReads()->notAsked()
            : $this->putTheReplacement(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->replacingIt->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** Leave replacing it, keeping nothing of what was read. */
    public function leaveTheReplacement(): void
    {
        $this->replacing = false;
        $this->replacement = null;
        $this->followingTheReplacement = null;
        $this->replacementGoing = null;
        $this->watching = null;
        $this->movedOn = null;
    }

    /** Leave whatever is open, back to the credentials as last read. */
    abstract public function backToTheCredentials(): void;

    /** Whether work the screen asked of the stack is still being followed. */
    private function workRuns(): bool
    {
        return is_string($this->following) || is_string($this->followingTheReplacement);
    }

    /** Ask who is watching and what replacing it would do. */
    private function readTheReplacement(): void
    {
        $this->watching = $this->whoIsWatching();
        $this->replacementGoing = $this->putTheReplacement(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->replacingIt->read($stack, $session));
    }

    /** Who is watching on the media server now, asked with the operator's session alone. */
    private function whoIsWatching(): WhoIsWatchingAsShown
    {
        $stack = $this->stack();
        $unread = new WhoIsWatchingAsShown(read: false, sessions: []);

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session, Whose $whose): WhoIsWatchingAsShown => $whose->either(
                operator: fn(): WhoIsWatchingAsShown => new HowAReplacementReads()->watching($this->playing->now($stack, $session)),
                member: static fn(): WhoIsWatchingAsShown => $unread,
            ),
            notHeld: static fn(): WhoIsWatchingAsShown => $unread,
        );
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<WhatAReplacementCameTo> $asking
     */
    private function putTheReplacement(Closure $asking): TheReplacementTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheReplacementTurnedOutToBe => $this->replacementShown($asking($stack, $session), $stack),
            notHeld: static fn(): TheReplacementTurnedOutToBe => new HowAReplacementReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * Every answer carries the credentials as they stand, which replace what
     * was read. A refusal reads them again. A yes refused because the reading
     * moved is no failure: the replacement is read again.
     *
     * @param WhatBecameOfTheWork<WhatAReplacementCameTo> $became
     */
    private function replacementShown(WhatBecameOfTheWork $became, Stack $stack): TheReplacementTurnedOutToBe
    {
        return $became->eitherOrMoved(
            underway: function (Job $job): TheReplacementTurnedOutToBe {
                $this->followingTheReplacement = $job->shown();

                return new HowAReplacementReads()->running();
            },
            answered: function (WhatAReplacementCameTo $came): TheReplacementTurnedOutToBe {
                $this->followingTheReplacement = null;
                $this->held = $came->held();
                $this->replacement = $came->replacement();

                return new HowAReplacementReads()->answered($came);
            },
            ended: function (): TheReplacementTurnedOutToBe {
                $this->followingTheReplacement = null;

                return new HowAReplacementReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheReplacementTurnedOutToBe {
                $this->followingTheReplacement = null;
                $this->held = null;

                return new HowAReplacementReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheReplacementTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowAReplacementReads()->met($why);
            },
            moved: function (ARefusalInItsWords $why): TheReplacementTurnedOutToBe {
                $this->followingTheReplacement = null;
                $this->movedOn = new HowARefusalReads()->inItsWords($why);
                $this->readTheReplacement();

                return $this->howTheReplacementGoes();
            },
        );
    }
}
