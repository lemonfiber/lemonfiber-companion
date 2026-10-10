<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Playing\PutsATitleOnScreen;
use Modules\Household\Internal\Presenters\HowAShelfReads;
use Modules\Household\Internal\Presenters\HowTheirOwnTitlesRead;
use Modules\Household\Internal\Presenters\HowWhereTheyLeftOffReads;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Household\Internal\ViewModels\WhatTheirOwnTitlesTurnedOutToBe;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\PartWays;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * What this machine says the person holding the session may watch, led by
 * what is theirs.
 *
 * The member's Home tab, where a member lands. It leads with their own
 * titles: what they were part-way through, which plays on from where they
 * left off when pressed, what they asked for that has arrived and what is on
 * its way, and
 * then draws their shelf: the newest title in the house across the screen,
 * and the rows. Which libraries they reach, what their age limit allows and
 * what they are entitled to were decided by the core before the list
 * arrived, so nothing here filters a row, sorts one or hides one.
 *
 * **There is no asking for anything on this screen.** Requesting something,
 * approving it and spending an allowance are the household surface's and are
 * reached from it. Keeping them off here is what stops the shelf growing a
 * second way to ask, able to disagree with the first about what a member is
 * allowed.
 *
 * **Nothing here composes an address.** A holding arrives as a name and a kind
 * and is drawn as one. The hero's Play asks the core for the title again and
 * plays where the core says it plays — building an address out of a stack's
 * address and a holding's id would be this app holding a second copy of how
 * the library works, and would be wrong the first time the route to it is not
 * the one it assumed.
 *
 * `Concealed` for the reason every stack-facing screen is: what a household
 * holds is the household's business, and a diagnostic report is assembled from
 * what an operator chooses to send rather than from what a screen happened to
 * hold.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatYouCanWatch extends NativeComponent implements HearsThePlayer
{
    use PlaysTitles;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::what-you-can-watch';

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatAMemberTurnedOutToBeAbleToWatch $answered = null;

    /** What came back when their own requests were asked for, once the frame has asked. */
    public ?WhatTheirOwnTitlesTurnedOutToBe $theirs = null;

    /** What came back when what they were part-way through was asked for, once the frame has asked. */
    public ?WhatTheirOwnTitlesTurnedOutToBe $leftOff = null;

    public function __construct(
        private readonly Watching $watching,
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        private readonly PutsATitleOnScreen $titles,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** What came back, asked once per frame. */
    public function answer(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->answered ??= $this->ask();
    }

    /** What came back for their own requests, asked once per frame. */
    public function theirOwn(): WhatTheirOwnTitlesTurnedOutToBe
    {
        return $this->theirs ??= $this->askForTheirOwn();
    }

    /** What they were part-way through, asked once per frame. */
    public function whereTheyLeftOff(): WhatTheirOwnTitlesTurnedOutToBe
    {
        return $this->leftOff ??= $this->askWhereTheyLeftOff();
    }

    /** Whether what they were part-way through could not be asked for while the shelf answered, for {@see theirOwnWereStopped()}'s reason. */
    public function leftOffWasStopped(): bool
    {
        return $this->answer()->cameBack() && $this->whereTheyLeftOff()->wasStopped();
    }

    /**
     * Whether their own requests could not be asked for while the shelf
     * answered, which Home says where those rows would be.
     *
     * Only then: where the shelf met something too, the shelf says so, and the
     * same obstacle said twice is a screen repeating itself.
     */
    public function theirOwnWereStopped(): bool
    {
        return $this->answer()->cameBack() && $this->theirOwn()->wasStopped();
    }

    /**
     * Ask the house again, for both readings.
     *
     * The action an obstacle must not take away. Forgetting what came back
     * rather than asking here, so the next accessor asks and one frame stays
     * one asking.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->theirs = null;
        $this->leftOff = null;
    }

    /** Play the title across the screen, by the id the core lists it under. */
    public function play(string $title): void
    {
        if ($title !== '') {
            $held = HoldingId::called($title);
            $this->pressed($held, $this->titles->theTitle($this->stack(), $held));
        }
    }

    /** Play on something they were part-way through from where they left off, by the id the core lists it under. */
    public function resume(string $partWay): void
    {
        if ($partWay !== '') {
            $held = HoldingId::called($partWay);
            $this->pressed($held, $this->titles->whereTheyLeftOff($this->stack(), $held));
        }
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    protected function titlesOnScreen(): PutsATitleOnScreen
    {
        return $this->titles;
    }

    /** The core's answer changed under Home: the title's own page says what it is now. */
    protected function playsNothingNow(HoldingId $title): void
    {
        $this->navigate($this->goes()->title($title));
    }

    /** Asking met this: Home says what stood in the way. */
    protected function metOnPlay(Obstacle $why): void
    {
        $this->answered = new HowAShelfReads()->met($why);
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Resumed once and folded twice — for the session and for whose it is —
     * rather than read twice, so the two cannot come from different frames.
     */
    private function ask(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        $stack = $this->stack();
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: fn(Session $session): WhatAMemberTurnedOutToBeAbleToWatch => $resumed->whoseItIs(
                nobody: static fn(): WhatAMemberTurnedOutToBeAbleToWatch
                    => new HowAShelfReads()->signedOut(),
                theirs: fn(Whose $whose): WhatAMemberTurnedOutToBeAbleToWatch
                    => $this->asked($stack, $session, $whose),
            ),
            notHeld: static fn(): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->signedOut(),
        );
    }

    /** Resume the session and ask for their own requests, or say the session has ended. */
    private function askForTheirOwn(): WhatTheirOwnTitlesTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheirOwnTitlesTurnedOutToBe => $this->owing->theirRequests($stack, $session)->asked()->either(
                told: static fn(Requested $wanted): WhatTheirOwnTitlesTurnedOutToBe
                    => new HowTheirOwnTitlesRead()->these($wanted),
                refused: function (Obstacle $why) use ($stack): WhatTheirOwnTitlesTurnedOutToBe {
                    // Told here as well as on the shelf's read: whichever read
                    // met the refusal is the one holding the obstacle when the
                    // store has to hear about it. Letting go twice is letting go.
                    $this->letGoOfTheSession($why, $stack);

                    return new HowTheirOwnTitlesRead()->met($why);
                },
            ),
            notHeld: static fn(): WhatTheirOwnTitlesTurnedOutToBe => new HowTheirOwnTitlesRead()->signedOut(),
        );
    }

    /** Resume the session and ask what they were part-way through, or say the session has ended. */
    private function askWhereTheyLeftOff(): WhatTheirOwnTitlesTurnedOutToBe
    {
        $stack = $this->stack();
        $reads = new HowWhereTheyLeftOffReads();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session, Whose $whose): WhatTheirOwnTitlesTurnedOutToBe => $this->watching->partWayThrough($stack, $session, $whose)->either(
                told: static fn(PartWays $partWay): WhatTheirOwnTitlesTurnedOutToBe => $reads->these($partWay),
                refused: $this->lettingGoIfRefused($stack, $reads->met(...)),
            ),
            notHeld: static fn(): WhatTheirOwnTitlesTurnedOutToBe => $reads->signedOut(),
        );
    }

    /** What the stack said, or what the member met instead. */
    private function asked(Stack $stack, Session $session, Whose $whose): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->watching->theShelfOf($stack, $session, $whose)->either(
            told: static fn(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->these($shelf, $stack->id()),
            outOfReach: static fn(): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->outOfReach(),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeAbleToWatch {
                // A credential refused on this read is the same signed-out
                // device as one refused on any other, and a fold cannot forget
                // anything — so the store is told here, where the obstacle is
                // still in hand.
                $this->letGoOfTheSession($why, $stack);

                return new HowAShelfReads()->met($why);
            },
        );
    }
}
