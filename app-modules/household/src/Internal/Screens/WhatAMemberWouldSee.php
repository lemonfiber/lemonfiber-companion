<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\DrawnAsAMemberSeesIt;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Presenters\HowAShelfReads;
use Modules\Household\Internal\Presenters\HowWhatAMemberIsOwedReads;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeAbleToWatch;
use Modules\Household\Internal\ViewModels\WhatAMemberTurnedOutToBeOwed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Owing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Shelf;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\Watching;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\Whose;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheHouseholdsTabs;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The member's side as somebody invited with the household's defaults sees it, previewed by the operator.
 *
 * The member's Home and Requests, drawn in the member's theme from the core's
 * answers for the household's defaults. Those answers are read for nobody, so
 * nothing here names a member or shows anybody's library or requests, and the
 * operator sees the member's side without reading anyone's.
 *
 * **One screen, with the two tabs as a choice on it.** It opens over the
 * operator's screen it was opened from, so the platform's way back and the
 * mark's control both return there in one step, whichever tab is showing.
 *
 * **It asks for nothing.** Asking from it would be a real request made under
 * the operator, so the control a member asks with is drawn and cannot be used,
 * with a note saying a preview cannot ask.
 *
 * **Only the operator's session asks.** The composition root never builds this
 * for a member's session, and one that reached it anyway is told it is not
 * theirs to ask rather than sent to the stack: a member is somebody, and their
 * side is their own. Nothing it read is kept.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatAMemberWouldSee extends NativeComponent implements DrawnAsAMemberSeesIt
{
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::what-a-member-would-see';

    /**
     * Which of the member's two tabs is showing, by the tab's own word.
     *
     * `public` for the reason every screen's state is: `NativeComponent`'s
     * property syncing writes only public, non-static properties.
     */
    public string $showing = TheHouseholdsTabs::Home->value;

    /** What the shelf came back as, once the frame has asked. */
    public ?WhatAMemberTurnedOutToBeAbleToWatch $watched = null;

    /** What the allowance came back as, once the frame has asked. */
    public ?WhatAMemberTurnedOutToBeOwed $told = null;

    public function __construct(
        private readonly Watching $watching,
        private readonly Owing $owing,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The tab showing. */
    public function tab(): TheHouseholdsTabs
    {
        return TheHouseholdsTabs::tryFrom($this->showing) === TheHouseholdsTabs::Requests
            ? TheHouseholdsTabs::Requests
            : TheHouseholdsTabs::Home;
    }

    /** Show what a member could watch. */
    public function showHome(): void
    {
        $this->showing = TheHouseholdsTabs::Home->value;
    }

    /** Show what a member would be told about asking. */
    public function showRequests(): void
    {
        $this->showing = TheHouseholdsTabs::Requests->value;
    }

    /** Ask the stack again, for both tabs. */
    public function again(): void
    {
        $this->watched = null;
        $this->told = null;
    }

    /** Back to the operator's screen the preview was opened from. */
    public function backToTheSwitchboard(): void
    {
        $this->back();
    }

    /** What somebody invited with the household's defaults could watch, asked once per frame. */
    public function shelf(): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->watched ??= $this->asTheOperator(
            asked: fn(Stack $stack, Session $session): WhatAMemberTurnedOutToBeAbleToWatch => $this->watched($stack, $session),
            otherwise: static fn(?Obstacle $why): WhatAMemberTurnedOutToBeAbleToWatch => $why instanceof Obstacle
                ? new HowAShelfReads()->met($why)
                : new HowAShelfReads()->signedOut(),
        );
    }

    /** What somebody invited with the household's defaults would be told, asked once per frame. */
    public function allowance(): WhatAMemberTurnedOutToBeOwed
    {
        return $this->told ??= $this->asTheOperator(
            asked: fn(Stack $stack, Session $session): WhatAMemberTurnedOutToBeOwed => $this->toldAbout($stack, $session),
            otherwise: static fn(?Obstacle $why): WhatAMemberTurnedOutToBeOwed => $why instanceof Obstacle
                ? new HowWhatAMemberIsOwedReads()->met($why)
                : new HowWhatAMemberIsOwedReads()->signedOut(),
        );
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }

    /**
     * Ask the stack under the operator's session, or say why nothing was asked.
     *
     * `otherwise` is handed the obstacle where the session is a member's, and
     * nothing where there is no session at all.
     *
     * @template T of object
     *
     * @param  callable(Stack, Session): T $asked
     * @param  callable(?Obstacle): T      $otherwise
     * @return T
     */
    private function asTheOperator(callable $asked, callable $otherwise): object
    {
        $stack = $this->stack();
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: static fn(Session $session): object => $resumed->whoseItIs(
                nobody: static fn(): object => $otherwise(null),
                theirs: static fn(Whose $whose): object => $whose->either(
                    operator: static fn(): object => $asked($stack, $session),
                    member: static fn(): object => $otherwise(Obstacle::of(KindOfObstacle::NotForThisAccount)),
                ),
            ),
            notHeld: static fn(): object => $otherwise(null),
        );
    }

    /** What the stack said the household's defaults hold, or what stood in the way. */
    private function watched(Stack $stack, Session $session): WhatAMemberTurnedOutToBeAbleToWatch
    {
        return $this->watching->theDefaultShelf($stack, $session)->either(
            told: static fn(Shelf $shelf): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->these($shelf),
            outOfReach: static fn(Sentences $said): WhatAMemberTurnedOutToBeAbleToWatch
                => new HowAShelfReads()->outOfReach($said),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeAbleToWatch {
                // A credential refused here is the same signed-out device as
                // one refused on any other read, so the store is told while the
                // obstacle is still in hand.
                $this->letGoOfTheSession($why, $stack);

                return new HowAShelfReads()->met($why);
            },
        );
    }

    /** What the stack said the household's defaults are told, or what stood in the way. */
    private function toldAbout(Stack $stack, Session $session): WhatAMemberTurnedOutToBeOwed
    {
        return $this->owing->whatTheDefaultsAreTold($stack, $session)->either(
            told: static fn(Sentences $said): WhatAMemberTurnedOutToBeOwed
                => new HowWhatAMemberIsOwedReads()->these($said),
            refused: function (Obstacle $why) use ($stack): WhatAMemberTurnedOutToBeOwed {
                $this->letGoOfTheSession($why, $stack);

                return new HowWhatAMemberIsOwedReads()->met($why);
            },
        );
    }
}
