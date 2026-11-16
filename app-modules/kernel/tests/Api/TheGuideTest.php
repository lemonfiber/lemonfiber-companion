<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function array_map;

use ArrayObject;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AChannelsGuide;
use Modules\Kernel\Api\AnAiring;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\KindOfObstacle;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\TheGuide;
use Modules\Kernel\Api\WhatTheGuideSays;
use Modules\Kernel\Api\WhenItSays;

it('holds a programme\'s title, start, end and certificate, and knows when it is on', function (): void {
    $news = AnAiring::of('The news', Instant::atEpochSeconds(100), Instant::atEpochSeconds(200), '6');

    expect($news->title())->toBe('The news')
        ->and($news->starts()->epochSeconds())->toBe(100)
        ->and($news->ends()->epochSeconds())->toBe(200)
        ->and($news->certificate())->toBe('6')
        ->and($news->isOnAt(Instant::atEpochSeconds(99)))->toBeFalse()
        ->and($news->isOnAt(Instant::atEpochSeconds(100)))->toBeTrue()
        ->and($news->isOnAt(Instant::atEpochSeconds(199)))->toBeTrue()
        ->and($news->isOnAt(Instant::atEpochSeconds(200)))->toBeFalse();
});

it('holds a channel\'s programmes in the order sent, and where its guide ends', function (): void {
    $later = AnAiring::of('Later', Instant::atEpochSeconds(300), Instant::atEpochSeconds(400), '');
    $sooner = AnAiring::of('Sooner', Instant::atEpochSeconds(100), Instant::atEpochSeconds(200), '');
    $channel = AChannelsGuide::of(ChannelId::called('c1'), 'NPO 1', '1', WhenItSays::at(Instant::atEpochSeconds(400)), $later, $sooner);

    expect($channel->id()->named())->toBe('c1')
        ->and($channel->name())->toBe('NPO 1')
        ->and($channel->number())->toBe('1')
        ->and(array_map(static fn(AnAiring $one): string => $one->title(), iterator_to_array($channel->airings(), preserve_keys: false)))->toBe(['Later', 'Sooner'])
        ->and(array_keys(iterator_to_array($channel->airings(), preserve_keys: true)))->toBe([0, 1])
        ->and($channel->until()->isSaid())->toBeTrue()
        ->and(iterator_to_array(AChannelsGuide::of(ChannelId::called('c2'), 'Radio', '', WhenItSays::unsaid())->airings(), preserve_keys: false))->toBe([]);
});

it('holds the channels in the order sent, and knows when it holds none', function (): void {
    $two = TheGuide::of(AChannelsGuide::of(ChannelId::called('b'), 'B', '', WhenItSays::unsaid()), AChannelsGuide::of(ChannelId::called('a'), 'A', '', WhenItSays::unsaid()));

    expect(array_map(static fn(AChannelsGuide $one): string => $one->name(), iterator_to_array($two, preserve_keys: false)))->toBe(['B', 'A'])
        ->and(array_keys(iterator_to_array($two, preserve_keys: true)))->toBe([0, 1])
        ->and($two->isEmpty())->toBeFalse()
        ->and(TheGuide::of()->isEmpty())->toBeTrue();
});

it('says whether the core answered the guide or what stood in the way', function (): void {
    $fold = static fn(WhatTheGuideSays $says): string => $says->either(
        told: static fn(TheGuide $guide): ArrayObject => new ArrayObject([$guide->isEmpty() ? 'told nothing' : 'told']),
        refused: static fn(Obstacle $why): ArrayObject => new ArrayObject([$why->kind()->value]),
    )->getArrayCopy()[0];

    expect($fold(WhatTheGuideSays::told(TheGuide::of())))->toBe('told nothing')
        ->and($fold(WhatTheGuideSays::refused(Obstacle::of(KindOfObstacle::StackDidNotAnswer))))->toBe(KindOfObstacle::StackDidNotAnswer->value);
});
