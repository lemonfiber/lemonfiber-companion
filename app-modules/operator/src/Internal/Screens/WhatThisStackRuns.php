<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this machine is running, and the three things to do about it.
 *
 * The app offers start, stop and restart by form and by service,
 * and this is where that is offered. A screen of its own rather than a section
 * of {@see HowThisStackIs}: health answers *is anything wrong*, and this
 * answers *what is on, and what do I want on* — which an operator opens the app
 * for on an evening when every check passes and the film still will not play.
 *
 * **The yes is built from the listing, never from the tap.** A
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
 * **It says how long a stop lasts, which it could not until recently.** The rule
 * asks for the bound the stack reported or for the fact that it reported none,
 * and for a long time no payload carried either — the gap was held by
 * `WhatTheContractDoesNotCarryTest`, which went red the day lemonfiber began
 * reporting it and named this requirement to go and answer. The sentence is in
 * the confirmation now, and the number is the stack's: this app may not
 * side inventing one, and a length worked out here would be a guess at
 * something the stack knows, wrong in exactly the cases somebody most needs it.
 *
 * That row named the `lifecycle` envelope at first, on the reasoning that a
 * bound would arrive where what an operation touched already arrives. It
 * arrived on the reading instead, because a bound is only any use *before* the
 * verb runs — which is why a register watches for a fact and not for a place.
 *
 * **It polls only while something is settling.** A service that is
 * starting becomes a running one on its own, and *ask again* as the only road
 * to finding out is the reliance on leaving and returning that rule refuses.
 * Every other state here is standing, so the cadence costs a machine on a home
 * network nothing the rest of the time — which is what keeps this from being
 * the polling that is refused.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
final class WhatThisStackRuns extends NativeComponent
{
    use AsksWhatTheStackIsRunning;

    public function __construct(
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * What came back, asked once per frame.
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
     * Look again while the machine is settling into what it was told.
     *
     * It does nothing unless something is actually settling, which is what
     * keeps this from being the polling that is refused: a stack whose
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



}
