<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Contract\Api;
use Modules\Kernel\Api\Ability;
use Modules\Kernel\Api\AnAction;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\Forgotten;
use Modules\Kernel\Api\KnowingWhatAStackOffers;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\WhetherItIsOffered;
use Modules\Sdk\Internal\WhatEachStackOffers;

/**
 * What a stack says it can do, as a screen asks before drawing a button.
 *
 * Answered from what {@see WhatEachStackOffers} holds, which is what
 * {@see ClientsThatAskWhatIsOffered} asks before anything is sent, so a button
 * and the request behind it are answered from the same declaration. Asking
 * again lets go of it, and the next question asks the stack, on a frame of its
 * own: the button is drawn on the next one.
 */
final readonly class Assessors implements ForgetsAStack, KnowingWhatAStackOffers
{
    public function __construct(
        private Clients $clients,
        private Clock $clock,
        private WhatEachStackOffers $held,
    ) {}

    public function whetherItOffers(Stack $stack, Session $session, AnAction $action): WhetherItIsOffered
    {
        return $this->held->forAButton(
            $stack,
            $session,
            $this->clock->now(),
            $this->clients->client($stack, $session),
            Ability::of(Api::action($action->asked())),
        );
    }

    public function askAgain(StackId $stack): Forgotten
    {
        return $this->held->letGoOf($stack) ? Forgotten::rows(1) : Forgotten::nothing();
    }

    public function aScreenOpens(): Forgotten
    {
        $old = $this->held->letGoOfWhatIsOlderThanABreak($this->clock->now());

        return $old === 0 ? Forgotten::nothing() : Forgotten::rows($old);
    }

    /** What the stack said goes with the stack, which asking again is the same letting go of. */
    public function forgetTheStack(StackId $stack): Forgotten
    {
        return $this->askAgain($stack);
    }

    public function keepsAnythingOf(StackId $stack): bool
    {
        return $this->held->holds($stack);
    }
}
