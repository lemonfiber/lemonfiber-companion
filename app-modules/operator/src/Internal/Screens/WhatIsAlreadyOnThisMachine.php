<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AMove;
use Modules\Kernel\Api\AMoveAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\MovingIn;
use Modules\Kernel\Api\MovingInBy;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stance;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheSurvey;
use Modules\Kernel\Api\WhatBecameOfTheMove;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAMoveReads;
use Modules\Operator\Internal\Presenters\HowTheSurveyReads;
use Modules\Operator\Internal\ViewModels\TheMoveTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheSurveyTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

/**
 * What is already on this machine that is not lemonfiber's, and moving in beside it.
 *
 * Every project and service the stack found comes first, with whether each
 * runs and whether it could be taken over; then what is in the way, what
 * cannot be taken over and what the layout costs; and the modes last, as the
 * stack orders them. A survey that could not look is told apart from one that
 * found nothing. It carries out no remedy, and reads the survey once, when
 * the frame is built.
 *
 * **Nothing is moved until the operator has seen what it would come to.** A
 * mode is asked about first, without the yes, and what comes back — where it
 * stands, what would not come across, what is copied first — is drawn before
 * anything else is offered. The yes is a second tap, and only beneath an
 * answer that is pending; it sends the act that answer was about.
 *
 * **Every answer is work the stack names and this follows**, so the handle is
 * held and asked after at {@see HowOftenAScreenLooks::WhileWorkRuns} while it runs, the
 * way {@see AskingSomebodyIn} follows an invitation.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatIsAlreadyOnThisMachine extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-is-already-on-this-machine';

    /**
     * What came back, once the frame has asked.
     *
     * `public`, for the reason {@see WhatIsRunningHere::$answered} gives.
     */
    public ?TheSurveyTurnedOutToBe $answered = null;

    /** The word of the mode being asked about, which what comes back is drawn with. */
    public string $about = '';

    /** The handle of the work being followed, while there is one. */
    public ?string $following = null;

    /** The move the stack last answered pending, which is the only one a yes is sent for. */
    public ?AMove $staged = null;

    /** Where moving in has got to, once this frame has asked. Public for {@see self::$answered}'s reason. */
    public ?TheMoveTurnedOutToBe $going = null;

    /** The pending move last agreed to, so a yes other work held can be sent again for it. */
    public ?AMove $agreedOn = null;

    public function __construct(
        private readonly MovingIn $movingIn,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /**
     * Ask the machine again, which an obstacle must not take away.
     *
     * Asks after the work being followed where there is some. A request that
     * never reached the stack is not sent again on its own.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->going = null;
    }

    /**
     * Ask what moving in this way would come to, doing nothing.
     *
     * **Refuses a mode the survey did not list**, for
     * {@see AskingSomebodyIn::wouldTakeThePasswordOff()}'s reason: the control
     * is drawn only beside a listed mode, and a screen whose guarantee lived
     * in its template would ask about whatever a tap named.
     */
    public function wouldMoveIn(string $mode): void
    {
        $by = MovingInBy::tryFrom($mode);

        foreach ($this->answer()->modes as $offered) {
            if ($by instanceof MovingInBy && $offered->mode === $mode) {
                $this->leaveIt();
                $this->going = $this->put(
                    $mode,
                    fn(Stack $stack, Session $session): WhatBecameOfTheMove => $this->movingIn->wouldMoveIn($stack, $session, $by),
                );

                return;
            }
        }
    }

    /**
     * Move in as the answer on the screen described.
     *
     * Reached only beneath a pending answer; a tap from anywhere else does
     * nothing, rather than sending an act nobody was shown.
     */
    public function moveIn(): void
    {
        $staged = $this->staged;

        if (! $staged instanceof AMove || $staged->stance() !== Stance::Pending) {
            return;
        }

        $agreed = AMoveAgreed::after($staged);
        $this->agreedOn = $staged;
        $this->staged = null;

        $this->going = $this->put(
            $agreed->by()->value,
            fn(Stack $stack, Session $session): WhatBecameOfTheMove => $this->movingIn->moveIn($stack, $session, $agreed),
        );
    }

    /** Leave the answer where it is and go back to the modes, moving nothing. */
    public function leaveIt(): void
    {
        $this->agreedOn = null;
        $this->about = '';
        $this->following = null;
        $this->staged = null;
        $this->going = null;
    }

    /**
     * Send the request other work held again: the yes, or asking what a mode would come to.
     *
     * Only where other work held the stack, for
     * {@see \Modules\Operator\Internal\FollowsWhatTheVerbCameTo::tryAgain()}'s
     * reason. A yes is sent for the same pending move it was agreed to on;
     * with no yes outstanding, the request held was the question.
     */
    public function tryAgain(): void
    {
        if ($this->going?->went->wasHeldByOtherWork() !== true) {
            return;
        }

        $agreedOn = $this->agreedOn;

        if (! $agreedOn instanceof AMove) {
            $this->wouldMoveIn($this->about);

            return;
        }

        $this->staged = $agreedOn;
        $this->moveIn();
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
        if ($this->howTheMoveIsGoing()->isWorking) {
            $this->going = null;
        }
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howTheMoveIsGoing()->isWorking;
    }

    /** What came back, asked once per frame. */
    public function answer(): TheSurveyTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Where moving in has got to, asked once per frame.
     *
     * Asks the stack after the work being followed where there is some, and
     * otherwise says nothing has been asked.
     */
    public function howTheMoveIsGoing(): TheMoveTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowAMoveReads()->notAsked()
            : $this->put(
                $this->about,
                fn(Stack $stack, Session $session): WhatBecameOfTheMove => $this->movingIn->whatBecameOf($stack, $session, Job::named($following)),
            );
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

    /**
     * One act put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheMove $asking
     */
    private function put(string $mode, Closure $asking): TheMoveTurnedOutToBe
    {
        $stack = $this->stack();
        $this->about = $mode;

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheMoveTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheMoveTurnedOutToBe => new HowAMoveReads()->signedOut($mode),
        );
    }

    /** What the stack said, as the screen draws it, holding what to follow or to agree to. */
    private function shown(WhatBecameOfTheMove $became, Stack $stack): TheMoveTurnedOutToBe
    {
        $mode = $this->about;

        return $became->either(
            underway: function (Job $job) use ($mode): TheMoveTurnedOutToBe {
                $this->following = $job->shown();

                return new HowAMoveReads()->running($mode);
            },
            answered: function (AMove $move): TheMoveTurnedOutToBe {
                $this->following = null;
                $this->staged = $move->stance() === Stance::Pending ? $move : null;

                // What is on the machine is the stack's to say again once a
                // move has done something to it, rather than this screen's to
                // patch.
                if ($move->stance() === Stance::Applied) {
                    $this->answered = null;
                }

                return new HowAMoveReads()->answered($move);
            },
            ended: function () use ($mode): TheMoveTurnedOutToBe {
                $this->following = null;

                return new HowAMoveReads()->ended($mode);
            },
            refused: function (string $because) use ($mode): TheMoveTurnedOutToBe {
                $this->following = null;

                return new HowAMoveReads()->refused($because, $mode);
            },
            met: function (Obstacle $why) use ($stack, $mode): TheMoveTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowAMoveReads()->met($why, $mode);
            },
        );
    }
}
