<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\Presenters\HowAVerbReads;
use Modules\Operator\Internal\Presenters\HowOneThingReads;
use Modules\Operator\Internal\ViewModels\WhatAVerbTakesAwaySays;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;
use Modules\Operator\Internal\ViewModels\WhatOneThingIs;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * One thing this machine runs, and what may be done with it (`N2-R7`).
 *
 * The verbs are here rather than on {@see WhatThisStackRuns} because a list is
 * read and a verb is chosen, and the two acts do not want the same frame. Drawn
 * once per row, four services put fifteen controls on one screen and four of
 * them answer to *Start it*: which service a control acts on is then carried by
 * where it sits, and position is the one thing somebody being read to cannot
 * check.
 *
 * **A service and a form arrive here together, and that is not a shortcut.**
 * `N2-R7` asks for both granularities and what an operator is choosing between
 * is identical either way; what the stack is told differs only in which name it
 * carries. A second screen would be this one with a word changed, and two
 * screens offering one decision is how they come to offer it differently.
 *
 * **The yes is built from the listing, never from the route.** `N2-R8` wants a
 * disruptive action to state what it disturbs before it is confirmed, and the
 * way that requirement is broken is never deliberate: a handler passes its
 * argument straight to the port. So {@see wouldYouLike()} takes the verb alone,
 * finds what the URI names in what was actually read, and builds
 * {@see AgreedTo} from *that* — a name this screen never read cannot be acted
 * on, however it arrives.
 *
 * **A service wins over a form where both could match.** That is a stack which
 * named a service after its form, and the narrower reading is the safer one:
 * agreeing about one service and being sent a whole form is the mistake that
 * costs a household something.
 *
 * **A start is not confirmed and the other two are.** That line is
 * {@see WhatToDoWithIt::takesSomethingAway()}'s and is not redrawn here. A
 * screen that asked about a start would be teaching an operator to confirm
 * without reading, which is what makes the stop confirmation worth anything.
 *
 * **It polls only while something is settling** (`N1-R27`). A service that is
 * starting becomes a running one on its own, and *ask again* as the only road
 * to finding out is the reliance on leaving and returning that rule refuses.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and `N4-R13`'s diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatToDoWithThis extends NativeComponent
{
    use AsksWhatTheStackIsRunning;

    /** What the operator has been asked about, where a verb is waiting on a yes. */
    protected ?AgreedTo $asking = null;

    public function __construct(
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * What came back, asked once per frame (`N1-R65`).
     *
     * One accessor handing out the whole fold rather than one per field, which
     * is what keeps this screen under `H3`'s twenty methods. The asking itself
     * is {@see AsksWhatTheStackIsRunning}'s, and is handed what it needs.
     */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->answered ??= $this->askWhatIsRunning($this->stack(), $this->storage, $this->supervising);
    }

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * What the route names, and what may be done with it.
     *
     * One accessor handing out the value it folded, which is `H3`'s own advice
     * for a screen: an accessor per field is what takes a class past twenty,
     * and the four answers here are one answer.
     */
    public function thing(): WhatOneThingIs
    {
        $named = $this->param('service');

        return new HowOneThingReads()->of($this->answer(), is_string($named) ? trim($named) : '');
    }

    /**
     * Ask about a verb, or carry it out where it takes nothing away (`N2-R8`).
     *
     * Where the verb takes something away it is held rather than carried out,
     * and {@see agree()} is the only thing that sends it. That is `N2-R8` in
     * the shape of a method: this one cannot act on a disruptive verb however
     * it is called.
     */
    public function wouldYouLike(string $doing): void
    {
        $verb = WhatToDoWithIt::tryFrom($doing);

        if (! $verb instanceof WhatToDoWithIt) {
            return;
        }

        $agreed = $this->agreementFor($verb);

        if (! $agreed instanceof AgreedTo) {
            return;
        }

        if ($agreed->doing()->takesSomethingAway()) {
            $this->asking = $agreed;

            return;
        }

        $this->send($agreed);
    }

    /**
     * Carry out what the operator has just agreed to (`N2-R8`).
     *
     * It sends what was held and nothing a template passed in, so the thing
     * that was confirmed and the thing that happens are the same value.
     */
    public function agree(): void
    {
        $agreed = $this->asking;

        if (! $agreed instanceof AgreedTo) {
            return;
        }

        $this->asking = null;

        $this->send($agreed);
    }

    /** Put the question away without doing anything about it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /**
     * How long the pending question would take its subject away for (`N2-R8`).
     *
     * Read off the same listing the question was built from, so the number an
     * operator confirms on is the one the stack reported on the reading they
     * are looking at — not one fetched when they tapped, and not one this app
     * worked out. `N2-R14` forbids the second, and the first would be a
     * different stack's answer by the time it arrived.
     */
    public function whatItTakesAway(): ?WhatAVerbTakesAwaySays
    {
        $agreed = $this->asking;
        $disturbs = $this->answer()->disturbs;

        if (! $agreed instanceof AgreedTo || ! $disturbs instanceof Disturbances) {
            return null;
        }

        return new HowAVerbReads()->of($disturbs->forThe($agreed->doing()));
    }

    /**
     * What the operator is being asked about, or nothing where they are not.
     *
     * The value itself rather than a flag beside it, so the template renders
     * the sentence from what will actually be sent — a screen that stated one
     * service and held another is exactly the failure `N2-R8` is about.
     */
    public function asking(): ?AgreedTo
    {
        return $this->asking;
    }

    /**
     * Look again while the machine is settling into what it was told (`N1-R27`).
     *
     * It does nothing unless something is actually settling, which is what
     * keeps this from being the polling `N1-R66` refuses: a stack whose
     * services are all in standing states answers the same thing however often
     * it is read.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItSettles(): void
    {
        if (! $this->answer()->isSettling) {
            return;
        }

        $this->again();
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination: {@see WhereAStackIs} is the
     * only place that knows a stack's routes.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-to-do-with-this');
    }

    /**
     * The agreement a verb amounts to, against what was read.
     *
     * A service wins over a form where both could match, for the reason the
     * class docblock gives.
     */
    private function agreementFor(WhatToDoWithIt $doing): ?AgreedTo
    {
        $thing = $this->thing();

        if ($thing->service instanceof WhatOneServiceSays) {
            return AgreedTo::theService($doing, ServiceId::called($thing->named));
        }

        return $thing->isAForm ? AgreedTo::theForm($doing, Form::called($thing->named)) : null;
    }

    /**
     * Send it, and forget what was read.
     *
     * The listing in front of the operator is about the machine as it was
     * before they said anything, so the next accessor asks again.
     *
     * **What the verb answered is not kept.** A job name has nothing to be
     * redeemed for on this screen — what the operator wants to know is whether
     * the service is running, which the listing says — and a verb that could
     * not be delivered shows as the obstacle the next read meets, because it is
     * the same obstacle.
     */
    private function send(AgreedTo $agreed): void
    {
        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AsText => $this->tell($stack, $session, $agreed),
            notHeld: static fn(): AsText => AsText::nothing(),
        );

        $this->answered = null;
    }

    /**
     * Hand it to the port, and fold both arms to the same shape.
     *
     * Split out because `either()` wants two arms answering one type and a
     * closure that assigned a property in one of them would be doing the work
     * where the shape is being decided.
     */
    private function tell(Stack $stack, Session $session, AgreedTo $agreed): AsText
    {
        return $this->supervising->told($stack, $session, $agreed)->either(
            started: static fn(Job $job): AsText => AsText::of($job->shown()),
            met: function (Obstacle $why) use ($stack): AsText {
                // The verb's refusal lets go of the session too, and not only
                // the reading's. A stack that refuses a credential on a stop is
                // the same signed-out device as one that refuses it on a read,
                // and this is the call that happens the moment somebody taps.
                $this->letGoOfTheSession($why, $stack);

                return AsText::of($why->said());
            },
        );
    }
}
