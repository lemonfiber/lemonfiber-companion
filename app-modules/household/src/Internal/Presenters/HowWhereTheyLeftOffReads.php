<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Presenters;

use Modules\Household\Internal\ViewModels\WhatAShelfRowSays;
use Modules\Household\Internal\ViewModels\WhatOnePosterSays;
use Modules\Household\Internal\ViewModels\WhatTheirOwnTitlesTurnedOutToBe;
use Modules\Kernel\Api\APartWay;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\PartWays;

use function sprintf;

/**
 * What a member was part-way through, as the row Home leads with.
 *
 * Pure, and in the core's order, most recent first. Each is a poster saying
 * how long is left, which plays on from where they left off when pressed. A
 * row with nothing in it is not made.
 */
final readonly class HowWhereTheyLeftOffReads
{
    /** The key the row is headed with. */
    private const string CARRY_ON = 'household.shelf.carry_on';

    /** The line at the top of a poster, where the core knows how long it runs. */
    private const string LEFT = 'household.poster.left';

    /** The line at the top of a poster, where it does not. */
    private const string PART_WAY = 'household.poster.part_way';

    /** What pressing a poster does on Home: play it on from where it was left, by its id. */
    private const string RESUMES = "resume('%s')";

    /** The house answered, and this is what they were part-way through. */
    public function these(PartWays $partWay): WhatTheirOwnTitlesTurnedOutToBe
    {
        $posters = [];

        foreach ($partWay as $one) {
            $posters[] = $this->posterFor($one);
        }

        return WhatTheirOwnTitlesTurnedOutToBe::these($posters === [] ? [] : [new WhatAShelfRowSays(self::CARRY_ON, $posters)]);
    }

    /** Something stood in the way, and this is what the member met. */
    public function met(Obstacle $why): WhatTheirOwnTitlesTurnedOutToBe
    {
        return WhatTheirOwnTitlesTurnedOutToBe::somethingStopped($why);
    }

    /** This device holds no session for that house, so nothing was asked. */
    public function signedOut(): WhatTheirOwnTitlesTurnedOutToBe
    {
        return WhatTheirOwnTitlesTurnedOutToBe::theSessionEnded();
    }

    private function posterFor(APartWay $one): WhatOnePosterSays
    {
        $holding = $one->holding();
        $tap = sprintf(self::RESUMES, $holding->id()->named());

        return $one->left()->either(
            minutes: static fn(int $minutes): WhatOnePosterSays => WhatOnePosterSays::ofSomethingPartWay($holding->titled(), self::LEFT, ['minutes' => (string) $minutes], $tap),
            unstated: static fn(): WhatOnePosterSays => WhatOnePosterSays::ofSomethingPartWay($holding->titled(), self::PART_WAY, [], $tap),
        );
    }
}
