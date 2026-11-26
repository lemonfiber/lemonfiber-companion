<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_filter;
use function array_values;

use Closure;

use function in_array;

use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheFormsToSwitchTo;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhatBecameOfTheWork;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowTheSwitchReads;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\TheSwitchTurnedOutToBe;
use Native\Mobile\Edge\NativeComponent;

/**
 * Making a chosen set of forms the active set: chosen from the forms the stack declares, rehearsed, and made only on a separate yes.
 *
 * The screen that uses it takes {@see \Modules\Kernel\Api\Switching} as a protected `$switching`.
 * Rehearsing and switching each answer at once or as a job, followed by
 * {@see FollowsOneJobAtATime}, which shows what the job says while it runs.
 *
 * @phpstan-require-extends NativeComponent
 */
trait SwitchesTheForms
{
    /** @use FollowsOneJobAtATime<TheSwitchTurnedOutToBe> */
    use FollowsOneJobAtATime;

    /** @var list<string> the forms chosen as the active set, by name */
    public array $switchingTo = [];

    /** The forms the switch on the screen was asked with. */
    public ?TheFormsToSwitchTo $switchAsked = null;

    /** The forms a yes sends, where the report on the screen is a rehearsal of them. */
    public ?TheFormsToSwitchTo $switchMayBeMade = null;

    /** Whether what was last sent made the switch rather than rehearsed it. */
    public bool $switchWasMade = false;

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
        $this->switchMayBeMade = null;
        $this->forget();

        $declared = $this->forms()->names ?? [];
        $chosen = [];

        foreach ($declared as $name) {
            if (in_array($name, $this->switchingTo, strict: true)) {
                $chosen[] = Form::called($name);
            }
        }

        if ($chosen === []) {
            $this->reported(new HowTheSwitchReads()->notAskable('stacks.switch.none_chosen'));

            return;
        }

        $this->switchAsked = TheFormsToSwitchTo::these(...$chosen);
        $this->switchWasMade = false;
        $this->sendTheSwitch($this->switchAsked);
    }

    /** Make the switch the rehearsal on the screen was asked with. */
    public function makeTheSwitch(): void
    {
        $forms = $this->switchMayBeMade;

        if (! $forms instanceof TheFormsToSwitchTo) {
            return;
        }

        $this->switchMayBeMade = null;
        $this->switchWasMade = true;
        $this->forget();
        $this->sendTheSwitch($forms);
    }

    /**
     * Send the rehearsal or the switch last sent again, where other work held the stack.
     *
     * Only then: the stack turned it away before acting on it, so nothing it
     * did is done twice.
     */
    public function tryTheSwitchAgain(): void
    {
        $forms = $this->switchAsked;

        if (! $forms instanceof TheFormsToSwitchTo || ! $this->howTheSwitchIsGoing()->stands->went->wasHeldByOtherWork()) {
            return;
        }

        $this->forget();
        $this->sendTheSwitch($forms);
    }

    public function howTheSwitchIsGoing(): TheSwitchTurnedOutToBe
    {
        return $this->whatBecameOfTheJob();
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheSwitchTurnedOutToBe
    {
        return $this->switchShown($this->switching->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): TheSwitchTurnedOutToBe
    {
        return new HowTheSwitchReads()->notAsked();
    }

    protected function withNoSessionHeld(): TheSwitchTurnedOutToBe
    {
        return new HowTheSwitchReads()->signedOut();
    }

    private function sendTheSwitch(TheFormsToSwitchTo $forms): void
    {
        $this->reported($this->switchPut(fn(Stack $stack, Session $session): WhatBecameOfTheWork => $this->switchWasMade
            ? $this->switching->carryOut($stack, $session, $forms)
            : $this->switching->rehearse($stack, $session, $forms)));
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
                $this->follow($job);

                return new HowTheSwitchReads()->running();
            },
            answered: function (TheSwitch $switch): TheSwitchTurnedOutToBe {
                $this->switchMayBeMade = $switch->did() === WhetherItWasRehearsed::Rehearsed ? $this->switchAsked : null;

                if (! $this->switchMayBeMade instanceof TheFormsToSwitchTo) {
                    $this->again();
                }

                return new HowTheSwitchReads()->answered($switch);
            },
            ended: fn(?WhyTheWorkEnded $why): TheSwitchTurnedOutToBe => new HowTheSwitchReads()->ended($why),
            refused: fn(ARefusalInItsWords $why): TheSwitchTurnedOutToBe => new HowTheSwitchReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheSwitchTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSwitchReads()->met($why);
            },
        );
    }
}
