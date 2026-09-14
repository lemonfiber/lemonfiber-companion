<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function in_array;
use function is_string;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Daemons;
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
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\WhatOneServiceSays;
use Modules\Operator\Internal\WhatThisStackRunsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine is running, and the three things to do about it.
 *
 * `N2-R7` asks the app to offer start, stop and restart by form and by service,
 * and this is where that is offered. A screen of its own rather than a section
 * of {@see HowThisStackIs}: health answers *is anything wrong*, and this
 * answers *what is on, and what do I want on* — which an operator opens the app
 * for on an evening when every check passes and the film still will not play.
 *
 * **The yes is built from the listing, never from the tap.** `N2-R8` wants a
 * disruptive action to state what it disturbs before it is confirmed, and the
 * way that requirement is broken is never deliberate: a template draws a row,
 * the stop button is right there, and a handler passes its argument straight to
 * the port. So {@see wouldYouLike()} takes two names, finds the row they belong
 * to in what was actually read, and builds {@see AgreedTo} from *that* — a name
 * this screen never read cannot be acted on, whatever a template sends.
 *
 * **A start is not confirmed and the other two are.** That line is
 * {@see WhatToDoWithIt::takesSomethingAway()}'s and is not redrawn here. A
 * screen that asked about a start would be teaching an operator to confirm
 * without reading, which is what makes the stop confirmation worth anything.
 *
 * **It does not say how long a stop lasts, and that is deliberate.** `N2-R8`
 * asks for the bound the stack reported or for the fact that it reported none,
 * and the contract carries neither for a lifecycle verb — there is no field on
 * the `lifecycle` envelope for it and no unconfirmed form of these three to ask
 * through. `N2-R14` says the app must not substitute one, and *the stack
 * reported none* would be substituting: nothing asked it. The gap is held by
 * `WhatTheContractDoesNotCarryTest`, which fails the day lemonfiber carries the
 * field, and the sentence belongs here when it does.
 *
 * **It polls only while something is settling** (`N1-R27`). A service that is
 * starting becomes a running one on its own, and *ask again* as the only road
 * to finding out is the reliance on leaving and returning that rule refuses.
 * Every other state here is standing, so the cadence costs a machine on a home
 * network nothing the rest of the time — which is what keeps this from being
 * the polling `N1-R17` refuses.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and `N4-R13`'s diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatThisStackRuns extends NativeComponent
{
    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatThisStackRunsTurnedOutToBe $answered = null;

    /** What the operator has been asked about, where a verb is waiting on a yes. */
    protected ?AgreedTo $asking = null;

    public function __construct(
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see cadence()}'s shape a level up and for the same reason: this screen
     * offers six verbs and a confirmation, and a method per field took it past
     * the twenty `Q-R64` allows — at which point the next fact the template
     * needs costs a method somewhere else. The template reads `->met` and
     * `->isSettling` off what one asking produced, which is also the only thing
     * that could be true of them.
     */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names — the argument
     * {@see HowThisStackIs::stack()} makes, and the same refusal for a route
     * naming a stack this device has forgotten.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /**
     * Say a verb about a service or a form (`N2-R7`).
     *
     * Both names arrive as text because a template has nothing else to send,
     * and both are resolved against what was actually read: a service must be a
     * row in this listing and a form must be one of its forms. A name that is
     * neither is silently nothing, which is the same answer
     * {@see WhatWouldBePutRight::agreeTo()} gives and for its reason — there is
     * no button for it on the screen, so a refusal would be a sentence about
     * something the template does not draw.
     *
     * Where the verb takes something away it is held rather than carried out,
     * and {@see agree()} is the only thing that sends it. That is `N2-R8` in
     * the shape of a method: this one cannot act on a disruptive verb however
     * it is called.
     */
    public function wouldYouLike(string $doing, string $about): void
    {
        $verb = WhatToDoWithIt::tryFrom($doing);

        if (! $verb instanceof WhatToDoWithIt) {
            return;
        }

        $agreed = $this->agreementFor($verb, $about);

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
     * that was confirmed and the thing that happens are the same value — the
     * argument `N2-R6` makes about a repair, applied to a verb.
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
     * The row a pending question is about, where it is about a service.
     *
     * What a stop disturbs is the other services that will not work without
     * this one, and only the row carries them. Nothing where the question is
     * about a form: a form's statement is the form, and its services are every
     * row that names it.
     */
    public function aboutTheService(): ?WhatOneServiceSays
    {
        $agreed = $this->asking;

        if (! $agreed instanceof AgreedTo || $agreed->isAboutAForm()) {
            return null;
        }

        return $this->row(ServiceId::called($agreed->named()));
    }

    /**
     * How often this screen looks again (`N1-R27`).
     *
     * The stated half of the requirement, and {@see WhatWouldBePutRight::cadence()}'s
     * shape: one accessor handing out the case, so the sentence a template
     * builds and the interval the poll keeps cannot drift apart.
     */
    public function cadence(): HowOften
    {
        return HowOften::WhileWorkRuns;
    }

    /**
     * Ask the stack again, because the operator said so (`N1-R3`).
     *
     * The action an obstacle must not take away: `N1-R3` says a control is not
     * hidden because the stack is unreachable, and an obstacle screen with
     * nothing on it leaves leaving and returning as the only road back.
     */
    public function again(): void
    {
        $this->answered = null;
    }

    /**
     * Look again while the machine is settling into what it was told (`N1-R27`).
     *
     * It does nothing unless something is actually settling, which is what
     * keeps this from being the polling `N1-R17` refuses: a stack whose
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
     * One accessor rather than one per destination, which is
     * {@see WhatStoppedComingIn::goes()}'s argument: {@see WhereAStackIs} is
     * the only place that knows a stack's routes.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-this-stack-runs');
    }



    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatThisStackRunsTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackRunsTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatThisStackRunsTurnedOutToBe
                => WhatThisStackRunsTurnedOutToBe::signedOut(),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatThisStackRunsTurnedOutToBe
    {
        return $this->supervising->running($stack, $session)->either(
            these: static fn(Daemons $daemons): WhatThisStackRunsTurnedOutToBe
                => WhatThisStackRunsTurnedOutToBe::these($daemons),
            met: static fn(Obstacle $why): WhatThisStackRunsTurnedOutToBe
                => WhatThisStackRunsTurnedOutToBe::met($why),
        );
    }

    /**
     * The agreement a verb and a name amount to, against what was read.
     *
     * A service wins over a form where both could match, which is a stack that
     * named a service after its form. The narrower reading is the safer one:
     * agreeing about one service and being sent a whole form is the mistake
     * that costs a household something.
     */
    private function agreementFor(WhatToDoWithIt $doing, string $about): ?AgreedTo
    {
        $service = ServiceId::called($about);

        if ($this->row($service) instanceof WhatOneServiceSays) {
            return AgreedTo::theService($doing, $service);
        }

        if (in_array($about, $this->answer()->forms, strict: true)) {
            return AgreedTo::theForm($doing, Form::called($about));
        }

        return null;
    }

    /** The row of that name in what was read, or nothing where there is none. */
    private function row(ServiceId $service): ?WhatOneServiceSays
    {
        foreach ($this->answer()->services as $row) {
            if ($row->is($service)) {
                return $row;
            }
        }

        return null;
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
            met: static fn(Obstacle $why): AsText => AsText::of($why->said()),
        );
    }
}
