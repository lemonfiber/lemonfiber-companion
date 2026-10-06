<?php

declare(strict_types=1);

namespace Modules\Household\Internal\Screens;

use Modules\Household\Internal\Presenters\HowATitleReads;
use Modules\Household\Internal\ViewModels\WhatThisTitleTurnedOutToBe;
use Modules\Household\Internal\WhatATitleIsOpenedWith;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Edge\NativeComponent;

/**
 * One title on a member's shelf, opened from its poster or from Home's hero.
 *
 * It draws what the shelf said of the title, handed over by what opened it:
 * the name lettered on its poster, its kind and its year. The core answers no
 * reading for one title, so there is no more to say about it here, and
 * nothing is asked.
 *
 * **Play is drawn and cannot be used**, with the reason beside it in the
 * app's own words: the core hands the app no way to play a title, and the
 * one action a title's screen carries is not hidden for that.
 *
 * `Concealed` because what a household holds is the household's business.
 */
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnlyWhenAsked)]
final class WhatThisTitleIs extends NativeComponent
{
    use FindsItsWayAroundTheHouse;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'household::what-this-title-is';

    public function __construct(protected readonly TheWayAround $around) {}

    /** The title, from what opened this screen. */
    public function title(): WhatThisTitleTurnedOutToBe
    {
        return new HowATitleReads()->handed(
            $this->data(WhatATitleIsOpenedWith::Titled->value),
            $this->data(WhatATitleIsOpenedWith::Medium->value),
            $this->data(WhatATitleIsOpenedWith::Year->value),
        );
    }
}
