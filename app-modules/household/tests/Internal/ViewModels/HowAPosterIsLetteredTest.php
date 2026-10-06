<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function intdiv;
use function it;

use Modules\Design\Api\TypeSize;
use Modules\Household\Internal\ViewModels\HowAPosterIsLettered;

use function sprintf;
use function str_repeat;

it('letters a short title of short words large', function (): void {
    expect(HowAPosterIsLettered::for('Alien'))->toBe(HowAPosterIsLettered::Large)
        ->and(HowAPosterIsLettered::for(str_repeat('a', HowAPosterIsLettered::LARGE_WORD_AT_MOST)))->toBe(HowAPosterIsLettered::Large);
});

it('letters a title large up to its longest, and no further', function (): void {
    $atMost = sprintf('%s%s', str_repeat('abc ', intdiv(HowAPosterIsLettered::LARGE_AT_MOST, 4)), str_repeat('d', HowAPosterIsLettered::LARGE_AT_MOST % 4));

    expect(HowAPosterIsLettered::for($atMost))->toBe(HowAPosterIsLettered::Large)
        ->and(HowAPosterIsLettered::for(sprintf('%s e', $atMost)))->toBe(HowAPosterIsLettered::Middle);
});

it('steps down from large for one word too long to fit a line at that size', function (): void {
    expect(HowAPosterIsLettered::for(str_repeat('a', HowAPosterIsLettered::LARGE_WORD_AT_MOST + 1)))->toBe(HowAPosterIsLettered::Middle);
});

it('letters a title at the middle step up to its longest, and no further', function (): void {
    $atMost = sprintf('%s%s', str_repeat('abc ', intdiv(HowAPosterIsLettered::MIDDLE_AT_MOST, 4)), str_repeat('d', HowAPosterIsLettered::MIDDLE_AT_MOST % 4));

    expect(HowAPosterIsLettered::for($atMost))->toBe(HowAPosterIsLettered::Middle)
        ->and(HowAPosterIsLettered::for(sprintf('%s e', $atMost)))->toBe(HowAPosterIsLettered::Small);
});

it('steps down from the middle for one word too long to fit a line at that size', function (): void {
    expect(HowAPosterIsLettered::for(str_repeat('a', HowAPosterIsLettered::MIDDLE_WORD_AT_MOST)))->toBe(HowAPosterIsLettered::Middle)
        ->and(HowAPosterIsLettered::for(str_repeat('a', HowAPosterIsLettered::MIDDLE_WORD_AT_MOST + 1)))->toBe(HowAPosterIsLettered::Small);
});

it('counts characters rather than bytes, so a title in any script is stepped by its length', function (): void {
    expect(HowAPosterIsLettered::for(str_repeat('é', HowAPosterIsLettered::LARGE_WORD_AT_MOST)))->toBe(HowAPosterIsLettered::Large);
});

it('sets each step at a smaller size of the brand\'s than the step before it', function (): void {
    expect(HowAPosterIsLettered::Large->setAt())->toBe(TypeSize::DisplayM)
        ->and(HowAPosterIsLettered::Middle->setAt())->toBe(TypeSize::Body)
        ->and(HowAPosterIsLettered::Small->setAt())->toBe(TypeSize::Caption);
});

it('lets a title take more lines the smaller it is lettered', function (): void {
    expect(HowAPosterIsLettered::Large->linesAtMost())->toBe(4)
        ->and(HowAPosterIsLettered::Middle->linesAtMost())->toBe(7)
        ->and(HowAPosterIsLettered::Small->linesAtMost())->toBe(8);
});

it('sets each step a size up on the hero, and lets it take fewer lines there the larger it is', function (): void {
    expect(HowAPosterIsLettered::Large->setOnTheHero())->toBe(TypeSize::DisplayL)
        ->and(HowAPosterIsLettered::Middle->setOnTheHero())->toBe(TypeSize::DisplayM)
        ->and(HowAPosterIsLettered::Small->setOnTheHero())->toBe(TypeSize::Body)
        ->and(HowAPosterIsLettered::Large->linesOnTheHero())->toBe(2)
        ->and(HowAPosterIsLettered::Middle->linesOnTheHero())->toBe(4)
        ->and(HowAPosterIsLettered::Small->linesOnTheHero())->toBe(6);
});
