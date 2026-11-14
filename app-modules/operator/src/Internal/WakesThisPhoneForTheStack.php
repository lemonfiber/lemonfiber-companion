<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Connection\Api\WhyWakingStayedOff;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\ADistributorToInstall;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\TheDistributors;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowACodeReads;
use Modules\Operator\Internal\Presenters\HowWakingReads;
use Modules\Operator\Internal\ViewModels\ALinkWithItsCode;
use Modules\Operator\Internal\ViewModels\HowWakingIsShown;
use Modules\Operator\Internal\ViewModels\WhatWakingSaid;
use Native\Mobile\Edge\NativeComponent;

/**
 * The switch that lets a stack wake this phone through a UnifiedPush distributor, on the stack's settings.
 *
 * The operator's alone, on a phone that can be woken this way. With several
 * distributors installed and none chosen, turning it on asks which first, and
 * the choice is kept for every stack. Removing the stack tells it to forget
 * the phone's endpoint first, and says so where it could not be reached.
 *
 * The screen that uses it takes {@see \Modules\Connection\Api\WakingThisPhone}
 * as a protected `$woken`, {@see \Modules\Connection\Api\ChoosingWhoWakesThisPhone}
 * as a protected `$wakers`, {@see \Modules\Kernel\Api\Encoding} as a protected
 * `$encoding` and {@see \Modules\Kernel\Api\SecureStorage} as a protected `$storage`.
 *
 * @phpstan-require-extends NativeComponent
 */
trait WakesThisPhoneForTheStack
{
    /** Why waking stayed off when it was last turned on, or nothing. */
    public ?WhatWakingSaid $wakingSaid = null;

    /** Whether the operator is choosing which distributor wakes the phone, before waking is turned on. */
    public bool $choosingADistributor = false;

    /** The switch as it was read for this frame. */
    private ?HowWakingIsShown $wakingShown = null;

    /** The switch, as it stands for this stack. */
    public function waking(): HowWakingIsShown
    {
        return $this->wakingShown ??= $this->theOperatorsSession(
            fn(): HowWakingIsShown => new HowWakingReads()->forTheStack(
                $this->wakers->who(),
                $this->woken->forTheStack($this->stack()->id()),
                $this->whereADistributorIsFound(),
            ),
            static fn(): HowWakingIsShown => new HowWakingReads()->notDrawn(),
        );
    }

    /**
     * The switch was turned on or off: off stops the wake at once, and on asks which distributor first where that is not yet chosen.
     *
     * Taken as the state the switch now shows, which the platform hands over.
     */
    public function wakingTurned(bool $on): void
    {
        $this->wakingSaid = null;
        $this->wakingShown = null;
        $this->choosingADistributor = false;

        if ($on === $this->woken->forTheStack($this->stack()->id())->isOn()) {
            return;
        }

        if (! $on) {
            $this->theOperatorsSession(
                fn(Session $session): HowThisPhoneIsWoken => $this->woken->turnOff($this->stack(), $session),
                static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            );

            return;
        }

        $this->wakers->who()->either(
            through: fn(ADistributor $through): HowThisPhoneIsWoken => $this->turnedOnThrough($through),
            toChoose: function (): HowThisPhoneIsWoken {
                $this->choosingADistributor = true;

                return HowThisPhoneIsWoken::off();
            },
            noneInstalled: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            notHere: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
        );
    }

    /** The operator chose the distributor that wakes this phone, and waking is turned on through it. A package not installed changes nothing. */
    public function wakeThrough(string $package): void
    {
        $this->wakingShown = null;
        $this->choosingADistributor = false;

        $this->wakers->who()->either(
            through: fn(ADistributor $through, TheDistributors $installed): HowThisPhoneIsWoken => $this->chosenAmong($installed, ADistributor::of($package, '')),
            toChoose: fn(TheDistributors $installed): HowThisPhoneIsWoken => $this->chosenAmong($installed, ADistributor::of($package, '')),
            noneInstalled: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            notHere: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
        );
    }

    /** Whether the stack wakes this phone, or holds its endpoint still, so removing it has the stack told first. */
    public function removingTellsTheStack(): bool
    {
        return $this->woken->forTheStack($this->stack()->id())->either(
            off: static fn(): WhatRemovingTells => WhatRemovingTells::Nothing,
            on: static fn(): WhatRemovingTells => WhatRemovingTells::ToForgetThisPhone,
            owed: static fn(): WhatRemovingTells => WhatRemovingTells::ToForgetThisPhone,
        ) === WhatRemovingTells::ToForgetThisPhone;
    }

    /** Tell the stack to forget this phone's endpoint, and answer whether it still holds it because it could not be reached. */
    private function stillHeldOnceTold(): bool
    {
        return $this->theOperatorsSession(
            fn(Session $session): WhatRemovingTells => $this->woken->stillHeldAfterTelling($this->stack(), $session) ? WhatRemovingTells::ToForgetThisPhone : WhatRemovingTells::Nothing,
            static fn(): WhatRemovingTells => WhatRemovingTells::Nothing,
        ) === WhatRemovingTells::ToForgetThisPhone;
    }

    private function chosenAmong(TheDistributors $installed, ADistributor $asked): HowThisPhoneIsWoken
    {
        foreach ($installed->onlyThe($asked) as $chosen) {
            $this->wakers->choose($chosen);
            $this->turnedOnThrough($chosen);
        }

        return HowThisPhoneIsWoken::off();
    }

    private function turnedOnThrough(ADistributor $through): HowThisPhoneIsWoken
    {
        return $this->theOperatorsSession(
            fn(Session $session): HowThisPhoneIsWoken => $this->woken->turnOn($this->stack(), $session, $through)->either(
                on: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
                off: function (WhyWakingStayedOff $why): HowThisPhoneIsWoken {
                    $this->wakingSaid = new HowWakingReads()->stayedOff($why);
                    $why->either(
                        noKeyName: static fn(): WhyWakingStayedOff => $why,
                        noEndpoint: static fn(): WhyWakingStayedOff => $why,
                        refused: static fn(): WhyWakingStayedOff => $why,
                        met: $this->lettingGoIfRefused($this->stack(), static fn(): WhyWakingStayedOff => $why),
                        notKept: static fn(): WhyWakingStayedOff => $why,
                    );

                    return HowThisPhoneIsWoken::off();
                },
            ),
            static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
        );
    }

    /** Where a distributor is found, with its code. */
    private function whereADistributorIsFound(): ALinkWithItsCode
    {
        $page = ADistributorToInstall::ntfy();

        return new ALinkWithItsCode($page->carried(), new HowACodeReads()->squares($this->encoding->codeFor($page)));
    }

    /**
     * What `$operator` makes of the operator's session on this stack, or what `$otherwise` makes where the session is a member's or none.
     *
     * @template TOperator of object
     * @template TOtherwise of object
     *
     * @param  Closure(Session): TOperator $operator
     * @param  Closure(): TOtherwise       $otherwise
     * @return TOperator|TOtherwise
     */
    private function theOperatorsSession(Closure $operator, Closure $otherwise): object
    {
        return $this->storage->resume($this->stack()->id())->either(
            held: static fn(Session $session, Whose $whose): object => $whose->either(
                operator: static fn(): object => $operator($session),
                member: static fn(): object => $otherwise(),
            ),
            notHeld: static fn(): object => $otherwise(),
        );
    }
}
