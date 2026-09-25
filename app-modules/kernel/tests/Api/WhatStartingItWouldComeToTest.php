<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;

use Modules\Kernel\Api\AFootprint;
use Modules\Kernel\Api\AServiceLeftOut;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\Forms;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheRehearsalSaysNothing;
use Modules\Kernel\Api\TheServicesLeftOut;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatTheRehearsalFound;

/** One word carried out of an `either()` arm. */
final readonly class WhatARehearsalAnswered
{
    public function __construct(public string $said) {}
}

function qBittorrentLeftOut(): AServiceLeftOut
{
    return AServiceLeftOut::needing(ServiceId::called('qbittorrent'), 'qBittorrent', WhatItWouldNeed::Torrent, Forms::these(Form::called('hunt')));
}

it('keeps what would start, what would be left out and the estimate, in the order given', function (): void {
    $rehearsal = WhatStartingItWouldComeTo::rehearsed(
        Services::these(ServiceId::called('sabnzbd')),
        TheServicesLeftOut::of(
            qBittorrentLeftOut(),
            AServiceLeftOut::needing(ServiceId::called('nzbget'), 'NZBGet', WhatItWouldNeed::Usenet, Forms::none()),
        ),
        AFootprint::estimated(700, Services::these(ServiceId::called('sonarr'))),
    );
    $leftOut = [...$rehearsal->leftOut()];

    expect($rehearsal->wouldStart()->count())->toBe(1)
        ->and($rehearsal->leftOut())->toHaveCount(2)
        ->and([$leftOut[0]->id()->named(), $leftOut[0]->name(), $leftOut[0]->needs(), $leftOut[0]->askedBy()->count()])
        ->toBe(['qbittorrent', 'qBittorrent', WhatItWouldNeed::Torrent, 1])
        ->and([$leftOut[1]->name(), $leftOut[1]->needs()])->toBe(['NZBGet', WhatItWouldNeed::Usenet])
        ->and($rehearsal->footprint()->mebibytes())->toBe(700)
        ->and($rehearsal->footprint()->unestimated()->count())->toBe(1);
});

it('keeps the services left out as a list however they are handed, and knows which they are', function (): void {
    $leftOut = TheServicesLeftOut::of(...['a' => qBittorrentLeftOut()]);

    expect(array_keys([...$leftOut]))->toBe([0])
        ->and($leftOut->include(ServiceId::called('qbittorrent')))->toBeTrue()
        ->and($leftOut->include(ServiceId::called('sonarr')))->toBeFalse();
});

it('refuses a service left out that is named as nothing, and an estimate below nothing', function (): void {
    expect(fn(): AServiceLeftOut => AServiceLeftOut::needing(ServiceId::called('qbittorrent'), ' ', WhatItWouldNeed::Usenet, Forms::none()))
        ->toThrow(TheRehearsalSaysNothing::class, '`name`')
        ->and(fn(): AFootprint => AFootprint::estimated(-1, Services::none()))
        ->toThrow(TheRehearsalSaysNothing::class, '`estimated_mib`');
});

it('takes an estimate of nothing, which a stack whose services declare none sends', function (): void {
    expect(AFootprint::estimated(0, Services::none())->mebibytes())->toBe(0);
});

it('says what each need is called on a screen', function (): void {
    expect(WhatItWouldNeed::Usenet->saidOnTheScreen())->toBe('health.rehearsal.needs.usenet')
        ->and(WhatItWouldNeed::Torrent->saidOnTheScreen())->toBe('health.rehearsal.needs.torrent');
});

it('answers the arm it was built with', function (): void {
    $rehearsal = WhatStartingItWouldComeTo::rehearsed(Services::none(), TheServicesLeftOut::of(), AFootprint::estimated(0, Services::none()));
    $found = static fn(WhatStartingItWouldComeTo $said): WhatARehearsalAnswered => new WhatARehearsalAnswered($said === $rehearsal ? 'found' : 'another');
    $met = static fn(Obstacle $why): WhatARehearsalAnswered => new WhatARehearsalAnswered($why->value);

    expect(WhatTheRehearsalFound::found($rehearsal)->either($found, $met)->said)->toBe('found')
        ->and(WhatTheRehearsalFound::met(Obstacle::StackDidNotAnswer)->either($found, $met)->said)->toBe(Obstacle::StackDidNotAnswer->value);
});
