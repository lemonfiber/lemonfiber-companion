<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Kernel\Api\APartWay;
use Modules\Kernel\Api\Holding;
use Modules\Kernel\Api\HoldingId;
use Modules\Kernel\Api\HowFarIn;
use Modules\Kernel\Api\HowLongItRuns;
use Modules\Kernel\Api\Medium;
use Modules\Kernel\Api\WhenItCameOut;
use Modules\Kernel\Api\WhereItPlays;

/** How much is left, as minutes or `unknown`. Named for this file. */
function whatIsLeft(HowLongItRuns $left): string
{
    return $left->either(
        minutes: static fn(int $minutes): ArrayObject => new ArrayObject([(string) $minutes]),
        unstated: static fn(): ArrayObject => new ArrayObject(['unknown']),
    )->getArrayCopy()[0];
}

/** A film this far into this long a run. Named for this file. */
function aFilmPartWay(int $reached, int $length): APartWay
{
    return APartWay::of(Holding::of(HoldingId::called('a1'), 'Alien', Medium::Film, WhenItCameOut::unstated()), HowFarIn::at($reached), $length, WhereItPlays::doesNotStream());
}

it('says how long is left in whole minutes, rounded up', function (): void {
    expect(whatIsLeft(aFilmPartWay(1_200, 7_020)->left()))->toBe('97')
        ->and(whatIsLeft(aFilmPartWay(1_200, 1_201)->left()))->toBe('1')
        ->and(whatIsLeft(aFilmPartWay(1_200, 1_260)->left()))->toBe('1')
        ->and(whatIsLeft(aFilmPartWay(1_200, 1_261)->left()))->toBe('2');
});

it('says nothing is left past the end, and that it does not know where the length is unknown', function (): void {
    $unknown = APartWay::ofUnknownLength(Holding::of(HoldingId::called('e1'), 'One', Medium::Episode, WhenItCameOut::unstated()), HowFarIn::at(61), WhereItPlays::doesNotStream());

    expect(whatIsLeft(aFilmPartWay(1_300, 1_200)->left()))->toBe('0')
        ->and(whatIsLeft($unknown->left()))->toBe('unknown')
        ->and($unknown->reached()->seconds())->toBe(61)
        ->and($unknown->holding()->titled())->toBe('One');
});
