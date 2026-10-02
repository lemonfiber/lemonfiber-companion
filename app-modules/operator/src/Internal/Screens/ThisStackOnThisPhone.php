<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatBecameOfRemoving;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Stack settings: what this phone keeps of one stack, and Remove from phone.
 *
 * Removing asks on this page, saying what goes and what stays, and then
 * takes the stack's pairing, session, readings, settings and markers off the
 * phone as one act. The app then starts over on the next stack in the
 * operator's order, or on a first run where there is none: every screen of a
 * stack that is gone is gone with it.
 */
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class ThisStackOnThisPhone extends NativeComponent
{
    use FindsItsWayAround;

    /**
     * The stack this page is about, read once.
     *
     * Held, because the page is drawn once more as it hands over to the next
     * screen, and by then the stack is no longer on the phone to be read.
     */
    public ?Stack $held = null;

    /** Whether the operator is being asked whether to remove the stack. */
    public bool $confirmingTheRemoval = false;

    /** Whether the removal could not begin, and nothing was removed. */
    public bool $removalRefused = false;

    public function __construct(private readonly TheWayAround $around, private readonly RemovingAStack $removing) {}

    public function stack(): Stack
    {
        return $this->held ??= $this->around->stackNamed($this->param('stack'));
    }

    /** The operator asked to remove the stack; they are asked whether they mean it, on this page. */
    public function askToRemove(): void
    {
        $this->confirmingTheRemoval = true;
        $this->removalRefused = false;
    }

    /** The operator kept the stack. */
    public function keepTheStack(): void
    {
        $this->confirmingTheRemoval = false;
    }

    /**
     * The operator removed the stack from the phone.
     *
     * Where it lands is asked first, while the stack is still in the order.
     * A removal that began is as good as done to the operator, finished or
     * not: the stack is in no list from that moment, and what is left of it
     * is let go of when the app next opens.
     */
    public function removeTheStack(): void
    {
        $landing = $this->around->afterRemoving($this->stack());

        if ($this->removing->remove($this->stack()->id()) === WhatBecameOfRemoving::Refused) {
            $this->confirmingTheRemoval = false;
            $this->removalRefused = true;

            return;
        }

        $this->replaceTheWholeStack($landing);
    }

    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::this-stack-on-this-phone');
    }
}
