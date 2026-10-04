<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\RemovingSomebody;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfTheRemoval;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheRemovalReads;
use Modules\Operator\Internal\ViewModels\TheRemovalTurnedOutToBe;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Taking one member out of the household: what it would cost first, then the yes, then how far it reached.
 *
 * **What it costs is read before anything is offered.** Opening the screen
 * asks the stack what taking them out would cost, taking nobody out: how many
 * of their requests are destroyed with them, whether they ask through the
 * request service, and what the stack found on the way. Only beneath that is
 * the yes offered.
 *
 * **The yes is given here and nowhere else.** The reading it is agreed
 * against is held for as long as it is on this screen, and a yes is sent only
 * against that reading; nothing is carried in from another screen, and a
 * reading asked for again is agreed to again.
 *
 * **How far it reached is the stack's word**, and only *everywhere* is drawn
 * as done. Every act is work the stack names and this follows, at
 * {@see HowOftenAScreenLooks::WhileWorkRuns} while it runs.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class TakingSomebodyOut extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What taking them out would cost, while it is in front of the operator.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * agreed against the reading itself.
     */
    public ?ARemoval $described = null;

    /** The handle of the work being followed, while there is one. Not shown and never kept past this screen. */
    public ?string $following = null;

    /** Whether the yes was sent, which is what an answer that cannot be read is about. */
    public bool $agreed = false;

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheRemovalTurnedOutToBe $going = null;

    public function __construct(
        private readonly RemovingSomebody $removing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}



    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->answer()->isWorking;
    }

    public function render(): View
    {
        return view('operator::taking-somebody-out');
    }

    /** The member this screen is about, by the name the route carries. */
    public function named(): string
    {
        $named = $this->param('service');

        return is_string($named) ? $named : '';
    }

    /**
     * Where taking them out has got to, asked once per frame.
     *
     * Asks after the work being followed where there is some, and otherwise
     * asks what taking them out would cost, which takes nobody out.
     */
    public function answer(): TheRemovalTurnedOutToBe
    {
        return $this->going ??= $this->asked();
    }

    /**
     * Take them out, as the reading on the screen described.
     *
     * Silent where no reading is held, which is a frame that has not read one
     * or an answer already carried out: there is nothing to agree to.
     */
    public function agree(): void
    {
        $described = $this->described;

        if (! $described instanceof ARemoval || $described->wasCarriedOut()) {
            return;
        }

        $agreed = ARemovalAgreed::after($described);
        $this->described = null;
        $this->agreed = true;

        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheRemoval => $this->removing->remove($stack, $session, $agreed),
        );
    }

    /**
     * Ask again, because the operator said so.
     *
     * After work the stack took on, it asks after the same handle. Otherwise
     * it asks afresh what taking them out would cost, which a yes has to be
     * given to again: a yes that met something on the way is never sent a
     * second time on its own.
     */
    public function again(): void
    {
        $this->going = null;

        if (is_string($this->following)) {
            return;
        }

        $this->described = null;
        $this->agreed = false;
    }

    /**
     * Ask after the work again while the stack is carrying it out.
     *
     * Nothing happens unless it is running, so a finished answer is not asked
     * for again. The interval is {@see HowOftenAScreenLooks}'s.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->going = null;
        }
    }

    /** What the stack is asked this frame: after the work being followed, or what taking them out would cost. */
    private function asked(): TheRemovalTurnedOutToBe
    {
        $following = $this->following;

        if (is_string($following)) {
            return $this->put(
                fn(Stack $stack, Session $session): WhatBecameOfTheRemoval => $this->removing->whatBecameOf($stack, $session, Job::named($following)),
            );
        }

        $named = $this->named();

        if (trim($named) === '') {
            return new HowTheRemovalReads()->namesNobody();
        }

        $who = SomebodyInTheHousehold::called($named);

        return $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheRemoval => $this->removing->wouldRemove($stack, $session, $who),
        );
    }

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheRemoval $asking
     */
    private function put(Closure $asking): TheRemovalTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheRemovalTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: fn(): TheRemovalTurnedOutToBe => new HowTheRemovalReads()->signedOut($this->named()),
        );
    }

    /** What the stack said, as the screen draws it, holding what to follow or to agree to. */
    private function shown(WhatBecameOfTheRemoval $became, Stack $stack): TheRemovalTurnedOutToBe
    {
        $name = $this->named();
        $agreed = $this->agreed;

        return $became->either(
            underway: function (Job $job) use ($name, $agreed): TheRemovalTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheRemovalReads()->running($name, $agreed);
            },
            answered: function (ARemoval $removal): TheRemovalTurnedOutToBe {
                $this->following = null;
                $this->described = $removal->wasCarriedOut() ? null : $removal;

                return new HowTheRemovalReads()->answered($removal);
            },
            ended: function () use ($name, $agreed): TheRemovalTurnedOutToBe {
                $this->following = null;

                return new HowTheRemovalReads()->ended($name, $agreed);
            },
            refused: function (string $because) use ($name, $agreed): TheRemovalTurnedOutToBe {
                $this->following = null;

                return new HowTheRemovalReads()->refused($because, $name, $agreed);
            },
            met: function (Obstacle $why) use ($stack, $name, $agreed): TheRemovalTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRemovalReads()->met($why, $name, $agreed);
            },
        );
    }
}
