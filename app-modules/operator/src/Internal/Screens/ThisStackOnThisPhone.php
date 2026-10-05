<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Connection\Api\RemovingAStack;
use Modules\Connection\Api\WhatBecameOfRemoving;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\News\Api\KindOfNews;
use Modules\News\Api\MarkingAsNew;
use Modules\Operator\Internal\ViewModels\AKindOfNewsAsShown;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * Stack settings: what this phone keeps of one stack, and Remove from phone.
 *
 * Removing asks on this page, saying what goes and what stays, and then
 * takes the stack's pairing, session, readings, settings and markers off the
 * phone as one act. The app then starts over on the next stack in the
 * operator's order, or on a first run where there is none: every screen of a
 * stack that is gone is gone with it.
 */
#[Lazy]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class ThisStackOnThisPhone extends NativeComponent
{
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::this-stack-on-this-phone';

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

    public function __construct(
        private readonly TheWayAround $around,
        private readonly RemovingAStack $removing,
        private readonly MarkingAsNew $marking,
        protected readonly SecureStorage $storage,
        protected readonly WhatItListensWith $listening,
    ) {}

    public function stack(): Stack
    {
        return $this->held ??= $this->around->stackOn($this);
    }

    /** The operator asked to remove the stack; they are asked whether they mean it, on this page. */
    /**
     * Each kind the stack can mark as new, and whether it does.
     *
     * @return list<AKindOfNewsAsShown>
     */
    public function kindsOfNews(): array
    {
        $marked = $this->marking->marked($this->stack()->id());
        $shown = [];

        foreach (KindOfNews::cases() as $kind) {
            $shown[] = new AKindOfNewsAsShown($kind->value, $kind->saidOnTheScreen(), $marked->include($kind));
        }

        return $shown;
    }

    /**
     * Mark a kind as new, or no longer, as its switch now stands.
     *
     * Set to where the switch stands rather than turned over, because the
     * platform hands the switch's state back with every change it reports, one
     * it reports as it draws the switch included, and a kind that flipped on
     * each would end up wherever the count of reports left it. A name the page
     * never drew changes nothing.
     */
    public function markAsNew(string $kind, bool $isMarked): void
    {
        $chosen = KindOfNews::tryFrom($kind);

        if (! $chosen instanceof KindOfNews) {
            return;
        }

        if ($isMarked) {
            $this->marking->mark($this->stack()->id(), $chosen);

            return;
        }

        $this->marking->markNoLonger($this->stack()->id(), $chosen);
    }

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

}
