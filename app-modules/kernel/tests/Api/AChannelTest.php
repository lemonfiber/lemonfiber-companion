<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_map;

use ArrayObject;

use function expect;
use function it;
use function iterator_to_array;

use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\ChannelIsUnnamed;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\WhenItSays;

/** What is on a channel now, in a word. Named for this file. */
function whatIsOn(AChannel $channel): string
{
    return $channel->now(
        showing: static fn(AProgramme $now): ArrayObject => new ArrayObject([$now->title()]),
        nothingSaid: static fn(): ArrayObject => new ArrayObject(['nothing']),
    )->getArrayCopy()[0];
}

it('says what is on a channel now, or that the guide says nothing', function (): void {
    $showing = AChannel::showing(ChannelId::called('c1'), 'NPO 1', '1', AProgramme::of('The news', WhenItSays::at(Instant::atEpochSeconds(5)), '6'));
    $silent = AChannel::withNothingSaid(ChannelId::called('c2'), 'Radio', '');

    expect(whatIsOn($showing))->toBe('The news')
        ->and(whatIsOn($silent))->toBe('nothing')
        ->and($showing->id()->named())->toBe('c1')
        ->and($showing->name())->toBe('NPO 1')
        ->and($showing->number())->toBe('1')
        ->and($silent->number())->toBe('');
});

it('names a channel as the core spells it, trimmed, and refuses one with no name', function (): void {
    expect(ChannelId::called(' c1 ')->named())->toBe('c1')
        ->and(ChannelId::called('c1')->is(ChannelId::called('c1')))->toBeTrue()
        ->and(ChannelId::called('c1')->is(ChannelId::called('c2')))->toBeFalse()
        ->and(static fn(): ChannelId => ChannelId::called('  '))->toThrow(ChannelIsUnnamed::class);
});

it('holds the channels in the order sent, and knows when it holds none', function (): void {
    $two = TheChannels::these(AChannel::withNothingSaid(ChannelId::called('b'), 'B', ''), AChannel::withNothingSaid(ChannelId::called('a'), 'A', ''));

    expect(array_map(static fn(AChannel $one): string => $one->name(), iterator_to_array($two, preserve_keys: false)))->toBe(['B', 'A'])
        ->and($two->isEmpty())->toBeFalse()
        ->and(TheChannels::these()->isEmpty())->toBeTrue();
});
