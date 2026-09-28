<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function implode;
use function in_array;

use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\WhatTheGuardSaw;
use Modules\Operator\Internal\ViewModels\AFormToGuardAsShown;
use Modules\Operator\Internal\ViewModels\HowTheGuardWent;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheGuardWouldGuard;

/**
 * Where a guard started from this screen stands, as the template draws it.
 *
 * One method per state, each saying only its own. A guard that saw the data
 * location go and could not stop the forms is said to have not stopped them,
 * and is never drawn as a guard that protected anything.
 */
final readonly class HowAGuardReads
{
    /**
     * The forms the stack declares, each saying whether it is named for the guard.
     *
     * @param list<string> $declared the forms the stack declares, in its order
     * @param list<string> $naming   the forms named so far
     */
    public function choosing(array $declared, array $naming): WhatTheGuardWouldGuard
    {
        $forms = [];
        $named = [];

        foreach ($declared as $form) {
            $isNamed = in_array($form, $naming, strict: true);
            $forms[] = new AFormToGuardAsShown($form, $isNamed);

            if ($isNamed) {
                $named[] = $form;
            }
        }

        return new WhatTheGuardWouldGuard($forms, implode(', ', $named), $named !== []);
    }

    /** Nothing was started from this screen, so there is nothing to follow. */
    public function notAsked(): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), wasAsked: false);
    }

    /** This device no longer holds a session for the stack the guard was asked of. */
    public function signedOut(): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::theSessionEnded());
    }

    /** Starting it, or asking after it, met this instead. */
    public function met(Obstacle $why): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::somethingStopped($why));
    }

    /** The stack is guarding the forms asked for. */
    public function guarding(Forms $asked): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), isGuarding: true, forms: $this->named($asked));
    }

    /** It saw the data location go: the forms it named, whether stopping them worked, and why it ended. */
    public function sawItGo(WhatTheGuardSaw $saw): HowTheGuardWent
    {
        return $this->following(
            HowTheReadingWent::itCameBack(),
            endedSaid: 'stacks.guard.saw_it_go',
            forms: $this->named($saw->forms()),
            stoppedSaid: $saw->stoppedThem() ? 'stacks.guard.stopped_them' : 'stacks.guard.did_not_stop_them',
            said: $saw->reason(),
        );
    }

    /** It never started, and the stack said why. */
    public function refused(string $said): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), endedSaid: 'stacks.guard.did_not_start', said: $said);
    }

    /** It ended without seeing anything: released, or let go because nothing asked. */
    public function ended(): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), endedSaid: 'stacks.guard.let_go');
    }

    /** The stack no longer knows it. */
    public function unknown(): HowTheGuardWent
    {
        return $this->following(HowTheReadingWent::itCameBack(), endedSaid: 'stacks.guard.unknown');
    }

    /**
     * The forms, by name, in the order they were given.
     *
     * @return list<string>
     */
    private function named(Forms $forms): array
    {
        $named = [];

        foreach ($forms as $form) {
            $named[] = $form->named();
        }

        return $named;
    }

    /**
     * One state of following it, every field its own state does not fill left empty.
     *
     * @param list<string> $forms
     */
    private function following(
        HowTheReadingWent $went,
        bool $wasAsked = true,
        bool $isGuarding = false,
        string $endedSaid = '',
        array $forms = [],
        string $stoppedSaid = '',
        string $said = '',
    ): HowTheGuardWent {
        return new HowTheGuardWent($went, $wasAsked, $isGuarding, $endedSaid, $forms, implode(', ', $forms), $stoppedSaid, $said);
    }
}
