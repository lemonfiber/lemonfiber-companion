<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_keys;

use Closure;

use function in_array;
use function is_string;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\ASettingChangeAgreed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Native\Mobile\Attributes\Poll;

/**
 * Changing one or more of the settings a screen lists in one act: rehearsed, agreed to, and followed.
 *
 * A setting is changed or put back to its default by a rehearsal first, and the
 * yes carries the offer that rehearsal answered. A setting that lists its
 * choices is given one of them, never a typed value. Where the stack refuses a
 * change, each setting on the screen that hangs together with one the change
 * moves is offered in the same change, so settings that depend on each other
 * move together. Which settings may be changed is the screen's to say, through
 * `itsSetting()`; a secret is never one.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\SettingWhatServicesRead}
 * as a protected `$tuning` and {@see \Modules\Kernel\Api\SecureStorage} as a
 * protected `$storage`, and holds what it read as a public `$read` and what it
 * drew as a public `$answered`, for {@see OffersTheAppsSettings}'s reason.
 */
trait ChangesASetting
{
    /** What is typed into the open setting's field. */
    public string $typed = '';

    /** The setting open for changing, or null where none is. */
    public ?ASettingBeingChanged $changing = null;

    /** What the last rehearsal was asked with, which is what a yes agrees to. */
    public ?ASettingChange $asked = null;

    /** What the stack last reported, rehearsed or written. */
    public ?TheSettingAsChanged $report = null;

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** Where changing a setting has got to, once this frame has asked. Public for {@see Screens\WhatIsRunningHere::$answered}'s reason. */
    public ?TheSettingChangeTurnedOutToBe $going = null;

    /** Open one of the screen's settings for changing, seeded with its value; any other name, a secret's among them, opens nothing. */
    public function change(string $setting): void
    {
        $held = $this->itsSetting($setting);

        if (is_string($this->following) || ! $held instanceof ASetting) {
            return;
        }

        $this->startAgain();
        $this->changing = new ASettingBeingChanged($setting, toDefault: false);
        $this->typed = $held->choices()->isEmpty() || Among::theChoices($held->choices(), $held->value()->said()) ? $held->value()->said() : '';
    }

    /** Choose one of the values a setting the change moves lists; a value it does not list, or a setting the change does not move, chooses nothing. */
    public function choose(string $setting, string $value): void
    {
        $open = $this->changing;
        $held = $this->itsSetting($setting);

        if (is_string($this->following) || ! $open instanceof ASettingBeingChanged || $open->toDefault || ! $open->moves($setting) || ! $held instanceof ASetting || ! Among::theChoices($held->choices(), $value)) {
            return;
        }

        $this->startAgain();

        if ($setting === $open->named) {
            $this->typed = $value;

            return;
        }

        $this->changing = $open->choosing($setting, $value);
    }

    /** Move one of the settings the stack's refusal left behind in the same change; any other moves nothing. */
    public function alsoMove(string $setting): void
    {
        $open = $this->changing;

        if (is_string($this->following) || ! $open instanceof ASettingBeingChanged || ! in_array($setting, $this->toMoveAlongside(), strict: true)) {
            return;
        }

        $this->startAgain();
        $this->changing = $open->alsoMoving($setting);
    }

    /** Leave a setting moved alongside out of the change. */
    public function leaveOut(string $setting): void
    {
        $open = $this->changing;

        if (is_string($this->following) || ! $open instanceof ASettingBeingChanged) {
            return;
        }

        $this->startAgain();
        $this->changing = $open->leaving($setting);
    }

    /**
     * The settings offered in the same change once the stack refused it: each on the screen that hangs together with one the change moves and that it does not move yet, and, where the change gives values, that lists the values to choose among.
     *
     * @return list<string>
     */
    public function toMoveAlongside(): array
    {
        $open = $this->changing;
        $read = $this->read;

        if (! $open instanceof ASettingBeingChanged || ! $read instanceof TheServicesSettings || ! $this->howItIsGoing()->refused instanceof ARefusalAsShown) {
            return [];
        }

        $moved = [];

        foreach ([$open->named, ...array_keys($open->alongside)] as $named) {
            $setting = Among::theSettings($read->settings(), $named);

            if ($setting instanceof ASetting) {
                $moved[] = $setting;
            }
        }

        $offered = [];

        foreach ($read->settings()->hangingTogetherWith(...$moved) as $setting) {
            if ($this->itsSetting($setting->name()) instanceof ASetting && ($open->toDefault || ! $setting->choices()->isEmpty())) {
                $offered[] = $setting->name();
            }
        }

        return $offered;
    }

    /** Close the open setting without asking the stack anything. */
    public function never(): void
    {
        $this->startAgain();
        $this->changing = null;
        $this->typed = '';
    }

    /** Leave what the change came to, keeping the setting open. */
    public function startAgain(): void
    {
        $this->asked = null;
        $this->report = null;
        $this->following = null;
        $this->going = null;
    }

    /** Ask what the change would do, writing nothing: the open setting given what is typed or chosen, or put back, with each setting moved alongside. */
    public function rehearse(): void
    {
        $open = $this->changing;
        $held = $open instanceof ASettingBeingChanged ? $this->itsSetting($open->named) : null;
        $change = $open?->asked($this->typed);

        if (is_string($this->following) || ! $held instanceof ASetting || ! $change instanceof ASettingChange || ! $this->rehearsable($open, $held)) {
            return;
        }

        $this->ask($change);
    }

    /** Ask what putting one of the screen's settings back to its default would do, writing nothing. */
    public function backToDefault(string $setting): void
    {
        if (is_string($this->following) || ! $this->itsSetting($setting) instanceof ASetting) {
            return;
        }

        $this->changing = new ASettingBeingChanged($setting, toDefault: true);
        $this->ask(ASettingChange::backToDefault($setting));
    }

    /** Make the change the rehearsal on the screen described; anywhere else, nothing is sent. */
    public function agree(): void
    {
        $asked = $this->asked;
        $report = $this->report;

        if (! $asked instanceof ASettingChange || ! $report instanceof TheSettingAsChanged || ! $report->rehearses($asked)) {
            return;
        }

        $agreed = ASettingChangeAgreed::after($report, $asked);
        $this->report = null;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->carryOut($stack, $session, $agreed));
    }

    /** Ask after the work again while the stack is at it, at {@see HowOftenAScreenLooks}'s interval. */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->stands->isWorking()) {
            $this->going = null;
        }
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->stands->isWorking();
    }

    /** Where changing a setting has got to, asked once per frame. */
    public function howItIsGoing(): TheSettingChangeTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowASettingChangeReads()->notAsked()
            : $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** The setting of that name the screen lists, as last read, or none; a secret is never one. */
    abstract protected function itsSetting(string $setting): ?ASetting;

    /** Whether the open change is one to rehearse now: a value among the setting's choices where it lists any, or settings put back alongside the one already rehearsed. */
    private function rehearsable(ASettingBeingChanged $open, ASetting $held): bool
    {
        return $open->toDefault
            ? $open->alongside !== []
            : $held->choices()->isEmpty() || Among::theChoices($held->choices(), $this->typed);
    }

    /** Take what the stream said of the settings in place of what was read. */
    private function hearTheSettings(): void
    {
        $heard = $this->settingsHeard;

        if ($heard instanceof TheServicesSettings) {
            $this->settingsHeard = null;
            $this->read = $heard;
            $this->answered = null;
        }
    }

    private function ask(ASettingChange $change): void
    {
        $this->startAgain();
        $this->asked = $change;
        $this->going = $this->put(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->tuning->rehearse($stack, $session, $change));
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWork<TheSettingAsChanged> $asking
     */
    private function put(Closure $asking): TheSettingChangeTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheSettingChangeTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheSettingChangeTurnedOutToBe => new HowASettingChangeReads()->signedOut(),
        );
    }

    /**
     * What the stack said, as the screen draws it, holding what to follow or to agree to.
     *
     * A written change carries every setting after it, which replaces what was
     * read. A refusal reads the settings again, so a change agreed against a
     * value that moved is drawn against the one that stands.
     *
     * @param WhatBecameOfTheWork<TheSettingAsChanged> $became
     */
    private function shown(WhatBecameOfTheWork $became, Stack $stack): TheSettingChangeTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheSettingChangeTurnedOutToBe {
                $this->following = $job->shown();

                return new HowASettingChangeReads()->running();
            },
            answered: function (TheSettingAsChanged $report): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->report = $report;
                $this->read = $report->did() === WhatTheSettingRunDid::Rehearsed ? $this->read : $report->settings();
                $this->answered = null;

                return new HowASettingChangeReads()->answered($report);
            },
            ended: function (): TheSettingChangeTurnedOutToBe {
                $this->following = null;

                return new HowASettingChangeReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheSettingChangeTurnedOutToBe {
                $this->following = null;
                $this->read = null;
                $this->answered = null;

                return new HowASettingChangeReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheSettingChangeTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowASettingChangeReads()->met($why);
            },
        );
    }
}
