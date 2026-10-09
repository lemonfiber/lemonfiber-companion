<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\Playing\PutsATitleOnScreen;
use Modules\Household\Internal\Presenters\HowATitleReads;
use Modules\Household\Internal\ViewModels\WhatThisTitleTurnedOutToBe;
use Modules\Kernel\Api\ATitle;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
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
 * One title on a member's shelf, in full, opened from its poster or from Home's hero.
 *
 * The title the route names, read as the member: what it is about, how long
 * it runs, its genres, its certificate, when it came out, and a series'
 * seasons with their episodes. A title outside the member's limits is the
 * core's absent answer, the same as one the household does not hold.
 *
 * **Play is the one action, and it is never hidden.** Where the core states
 * no location, the reason beside it is the core's own words. Pressed, it puts
 * the player on screen with what the core states at that moment; where it did
 * not play, or stopped and cannot go on, the line beside it says why.
 *
 * `Concealed` because what a household holds is the household's business.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatThisTitleIs extends NativeComponent implements HearsThePlayer
{
    use PlaysTitles;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::what-this-title-is';

    /** What came back, once the frame has asked; public so the screen's state holds it. */
    public ?WhatThisTitleTurnedOutToBe $answered = null;

    public function __construct(
        private readonly Watching $watching,
        private readonly SecureStorage $storage,
        private readonly PutsATitleOnScreen $titles,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The title, asked once per frame. */
    public function title(): WhatThisTitleTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /** Forget what came back, so the next frame asks again. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Play the title: itself, or the episode its own Play plays. */
    public function play(): void
    {
        $page = $this->page();

        if ($page instanceof HoldingId) {
            $this->pressed($page, $this->titles->theTitle($this->stack(), $page));
        }
    }

    /** Play one of its episodes, by the id the core lists it under. */
    public function playTheEpisode(string $episode): void
    {
        $page = $this->page();

        if ($page instanceof HoldingId && $episode !== '') {
            $this->pressed($page, $this->titles->theEpisode($this->stack(), $page, HoldingId::called($episode)));
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

    /** The core's answer changed under the page: read it again, and draw what it says now. */
    protected function playsNothingNow(HoldingId $title): void
    {
        $this->again();
    }

    /** Asking met this: the page says what stood in the way. */
    protected function metOnPlay(Obstacle $why): void
    {
        $this->answered = new HowATitleReads()->met($why);
    }

    /** Resume the session and ask for the title the route names, or say the session has ended. */
    private function ask(): WhatThisTitleTurnedOutToBe
    {
        $stack = $this->stack();
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: fn(Session $session): WhatThisTitleTurnedOutToBe => $resumed->whoseItIs(
                nobody: static fn(): WhatThisTitleTurnedOutToBe => new HowATitleReads()->signedOut(),
                theirs: fn(Whose $whose): WhatThisTitleTurnedOutToBe => $this->asked($stack, $session, $whose),
            ),
            notHeld: static fn(): WhatThisTitleTurnedOutToBe => new HowATitleReads()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session, Whose $whose): WhatThisTitleTurnedOutToBe
    {
        $page = $this->page();

        if (! $page instanceof HoldingId) {
            return new HowATitleReads()->absent();
        }

        return $this->watching->theTitle($stack, $session, $whose, $page)->either(
            told: static fn(ATitle $title): WhatThisTitleTurnedOutToBe => new HowATitleReads()->told($title),
            absent: static fn(): WhatThisTitleTurnedOutToBe => new HowATitleReads()->absent(),
            refused: function (Obstacle $why) use ($stack): WhatThisTitleTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowATitleReads()->met($why);
            },
        );
    }

    /** The title the route names, or none where it names nothing. */
    private function page(): ?HoldingId
    {
        $named = $this->param('service');

        return is_string($named) && $named !== '' ? HoldingId::called($named) : null;
    }
}
