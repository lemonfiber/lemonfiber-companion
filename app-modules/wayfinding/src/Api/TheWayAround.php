<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api;

use Illuminate\Contracts\Translation\Translator;

use function is_string;

use Modules\Design\View\Tone;
use Modules\Health\Api\WhatWasHeardSoFar;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Standings;
use Modules\Kernel\Api\WhereTheOperatorWas;
use Modules\Stacks\Api\AStacksScreen;
use Native\Mobile\Edge\NativeComponent;

/**
 * What a screen about one stack reads about the stacks this phone holds, what
 * its menu is called and whose it is, where choosing another stack leads, and
 * how each stack stands while the list of them is open.
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
        private WhereTheOperatorWas $was,
    ) {}

    /**
     * The stack a screen is about, from the name its route carries, as this
     * phone has it paired.
     *
     * The route's parameter arrives untyped, and anything that is not text
     * names no stack.
     */
    public function stackOn(NativeComponent $screen): Stack
    {
        $named = $screen->param('stack');

        return $this->stack(StackId::rememberedAs(is_string($named) ? $named : ''));
    }

    /** The stack this phone calls by that name, as it has it paired. */
    public function stack(StackId $named): Stack
    {
        return $this->stacks->configured()->stack($named);
    }

    /** What a screen reader calls the control that opens the menu. */
    public function theMenuIsCalled(): string
    {
        $said = $this->catalogue->get(self::THE_MENU_IS_CALLED);

        return is_string($said) ? $said : self::THE_MENU_IS_CALLED;
    }

    /** Whose menu a screen about this stack draws, from whose session this phone holds for it. */
    public function whoTheMenuIsFor(Stack $stack): WhoTheMenuIsFor
    {
        return WhoTheMenuIsFor::for($this->storage, $stack->id());
    }

    /**
     * Every stack this phone holds, in its order, each with how it last stood
     * and whether it is the one a screen is about.
     */
    public function stacksToChooseFrom(Stack $current): TheStacksToChooseFrom
    {
        $rows = [];

        foreach ($this->stacks->configured() as $stack) {
            $standing = $this->lastStandingOf($stack);
            $rows[] = new AStackToChooseAsShown(
                id: $stack->id()->stored(),
                name: $stack->name()->shown(),
                word: $standing->saidInAWord(),
                tone: Tone::ofAStanding($standing)->value,
                current: $stack->is($current),
            );
        }

        return TheStacksToChooseFrom::of(...$rows);
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

        return $this->hearing->after($heard, ...$stacks);
    }

    /** Every subscription the list of stacks holds let go of, because it closed or cannot be seen. */
    public function stopHearingWhileChoosing(WhatEachStackSaidSoFar $heard): WhatEachStackSaidSoFar
    {
        return $this->hearing->letGo($heard);
    }

    /**
     * Where the app lands once this stack is removed from the phone: the next
     * stack in the operator's order, or the first where it was the last, or
     * the opening screen where it was the only one, which is then a first run.
     *
     * Asked before the removal, while the stack is still in the order.
     */
    public function afterRemoving(Stack $removed): string
    {
        $before = [];
        $after = [];
        $passed = false;

        foreach ($this->stacks->configured() as $stack) {
            if ($stack->is($removed)) {
                $passed = true;

                continue;
            }

            if ($passed) {
                $after[] = $stack;

                continue;
            }

            $before[] = $stack;
        }

        $next = [...$after, ...$before];

        return $next === [] ? AScreenWithoutAStack::TheList->value : $this->choosingLeadsTo($next[0]);
    }

    /**
     * Where the app opens: the stack the operator was last on, on the tab they
     * last used there, or the first stack in their order where that one is
     * gone; nowhere where the phone holds no stack.
     */
    public function whereTheOpeningLands(): string
    {
        $first = null;

        foreach ($this->stacks->configured() as $stack) {
            if ($this->was->wasLastOn($stack->id())) {
                return $this->choosingLeadsTo($stack);
            }

            $first ??= $stack;
        }

        return $first instanceof Stack ? $this->choosingLeadsTo($first) : '';
    }

    /**
     * Where choosing a stack from that list leads: its sign-in where this
     * phone holds no session for it, the tab the operator last used on it for
     * the operator, and Home for a member.
     */
    public function choosingLeadsTo(Stack $stack): string
    {
        return match (WhereTappingLeads::for($this->storage, $stack->id())) {
            WhereTappingLeads::TheSignIn => AStacksScreen::SignIn->forTheStack($stack->id()),
            WhereTappingLeads::TheReport => $this->onItsLastTab($stack),
            WhereTappingLeads::TheirHome => AStacksScreen::Shelf->forTheStack($stack->id()),
        };
    }

    /** The stack, on the tab the operator last used there, and on Health where they have used none. */
    public function onItsLastTab(Stack $stack): string
    {
        return TheTabs::kept($this->was->tabOf($stack->id()))->screen()->forTheStack($stack->id());
    }

    /**
     * How the stack last stood, as this phone kept it, or unknown where nothing
     * was kept or what was kept has gone out of date.
     *
     * Everything this reads came out of a store, so a live reading cannot
     * arrive, and the arm the type has for one reads as never heard.
     */
    private function lastStandingOf(Stack $stack): HowItStands
    {
        $now = $this->clock->now();

        return $this->standings->lastKnownOf($stack->id())->either(
            waiting: static fn(): HowItStands => HowItStands::Unknown,
            holding: static fn(Reading $reading): HowItStands => $reading->either(
                live: static fn(): HowItStands => HowItStands::Unknown,
                retained: static fn(object $standing, Instant $at): HowItStands
                    => $standing instanceof HowItStands && WhatWasHeardSoFar::isStillCurrent($at, $now) ? $standing : HowItStands::Unknown,
            ),
        );
    }
}
