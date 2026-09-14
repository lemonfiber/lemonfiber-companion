<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A verb the operator said yes to, against the thing it will be done to.
 *
 * {@see Confirmed}'s argument applied to a service. `N2-R8` wants a disruptive
 * action to state what it disturbs before it is confirmed, and the way that
 * requirement is broken is never a deliberate decision: a screen draws a row,
 * the stop button is right there, and somewhere a tap handler calls the thing
 * that stops it. Nothing in the code says *this was confirmed*, because nothing
 * had to.
 *
 * So {@see Supervising::told()} takes one of these, and the only ways to make
 * one need the thing and the verb named together with what the operator was
 * shown. Rendering a listing produces no `AgreedTo` and cannot be made to.
 *
 * **Two constructors, because a form and a service are different acts.** Not a
 * nullable field told apart by an `instanceof` (`C2`): an operator stopping
 * *media* and one stopping *sonarr* have agreed to different amounts of
 * disruption, and the confirmation they were shown said different things. A
 * single constructor taking either would let a screen confirm about one and act
 * on the other.
 *
 * **Every verb arrives here the same shape, including a start.** Which of them
 * owes an operator a sentence beforehand is
 * {@see WhatToDoWithIt::takesSomethingAway()}'s decision and is made on the
 * screen; this type carries what was agreed, whichever verb it was. One shape
 * means the port has one signature, and a start being a tap rather than a
 * dialog is a difference in what the screen draws rather than in what it hands
 * over.
 *
 * **One field rather than two nullable ones.** A pair would have a third state
 * neither constructor can reach — agreed about neither a service nor a form —
 * and every reader would carry a branch for it, ending in a blank name or a
 * raise nothing can cause. A union has the two states the type has, so the
 * name is read without asking which arm it came from: {@see ServiceId} and
 * {@see Form} each answer {@see ServiceId::named()}, and only the argument it
 * travels under differs.
 */
final readonly class AgreedTo
{
    private function __construct(
        private WhatToDoWithIt $doing,
        private ServiceId|Form $about,
    ) {}

    /** The operator agreed to this, for one service. */
    public static function theService(WhatToDoWithIt $doing, ServiceId $service): self
    {
        return new self($doing, $service);
    }

    /** The operator agreed to this, for a whole form. */
    public static function theForm(WhatToDoWithIt $doing, Form $form): self
    {
        return new self($doing, $form);
    }

    /** Which of the three was agreed to. */
    public function doing(): WhatToDoWithIt
    {
        return $this->doing;
    }

    /**
     * What it was agreed about, as the name an adapter asks with.
     *
     * One accessor rather than two, because the caller that needs this is the
     * adapter putting it on the wire and it treats the two the same — the
     * difference between a form and a service is in *what was confirmed*, which
     * is the screen's business and is settled before anything reaches here.
     */
    public function named(): string
    {
        return $this->about->named();
    }

    /** Whether this was agreed about a whole form rather than one service. */
    public function isAboutAForm(): bool
    {
        return $this->about instanceof Form;
    }
}
