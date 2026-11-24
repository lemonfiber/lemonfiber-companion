<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARemoval;
use Modules\Kernel\Api\ARemovalAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
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
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheRemovalReads;
use Modules\Operator\Internal\ViewModels\TheRemovalTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;

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
 * **The yes quotes the reading it agrees to.** It carries the name the stack
 * gave that reading, so the stack refuses a yes given for a household that
 * has moved since. What taking somebody out does to their watch history is
 * the stack's sentence, drawn as it wrote it before the yes and after it.
 *
 * **How far it reached is the stack's word**, and only *everywhere* is drawn
 * as done. Every act is work the stack names and this follows through
 * {@see FollowsOneJobAtATime}: while it runs the screen shows what it says.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class TakingSomebodyOut extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;
    /** @use FollowsOneJobAtATime<TheRemovalTurnedOutToBe> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    public const string TEMPLATE = 'operator::taking-somebody-out';

    /**
     * What taking them out would cost, while it is in front of the operator.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * agreed against the reading itself.
     */
    public ?ARemoval $described = null;

    /** Whether the yes was sent, which is what an answer that cannot be read is about. */
    public bool $agreed = false;

    public function __construct(
        private readonly RemovingSomebody $removing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** The member this screen is about, by the name the route carries. */
    public function named(): string
    {
        return $this->around->aboutOn($this);
    }

    /**
     * Where taking them out has got to, asked once per frame.
     *
     * Asks after the work being followed where there is some, and otherwise
     * asks what taking them out would cost, which takes nobody out.
     */
    public function answer(): TheRemovalTurnedOutToBe
    {
        return $this->whatBecameOfTheJob();
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
        $this->forget();
        $this->reported($this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheRemoval => $this->removing->remove($stack, $session, $agreed),
        ));
    }

    /**
     * Ask again, because the operator said so.
     *
     * After work the stack took on and has not answered, it asks after the
     * same job. Otherwise it asks afresh what taking them out would cost,
     * which a yes has to be given to again: a yes that met something on the
     * way is never sent a second time on its own.
     */
    public function again(): void
    {
        if ($this->awaitsAnOutcome() || ($this->follows() && ! $this->answer()->stands->went->cameBack())) {
            $this->askAfterAgain();

            return;
        }

        $this->forget();
        $this->described = null;
        $this->agreed = false;
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): TheRemovalTurnedOutToBe
    {
        return $this->shown($this->removing->whatBecameOf($stack, $session, $job), $stack);
    }

    /** What taking them out would cost, asked where no work is followed; it takes nobody out. */
    protected function whileNoJobIsFollowed(): TheRemovalTurnedOutToBe
    {
        $named = $this->named();

        if (trim($named) === '') {
            return new HowTheRemovalReads()->namesNobody();
        }

        $who = SomebodyInTheHousehold::called($named);

        return $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheRemoval => $this->removing->wouldRemove($stack, $session, $who),
        );
    }

    protected function withNoSessionHeld(): TheRemovalTurnedOutToBe
    {
        return new HowTheRemovalReads()->signedOut($this->named());
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
                $this->follow($job);

                return new HowTheRemovalReads()->running($name, $agreed);
            },
            answered: function (ARemoval $removal): TheRemovalTurnedOutToBe {
                $this->described = $removal->wasCarriedOut() ? null : $removal;

                return new HowTheRemovalReads()->answered($removal);
            },
            ended: static fn(?WhyTheWorkEnded $why): TheRemovalTurnedOutToBe => new HowTheRemovalReads()->ended($why, $name, $agreed),
            refused: static fn(string $because): TheRemovalTurnedOutToBe => new HowTheRemovalReads()->refused($because, $name, $agreed),
            met: function (Obstacle $why) use ($stack, $name, $agreed): TheRemovalTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheRemovalReads()->met($why, $name, $agreed);
            },
        );
    }
}
