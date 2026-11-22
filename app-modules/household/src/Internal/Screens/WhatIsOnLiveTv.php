<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Household\Internal\OffersTheAppsSettings;
use Modules\Household\Internal\TheLiveTvGuide;
use Modules\Household\Internal\TheLiveTvShelf;
use Modules\Household\Internal\ViewModels\WhatTheGuideTurnedOutToBe;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Stacks\Api\AStacksScreen;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * The live TV guide, opened from See all on Home's Live TV shelf.
 *
 * Every channel the core lists for the member, with its programmes across
 * the window the core answers, as a grid panned in time and across channels.
 * A channel, or the programme on it now, tunes it as the shelf does; a later
 * programme opens a sheet with its title, times and certificate, and nothing
 * to press. A programme the core left out has no cell, so it has no sheet.
 * Programmes too short to press one by one are drawn as one cell, which opens
 * a list of them, each pressed as its own cell would be.
 *
 * Read once on opening and again when pulled down; nothing reads it on a
 * timer. What came of a channel pressed here, or how it stopped, is said
 * above the grid.
 *
 * `Concealed` because what a household watches is the household's business.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatIsOnLiveTv extends NativeComponent implements HearsThePlayer
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use FindsItsWayAroundTheHouse;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::what-is-on-live-tv';

    /** The guide, once the frame has asked; public so the screen's state holds it. */
    public ?WhatTheGuideTurnedOutToBe $answered = null;

    /** The channel last pressed here, by its id, so a stop can be told against it; public for the same reason. */
    public string $tunedTo = '';

    public function __construct(
        private readonly TheLiveTvGuide $guide,
        private readonly TheLiveTvShelf $channels,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The guide, asked once per frame. */
    public function guide(): WhatTheGuideTurnedOutToBe
    {
        return $this->answered ??= $this->guide->of($this->stack());
    }

    /** Forget what came back, so the next frame asks again. */
    public function again(): void
    {
        $this->answered = null;
    }

    /** Press a channel, or the programme on it now, by the id the core lists it under. */
    public function tuneTo(string $channel): void
    {
        if ($channel === '') {
            return;
        }

        $guide = $this->guide();
        $this->tunedTo = $channel;
        $this->answered = $guide->after($this->channels->tune($this->stack(), $channel, $guide->nameOf($channel)));
    }

    /** Open the sheet of a later programme, by its channel's row and where it sits in that channel's programmes. */
    public function aboutTheProgramme(int $row, int $at): void
    {
        $this->answered = $this->guide()->about($row, $at);
    }

    /** Open the list of the programmes drawn together, by their channel's row and the cell's place in it. */
    public function listTheProgrammes(int $row, int $place): void
    {
        $this->answered = $this->guide()->listing($row, $place);
    }

    /** Close the programme's sheet, or the list of programmes drawn together. */
    public function closeTheProgramme(): void
    {
        $this->answered = $this->guide()->closed();
    }

    /** The player moved: the channel last pressed here that stopped and cannot go on says why. */
    public function playerMoved(): void
    {
        if ($this->tunedTo === '') {
            return;
        }

        $guide = $this->guide();
        $came = $this->channels->howItStopped($this->tunedTo, $guide->nameOf($this->tunedTo));

        if (! $came->isNothing()) {
            $this->answered = $guide->after($came);
        }
    }

    /** Where a session that has ended is renewed. */
    public function signIn(): string
    {
        return AStacksScreen::SignIn->forTheStack($this->stack()->id());
    }
}
