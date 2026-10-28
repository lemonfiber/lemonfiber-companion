<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_filter;
use function array_values;

use Closure;

use function in_array;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Operator\Internal\Presenters\HowTheSwitchReads;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\TheSwitchTurnedOutToBe;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * Making a chosen set of forms the active set: chosen from the forms the stack declares, rehearsed, and made only on a separate yes.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\Switching} as a protected `$switching`.
 *
 * @phpstan-require-extends NativeComponent
 */
trait SwitchesTheForms
{
    /** @var list<string> the forms chosen as the active set, by name */
    public array $switchingTo = [];

    /** The forms the rehearsal on the screen was asked with, which is what a yes sends. */
    public ?TheFormsToSwitchTo $switchAsked = null;

    /** Whether the report on the screen is a rehearsal a yes may be given to. */
    public bool $switchRehearsed = false;

    /** The handle of the work being followed, where there is one. */
    public ?string $switchFollowing = null;

    public ?TheSwitchTurnedOutToBe $switchGoing = null;

    abstract public function forms(): ?TheFormsAsFound;

    /** Choose a form for the active set, or take it out again. */
    public function toggleForm(string $form): void
    {
        $this->switchingTo = in_array($form, $this->switchingTo, strict: true)
            ? array_values(array_filter($this->switchingTo, static fn(string $chosen): bool => $chosen !== $form))
            : [...$this->switchingTo, $form];
    }

    /** Ask what making the chosen forms the active set would do, moving nothing. */
    public function rehearseTheSwitch(): void
    {
        $this->switchAsked = null;
        $this->switchRehearsed = false;
        $this->switchFollowing = null;

        $declared = $this->forms()->names ?? [];
        $chosen = [];

        foreach ($declared as $name) {
            if (in_array($name, $this->switchingTo, strict: true)) {
                $chosen[] = Form::called($name);
            }
        }

        if ($chosen === []) {
            $this->switchGoing = new HowTheSwitchReads()->notAskable('stacks.switch.none_chosen');

            return;
        }

        $forms = TheFormsToSwitchTo::these(...$chosen);
        $this->switchAsked = $forms;
        $this->switchGoing = $this->switchPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->switching->rehearse($stack, $session, $forms));
    }

    /** Make the switch the rehearsal on the screen was asked with. */
    public function makeTheSwitch(): void
    {
        $forms = $this->switchAsked;

        if (! $forms instanceof TheFormsToSwitchTo || ! $this->switchRehearsed) {
            return;
        }

        $this->switchRehearsed = false;
        $this->switchGoing = $this->switchPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->switching->carryOut($stack, $session, $forms));
    }

    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItSwitches(): void
    {
        if ($this->howTheSwitchIsGoing()->stands->isWorking()) {
            $this->switchGoing = null;
        }
    }

    public function howTheSwitchIsGoing(): TheSwitchTurnedOutToBe
    {
        $following = $this->switchFollowing;

        return $this->switchGoing ??= $following === null
            ? new HowTheSwitchReads()->notAsked()
            : $this->switchPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->switching->whatBecameOf($stack, $session, Job::named($following)));
    }

    /** @param Closure(Stack, Session): WhatBecameOfTheWork<TheSwitch> $asking */
    private function switchPut(Closure $asking): TheSwitchTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheSwitchTurnedOutToBe => $this->switchShown($asking($stack, $session), $stack),
            notHeld: static fn(): TheSwitchTurnedOutToBe => new HowTheSwitchReads()->signedOut(),
        );
    }

    /**
     * @param WhatBecameOfTheWork<TheSwitch> $became
     */
    private function switchShown(WhatBecameOfTheWork $became, Stack $stack): TheSwitchTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheSwitchTurnedOutToBe {
                $this->switchFollowing = $job->shown();

                return new HowTheSwitchReads()->running();
            },
            answered: function (TheSwitch $switch): TheSwitchTurnedOutToBe {
                $this->switchFollowing = null;
                $this->switchRehearsed = $switch->did() === WhetherItWasRehearsed::Rehearsed;

                if (! $this->switchRehearsed) {
                    $this->again();
                }

                return new HowTheSwitchReads()->answered($switch);
            },
            ended: function (): TheSwitchTurnedOutToBe {
                $this->switchFollowing = null;

                return new HowTheSwitchReads()->ended();
            },
            refused: function (ARefusalInItsWords $why): TheSwitchTurnedOutToBe {
                $this->switchFollowing = null;

                return new HowTheSwitchReads()->refused($why);
            },
            met: function (Obstacle $why) use ($stack): TheSwitchTurnedOutToBe {
                $this->switchFollowing = null;
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSwitchReads()->met($why);
            },
        );
    }
}
