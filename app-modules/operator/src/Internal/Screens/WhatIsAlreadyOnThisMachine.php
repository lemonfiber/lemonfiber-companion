<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\TheSurvey;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTheSurveyReads;
use Modules\Operator\Internal\ViewModels\TheSurveyTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What is already on this machine that is not lemonfiber's, before anything is moved in.
 *
 * Every project and service the stack found comes first, with whether each
 * runs and whether it could be taken over; then what is in the way, what
 * cannot be taken over and what the layout costs; and the modes last, as the
 * stack orders them. A survey that could not look is told apart from one that
 * found nothing.
 *
 * It chooses no mode and carries out no remedy, and asks once, when the frame
 * is built.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class WhatIsAlreadyOnThisMachine extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheSurveyTurnedOutToBe $answered = null;

    public function __construct(
        private readonly MovingIn $movingIn,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, for
     * {@see WhatIsRunningHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Ask the machine again, which an obstacle must not take away. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::what-is-already-on-this-machine');
    }

    /** What came back, asked once per frame. */
    public function answer(): TheSurveyTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Resume the session, ask the machine, and flatten what came back. */
    private function ask(): TheSurveyTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheSurveyTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): TheSurveyTurnedOutToBe => new HowTheSurveyReads()->signedOut(),
        );
    }

    /** What the machine found already on it, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): TheSurveyTurnedOutToBe
    {
        return $this->movingIn->surveyedOn($stack, $session)->either(
            found: static fn(TheSurvey $survey): TheSurveyTurnedOutToBe => new HowTheSurveyReads()->this($survey),
            met: function (Obstacle $why) use ($stack): TheSurveyTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSurveyReads()->met($why);
            },
        );
    }
}
