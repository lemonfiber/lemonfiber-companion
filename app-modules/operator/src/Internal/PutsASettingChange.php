<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;

/**
 * One change to the settings store put to the stack by `settings-set`: rehearsed, carried out on a yes to what the rehearsal described, and followed.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\SettingWhatServicesRead}
 * as `$tuning` and {@see \Modules\Kernel\Api\SecureStorage} as `$storage`.
 */
trait PutsASettingChange
{
    /** What the last rehearsal was asked with, which is what a yes agrees to. */
    public ?ASettingChange $asked = null;

    /** What the stack last reported, rehearsed or written. */
    public ?TheSettingAsChanged $report = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** Where changing a setting has got to, once this frame has asked. Public for {@see Screens\WhatIsRunningHere::$answered}'s reason. */
    public ?TheSettingChangeTurnedOutToBe $going = null;

    /** Leave what the change came to, keeping the setting open. */
    public function startAgain(): void
    {
        $this->asked = null;
        $this->report = null;
        $this->following = null;
        $this->going = null;
    }

    /** Where changing a setting has got to, asked once per frame. */
    public function howItIsGoing(): TheSettingChangeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowASettingChangeReads()->notAsked()
            : $this->putToTheStore(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** What the stack answered of a change to the settings store, or nothing where it refused one. */
    abstract protected function theStoreAnswered(?TheSettingAsChanged $report): void;

    private function rehearseInTheStore(ASettingChange $change): void
    {
        $this->startAgain();
        $this->asked = $change;
        $this->going = $this->putToTheStore(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->rehearse($stack, $session, $change));
    }

    /** Make the change the last rehearsal described; where none did, nothing is sent. */
    private function carryOutWhatWasRehearsed(): void
    {
        $asked = $this->asked;
        $report = $this->report;

        if (! $asked instanceof ASettingChange || ! $report instanceof TheSettingAsChanged || ! $report->rehearses($asked)) {
            return;
        }

        $agreed = ASettingChangeAgreed::after($report, $asked);
        $this->report = null;
        $this->going = $this->putToTheStore(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->carryOut($stack, $session, $agreed));
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<TheSettingAsChanged> $asking
     */
    private function putToTheStore(Closure $asking): TheSettingChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheSettingChangeTurnedOutToBe => $this->heardFromTheStore($asking($stack, $session), $stack),
            notHeld: static fn(): TheSettingChangeTurnedOutToBe => new HowASettingChangeReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $became
     */
    private function heardFromTheStore(WhatBecameOfTheWork $became, Stack $stack): TheSettingChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheSettingChangeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowASettingChangeReads()->running();
            },
            answered: function (TheSettingAsChanged $report): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->report = $report;
                $this->theStoreAnswered($report);

                return new HowASettingChangeReads()->answered($report);
            },
            ended: function (?WhyTheWorkEnded $why): TheSettingChangeTurnedOutToBe {
                $this->following = null;

                return new HowASettingChangeReads()->ended($why);
            },
            refused: function (ARefusalInItsWords $why): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->theStoreAnswered(null);

                return new HowASettingChangeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheSettingChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowASettingChangeReads()->met($why);
            },
        );
    }
}
