<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\AFoundTitleAsShown;
use Modules\Household\Internal\ViewModels\WhatAskingShows;
use Modules\Household\Internal\ViewModels\WhatStoppedAMember;
use Modules\Household\Internal\ViewModels\WhatTheSearchShows;
use Modules\Household\Internal\WhatAFoundTitleIsOpenedWith;
use Modules\Kernel\Api\AFoundTitle;
use Modules\Kernel\Api\KindOfTitle;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\WhatAnAskBecame;
use Modules\Kernel\Api\WhatAnAskCameTo;
use Modules\Kernel\Api\WhatASearchFound;
use Modules\Kernel\Api\WhereAFoundTitleStands;
use Modules\Kernel\Api\WhereTheNextPageBegins;

use function sprintf;

/**
 * How a member's search and an ask for what it found read, in the household's words.
 *
 * Every sentence about the house's rules is the core's and passes through
 * untouched; this names only what a title is and where it stands.
 */
final readonly class HowAFoundTitleReads
{
    /** A title already in the house. */
    public const string ALREADY_HERE = 'household.search.already_here';

    /** A title the member may ask for. */
    public const string ASKABLE = 'household.search.askable';

    /** What an ask became, heading the core's sentence. */
    public const string BECAME = 'household.found.became.%s';

    public const string DATED = 'household.search.line';

    public const string UNDATED = 'household.search.line_undated';

    /** One page, each title a row leading to its page. */
    public function found(WhatASearchFound $page): WhatTheSearchShows
    {
        $rows = [];

        foreach ($page as $title) {
            $rows[] = $this->shown($title);
        }

        return WhatTheSearchShows::page(
            $page->approval(),
            $rows,
            $page->followedBy(more: static fn(WhereTheNextPageBegins $next): InWords => new InWords($next->said()), last: static fn(): InWords => new InWords(''))->said,
        );
    }

    /** What stood in the way of a search. */
    public function searchMet(Obstacle $why): WhatTheSearchShows
    {
        return WhatTheSearchShows::stoppedBy(WhatStoppedAMember::by($why));
    }

    /** What asking would come to, or came to. */
    public function asked(WhatAnAskCameTo $answer): WhatAskingShows
    {
        return WhatAskingShows::answered($answer->said(), $answer->wasRehearsed() ? '' : $this->became($answer->became()));
    }

    /** What stood in the way of asking. */
    public function askMet(Obstacle $why): WhatAskingShows
    {
        return WhatAskingShows::stoppedBy(WhatStoppedAMember::by($why));
    }

    /** The catalogue key for what a title is. */
    public function kind(KindOfTitle $kind): string
    {
        return match ($kind) {
            KindOfTitle::Film => Medium::Film->saidOnTheScreen(),
            KindOfTitle::Series => Medium::Series->saidOnTheScreen(),
        };
    }

    /** The catalogue key for where a title stands. */
    public function stands(WhereAFoundTitleStands $stands): string
    {
        return match ($stands) {
            WhereAFoundTitleStands::Here => self::ALREADY_HERE,
            WhereAFoundTitleStands::PartlyHere => Waiting::PartlyHere->saidToTheMember(),
            WhereAFoundTitleStands::OnItsWay => Waiting::Getting->saidToTheMember(),
            WhereAFoundTitleStands::Askable => self::ASKABLE,
        };
    }

    public function shown(AFoundTitle $title): AFoundTitleAsShown
    {
        $year = $title->year()->either(dated: static fn(int $year): InWords => new InWords((string) $year), unstated: static fn(): InWords => new InWords(''))->said;

        return new AFoundTitleAsShown(
            found: $title,
            titled: $title->title(),
            line: $year === '' ? self::UNDATED : self::DATED,
            year: $year,
            kind: $this->kind($title->kind()),
            stands: $this->stands($title->stands()),
            carries: [
                WhatAFoundTitleIsOpenedWith::Kind->value => $title->kind()->value,
                WhatAFoundTitleIsOpenedWith::Title->value => $title->title(),
                WhatAFoundTitleIsOpenedWith::Year->value => $year,
                WhatAFoundTitleIsOpenedWith::Stands->value => $title->stands()->value,
            ],
        );
    }

    private function became(WhatAnAskBecame $became): string
    {
        return sprintf(self::BECAME, $became->value);
    }
}
