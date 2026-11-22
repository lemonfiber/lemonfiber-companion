<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Presenters;

use function count;

use Modules\Kernel\Api\AnAreaLeftAlone;
use Modules\Kernel\Api\AThingLeftAlone;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Modules\Operator\Internal\ViewModels\AnAreaLeftAloneAsShown;
use Modules\Operator\Internal\ViewModels\AThingLeftAloneAsShown;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheAreasLeftAloneAsShown;

/** What the stack said it was told to leave alone, turned into what a screen draws. */
final readonly class HowTheAreasLeftAloneRead
{
    private const string NOT_CARRIED = 'stacks.left_alone.not_carried';

    private const string NONE = 'stacks.left_alone.none';

    private const string DECLARED = 'stacks.left_alone.declared';

    public function signedOut(): TheAreasLeftAloneAsShown
    {
        return new TheAreasLeftAloneAsShown(HowTheReadingWent::theSessionEnded(), '', 0, [], 0);
    }

    public function found(WhatWasFoundOfTheAreasLeftAlone $found): TheAreasLeftAloneAsShown
    {
        return $found->either(
            listed: $this->these(...),
            notCarried: $this->notCarried(...),
            met: $this->met(...),
        );
    }

    public function notCarried(): TheAreasLeftAloneAsShown
    {
        return new TheAreasLeftAloneAsShown(HowTheReadingWent::itCameBack(), self::NOT_CARRIED, 0, [], 0);
    }

    public function met(Obstacle $why): TheAreasLeftAloneAsShown
    {
        return new TheAreasLeftAloneAsShown(HowTheReadingWent::somethingStopped($why), '', 0, [], 0);
    }

    public function these(TheAreasLeftAlone $declared): TheAreasLeftAloneAsShown
    {
        $areas = [];

        foreach ($declared as $area) {
            $areas[] = $this->one($area);
        }

        $howMany = count($areas) + $declared->more();

        return new TheAreasLeftAloneAsShown(HowTheReadingWent::itCameBack(), $howMany === 0 ? self::NONE : self::DECLARED, $howMany, $areas, $declared->more());
    }

    private function one(AnAreaLeftAlone $area): AnAreaLeftAloneAsShown
    {
        $covers = [];

        foreach ($area->covers as $thing) {
            $covers[] = $this->thing($thing);
        }

        return new AnAreaLeftAloneAsShown($area->area, $area->areaCut, $area->why, $area->whyCut, $covers, $area->covers->more());
    }

    private function thing(AThingLeftAlone $thing): AThingLeftAloneAsShown
    {
        return new AThingLeftAloneAsShown($thing->name, $thing->kind->saidOnTheScreen());
    }
}
