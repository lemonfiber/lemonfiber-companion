<?php

declare(strict_types=1);

namespace Modules\Household\Tests\Internal\Presenters;

use function array_map;
use function expect;
use function it;

use Modules\Household\Internal\Presenters\HowTheChannelsRead;
use Modules\Household\Internal\ViewModels\WhatAChannelCameTo;
use Modules\Household\Internal\ViewModels\WhatOneChannelSays;
use Modules\Kernel\Api\AChannel;
use Modules\Kernel\Api\AProgramme;
use Modules\Kernel\Api\ChannelId;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\TheChannels;
use Modules\Kernel\Api\WhenItSays;
use Modules\Kernel\Api\Zone;

it('reads each channel in the core\'s order, with what is on until when on the phone\'s clock', function (): void {
    $read = new HowTheChannelsRead(Zone::named('Europe/Amsterdam'))->these(TheChannels::these(
        AChannel::showing(ChannelId::called('c1'), 'NPO 1', '1', AProgramme::of('The news', WhenItSays::at(Instant::atEpochSeconds(1_893_443_400)), '6')),
        AChannel::showing(ChannelId::called('c2'), 'NPO 2', '2', AProgramme::of('A film', WhenItSays::unsaid(), '')),
        AChannel::withNothingSaid(ChannelId::called('c3'), 'Radio', ''),
    ));

    expect(array_map(static fn(WhatOneChannelSays $one): array => [$one->id, $one->name, $one->number, $one->programme, $one->until, $one->certificate, $one->tap], $read->channels))->toBe([
        ['c1', 'NPO 1', '1', 'The news', '21:30', '6', 'tuneTo(\'c1\')'],
        ['c2', 'NPO 2', '2', 'A film', '', '', 'tuneTo(\'c2\')'],
        ['c3', 'Radio', '', '', '', '', 'tuneTo(\'c3\')'],
    ])
        ->and($read->isDrawn())->toBeTrue()
        ->and($read->nameOf('c2'))->toBe('NPO 2')
        ->and($read->nameOf('c9'))->toBe('c9')
        ->and($read->came)->toBeNull();
});

it('draws no shelf with no channel, and says what came of a press only where something did', function (): void {
    $read = new HowTheChannelsRead(Zone::utc())->these(TheChannels::these());

    expect($read->isDrawn())->toBeFalse()
        ->and($read->after(WhatAChannelCameTo::nothing())->came)->toBeNull()
        ->and($read->after(WhatAChannelCameTo::inOurWords('NPO 1', ''))->came)->toBeNull()
        ->and($read->after(WhatAChannelCameTo::said('NPO 1', 'Only at home.'))->came?->inTheHousesWords)->toBe('Only at home.')
        ->and($read->after(WhatAChannelCameTo::inOurWords('NPO 1', 'household.play.no_player'))->came?->key)->toBe('household.play.no_player');
});
