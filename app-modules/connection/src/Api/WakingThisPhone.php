<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Connection\Internal\TheWakingAsKept;
use Modules\Connection\Internal\WakingKept;
use Modules\Kernel\Api\ADistributor;
use Modules\Kernel\Api\AnEndpoint;
use Modules\Kernel\Api\APhoneKeysName;
use Modules\Kernel\Api\APushName;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\AWakeToRegister;
use Modules\Kernel\Api\AWakingKey;
use Modules\Kernel\Api\AWakingSecret;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Entropy;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\ForgetsEverythingKept;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\HowThisPhoneIsWoken;
use Modules\Kernel\Api\KeepingThePhoneKeysName;
use Modules\Kernel\Api\Noted;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ReachingADistributor;
use Modules\Kernel\Api\RegisteringAWake;
use Modules\Kernel\Api\Sealed;
use Modules\Kernel\Api\SealedPayload;
use Modules\Kernel\Api\SealStanding;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shape;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Unsealed;
use Modules\Kernel\Api\WhyNoEndpoint;

/**
 * Whether each stack wakes this phone through a UnifiedPush distributor, turned on and off stack by stack.
 *
 * Turning it on makes a WebPush key pair for the stack alone, asks the
 * distributor for an endpoint, and registers it with the stack under the name
 * this phone's key carries there. What the stack keeps is kept on the phone,
 * sealed, one row a stack.
 *
 * Turning it off drops the distributor's registration and the key pair at once,
 * so the wake stops whether or not the stack is reached. A removal the stack
 * could not be told of is owed, and sent on the next read of it that comes
 * back. Removing the stack from the phone owes nothing.
 */
final readonly class WakingThisPhone implements ForgetsAStack
{
    public function __construct(
        private Sealed $seal,
        private WakingKept $kept,
        private Clock $clock,
        private ReachingADistributor $distributor,
        private RegisteringAWake $stacks,
        private KeepingThePhoneKeysName $names,
        private Entropy $entropy,
    ) {}

    /** Whether the stack wakes this phone, off where nothing is kept or it does not read. */
    public function forTheStack(StackId $stack): HowThisPhoneIsWoken
    {
        return $this->kept->found($this->seal->stack($stack))->either(
            found: fn(SealedPayload $payload, Shape $shape): HowThisPhoneIsWoken => $this->seal->open($payload)->either(
                opened: static fn(Unsealed $value): HowThisPhoneIsWoken => TheWakingAsKept::read($shape, $value),
                unreadable: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            ),
            none: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
        );
    }

    /** Turn waking on for the stack through `$through`, sending first any removal still owed to it. */
    public function turnOn(Stack $stack, Session $session, ADistributor $through): WhatTurningWakingOnCameTo
    {
        $this->sendWhatIsOwed($stack, $session);

        return $this->names->nameOn($stack->id())->either(
            named: fn(APhoneKeysName $key): WhatTurningWakingOnCameTo => $this->distributor->endpointFor($stack->id(), $through)->either(
                given: fn(AnEndpoint $endpoint, AWakingKey $public): WhatTurningWakingOnCameTo => $this->registered(
                    $stack,
                    $session,
                    $through,
                    new AWakeToRegister(APushName::forThePhoneKey($key), $endpoint, $public, AWakingSecret::from($this->entropy->aKey())),
                ),
                not: static fn(WhyNoEndpoint $why): WhatTurningWakingOnCameTo => WhatTurningWakingOnCameTo::off(WhyWakingStayedOff::noEndpoint($why)),
            ),
            none: static fn(): WhatTurningWakingOnCameTo => WhatTurningWakingOnCameTo::off(WhyWakingStayedOff::noKeyName()),
        );
    }

    /** Turn waking off for the stack at once, and answer whether the stack was told or is owed its removal. */
    public function turnOff(Stack $stack, Session $session): HowThisPhoneIsWoken
    {
        $this->distributor->letGo($stack->id());

        return $this->forTheStack($stack->id())->either(
            off: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            on: fn(ADistributor $through, AWakeToRegister $wake): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $wake->name),
            owed: fn(APushName $name): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $name),
        );
    }

    /** Send the stack a removal owed to it, once it has been read again. */
    public function sendWhatIsOwed(Stack $stack, Session $session): HowThisPhoneIsWoken
    {
        return $this->forTheStack($stack->id())->either(
            off: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            on: static fn(ADistributor $through, AWakeToRegister $wake): HowThisPhoneIsWoken => HowThisPhoneIsWoken::on($through, $wake),
            owed: fn(APushName $name): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $name),
        );
    }

    /**
     * Keep the stack up with the distributor as a push wakes the phone for it.
     *
     * An endpoint the distributor changed is registered with the stack in place
     * of the old one; a registration the distributor dropped turns waking off.
     */
    public function followTheDistributor(Stack $stack, Session $session): HowThisPhoneIsWoken
    {
        return $this->forTheStack($stack->id())->either(
            off: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            on: fn(ADistributor $through, AWakeToRegister $wake): HowThisPhoneIsWoken => $this->distributor->endpointNowFor($stack->id())->either(
                given: fn(AnEndpoint $now): HowThisPhoneIsWoken => $now->is($wake->endpoint)
                    ? HowThisPhoneIsWoken::on($through, $wake)
                    : $this->movedTo($stack, $session, $through, $wake, $now),
                not: fn(): HowThisPhoneIsWoken => $this->turnOff($stack, $session),
            ),
            owed: fn(APushName $name): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $name),
        );
    }

    /**
     * Tell the stack to forget this phone's endpoint as it is removed from the phone, and answer whether it still holds it.
     *
     * Nothing is owed: the stack is gone from the phone with everything kept for it.
     */
    public function stillHeldAfterTelling(Stack $stack, Session $session): bool
    {
        $this->distributor->letGo($stack->id());

        return $this->forTheStack($stack->id())->either(
            off: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
            on: fn(ADistributor $through, AWakeToRegister $wake): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $wake->name),
            owed: fn(APushName $name): HowThisPhoneIsWoken => $this->removedOrOwed($stack, $session, $name),
        )->owesARemoval();
    }

    /**
     * Clear what the phone keeps alongside every other store, dropping every distributor registration and key pair.
     *
     * Each stack that held this phone's endpoint is owed its removal after,
     * sent on the next read of it that comes back.
     */
    public function clearedAlongside(ForgetsEverythingKept $everything, Configured $stacks): Forgotten
    {
        $owed = [];

        foreach ($stacks as $stack) {
            $waking = $this->forTheStack($stack->id())->either(
                off: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::off(),
                on: static fn(ADistributor $through, AWakeToRegister $wake): HowThisPhoneIsWoken => HowThisPhoneIsWoken::owed($wake->name),
                owed: static fn(APushName $name): HowThisPhoneIsWoken => HowThisPhoneIsWoken::owed($name),
            );

            if ($waking->owesARemoval()) {
                $owed[] = [$stack->id(), $waking];
            }
        }

        $this->distributor->letGoOfEvery();
        $forgotten = $everything->forgetEverything();

        foreach ($owed as [$stack, $waking]) {
            $this->keep($stack, $waking);
        }

        return $forgotten;
    }

    /** Forget whether the stack wakes this phone, and drop the distributor's registration and the key pair. */
    public function forgetTheStack(StackId $stack): Forgotten
    {
        $this->distributor->letGo($stack);

        return $this->kept->forget($this->seal->stack($stack));
    }

    /**
     * Whether anything is kept for the stack.
     *
     * Where nothing can be sealed, nothing could be read to say, so the answer
     * is yes: a removal that asks is told there may be something to forget.
     */
    public function keepsAnythingOf(StackId $stack): bool
    {
        if ($this->seal->standing() === SealStanding::Unavailable) {
            return true;
        }

        return $this->kept->found($this->seal->stack($stack))->holdsSomething();
    }

    /** Register the wake with the stack, and keep it where the stack keeps it; nothing is left behind where it does not. */
    private function registered(Stack $stack, Session $session, ADistributor $through, AWakeToRegister $wake): WhatTurningWakingOnCameTo
    {
        return $this->stacks->registeredOn($stack, $session, $wake)->either(
            registered: fn(AnEndpoint $held): WhatTurningWakingOnCameTo => $this->keptOn($stack, $session, $through, $wake->at($held)),
            refused: fn(ARefusalInItsWords $why): WhatTurningWakingOnCameTo => $this->letGoBecause($stack, WhyWakingStayedOff::refused($why)),
            met: fn(Obstacle $why): WhatTurningWakingOnCameTo => $this->letGoBecause($stack, WhyWakingStayedOff::met($why)),
        );
    }

    private function keptOn(Stack $stack, Session $session, ADistributor $through, AWakeToRegister $wake): WhatTurningWakingOnCameTo
    {
        return $this->keep($stack->id(), HowThisPhoneIsWoken::on($through, $wake))->either(
            down: static fn(): WhatTurningWakingOnCameTo => WhatTurningWakingOnCameTo::on($through),
            notKept: function () use ($stack, $session, $wake): WhatTurningWakingOnCameTo {
                $this->stacks->removedFrom($stack, $session, $wake->name);

                return $this->letGoBecause($stack, WhyWakingStayedOff::notKept());
            },
        );
    }

    private function letGoBecause(Stack $stack, WhyWakingStayedOff $why): WhatTurningWakingOnCameTo
    {
        $this->distributor->letGo($stack->id());

        return WhatTurningWakingOnCameTo::off($why);
    }

    /** Register the endpoint the distributor gives now in place of the one the stack holds, where the stack is reached. */
    private function movedTo(Stack $stack, Session $session, ADistributor $through, AWakeToRegister $wake, AnEndpoint $now): HowThisPhoneIsWoken
    {
        $moved = $wake->at($now);

        return $this->stacks->removedFrom($stack, $session, $wake->name)->either(
            removed: fn(): HowThisPhoneIsWoken => $this->stacks->registeredOn($stack, $session, $moved)->either(
                registered: fn(AnEndpoint $held): HowThisPhoneIsWoken => $this->kept($stack->id(), HowThisPhoneIsWoken::on($through, $moved->at($held))),
                refused: fn(): HowThisPhoneIsWoken => $this->turnOff($stack, $session),
                met: fn(): HowThisPhoneIsWoken => $this->turnOff($stack, $session),
            ),
            refused: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::on($through, $wake),
            met: static fn(): HowThisPhoneIsWoken => HowThisPhoneIsWoken::on($through, $wake),
        );
    }

    /** Tell the stack to forget the endpoint named `$name`, and keep what that leaves: off where it did, owed where it was not told. */
    private function removedOrOwed(Stack $stack, Session $session, APushName $name): HowThisPhoneIsWoken
    {
        return $this->stacks->removedFrom($stack, $session, $name)->either(
            removed: function () use ($stack): HowThisPhoneIsWoken {
                $this->kept->forget($this->seal->stack($stack->id()));

                return HowThisPhoneIsWoken::off();
            },
            refused: fn(): HowThisPhoneIsWoken => $this->kept($stack->id(), HowThisPhoneIsWoken::owed($name)),
            met: fn(): HowThisPhoneIsWoken => $this->kept($stack->id(), HowThisPhoneIsWoken::owed($name)),
        );
    }

    /** Keep this for the stack, and answer it. */
    private function kept(StackId $stack, HowThisPhoneIsWoken $waking): HowThisPhoneIsWoken
    {
        $this->keep($stack, $waking);

        return $waking;
    }

    private function keep(StackId $stack, HowThisPhoneIsWoken $waking): Noted
    {
        return $this->seal->seal(TheWakingAsKept::written($waking))->either(
            sealed: fn(SealedPayload $payload): Noted => $this->kept->keep($this->seal->stack($stack), $payload, Shape::current(), $this->clock->now()),
            refused: static fn(): Noted => Noted::notKept(),
        );
    }
}
