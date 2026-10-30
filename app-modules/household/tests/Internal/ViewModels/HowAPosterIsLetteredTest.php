<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Design\Api\TypeSize;
use Modules\Household\Internal\ViewModels\HowAPosterIsLettered;

use function sprintf;
use function str_repeat;

it('letters a title of eighteen characters, its words six at most, large', function (): void {
    expect(HowAPosterIsLettered::for('Alien'))->toBe(HowAPosterIsLettered::Large)
        ->and(HowAPosterIsLettered::for('aaaaaa'))->toBe(HowAPosterIsLettered::Large)
        ->and(HowAPosterIsLettered::for('abc abc abc abc dd'))->toBe(HowAPosterIsLettered::Large);
});

it('steps down from large past eighteen characters, or for a word of seven', function (): void {
    expect(HowAPosterIsLettered::for('abc abc abc abc ddd'))->toBe(HowAPosterIsLettered::Middle)
        ->and(HowAPosterIsLettered::for('aaaaaaa'))->toBe(HowAPosterIsLettered::Middle)
        ->and(HowAPosterIsLettered::for('a aaaaaaa'))->toBe(HowAPosterIsLettered::Middle);
});

it('letters a title of fifty-six characters, its words eleven at most, at the middle step', function (): void {
    expect(HowAPosterIsLettered::for(sprintf('%s%s', str_repeat('abc ', 13), 'dddd')))->toBe(HowAPosterIsLettered::Middle)
        ->and(HowAPosterIsLettered::for(str_repeat('a', 11)))->toBe(HowAPosterIsLettered::Middle);
});

it('steps down from the middle past fifty-six characters, or for a word of twelve', function (): void {
    expect(HowAPosterIsLettered::for(sprintf('%s%s', str_repeat('abc ', 13), 'ddddd')))->toBe(HowAPosterIsLettered::Small)
        ->and(HowAPosterIsLettered::for(str_repeat('a', 12)))->toBe(HowAPosterIsLettered::Small);
});

it('counts characters rather than bytes, so a title in any script is stepped by its length', function (): void {
    expect(HowAPosterIsLettered::for('éééééé'))->toBe(HowAPosterIsLettered::Large);
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
