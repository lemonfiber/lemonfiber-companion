<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Operator\Internal\ViewModels\AStackToChooseAsShown;
use Modules\Stacks\Api\AStacksScreen;

/**
 * What a screen about one stack reads about the stacks this phone holds, what
 * its menu is called, where choosing another stack leads, and how each stack
 * stands while the list of them is open.
 *
 * Every screen about a stack is handed one, built by the container, and asks
 * it rather than the stacks themselves. The container builds each with a
 * {@see HearingEachStack} of its own, so the list's subscriptions belong to
 * the list.
 */
final readonly class TheWayAround
{
    private const string THE_MENU_IS_CALLED = 'navigation.menu.open';

    public function __construct(
        private Stacks $stacks,
        private Translator $catalogue,
        private Standings $standings,
        private Clock $clock,
        private SecureStorage $storage,
        private HearingEachStack $hearing,
    ) {}

    /**
     * The stack a screen is about, from the name its route carries, as this
     * phone has it paired.
     *
     * The route's parameter arrives untyped, and anything that is not text
     * names no stack.
     */
    public function stackNamed(mixed $named): Stack
    {
        return $this->stacks->configured()->stack(StackId::rememberedAs(is_string($named) ? $named : ''));
    }

    /** What a screen reader calls the control that opens the menu. */
    public function theMenuIsCalled(): string
    {
        $said = $this->catalogue->get(self::THE_MENU_IS_CALLED);

        return is_string($said) ? $said : self::THE_MENU_IS_CALLED;
    }

    /**
     * Every stack this phone holds, in its order, each with how it last stood
     * and whether it is the one a screen is about.
     *
     * @return list<AStackToChooseAsShown>
     */
    public function stacksToChooseFrom(Stack $current): array
    {
        $said = new WhatEachStackLastSaid($this->standings, $this->clock);
        $rows = [];

        foreach ($this->stacks->configured() as $stack) {
            $line = $said->of($stack);
            $rows[] = new AStackToChooseAsShown(
                id: $stack->id()->stored(),
                name: $stack->name()->shown(),
                word: $line->word,
                tone: $line->tone,
                current: $stack->is($current),
            );
        }

        return $rows;
    }

    /**
     * What the list of stacks has heard after this wake, from every stack but one whose stream the screen already holds.
     *
     * Only while the list is open, and on its own subscriptions, so what the
     * screen underneath holds is neither asked twice nor let go of.
     */
    public function heardWhileChoosing(WhatEachStackSaidSoFar $heard, Stack $current, bool $itsStreamIsHeld): WhatEachStackSaidSoFar
    {
        $stacks = [];

        foreach ($this->stacks->configured() as $stack) {
            if (! $itsStreamIsHeld || ! $stack->is($current)) {
                $stacks[] = $stack;
            }
        }

        return $this->hearing->after($heard, $stacks);
    }

    /** Every subscription the list of stacks holds let go of, because it closed or cannot be seen. */
    public function stopHearingWhileChoosing(WhatEachStackSaidSoFar $heard): WhatEachStackSaidSoFar
    {
        return $this->hearing->letGo($heard);
    }

    /**
     * Where choosing a stack from that list leads: its sign-in where this
     * phone holds no session for it, its health for the operator, and what a
     * member is owed for a member.
     */
    public function choosingLeadsTo(Stack $stack): string
    {
        $where = WhereAStackIs::of($stack->id());

        return match (WhereTappingLeads::for($this->storage, $stack->id())) {
            WhereTappingLeads::TheSignIn => $where->signIn(),
            WhereTappingLeads::TheReport => $where->health(),
            WhereTappingLeads::WhatTheyAreOwed => AStacksScreen::Owed->forTheStack($stack->id()),
        };
    }
}
