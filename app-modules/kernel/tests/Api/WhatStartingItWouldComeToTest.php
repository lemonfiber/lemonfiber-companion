<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function array_keys;
use function expect;
use function it;

use Modules\Kernel\Api\AProfileLeftOut;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheProfilesLeftOut;
use Modules\Kernel\Api\TheRehearsalSaysNothing;
use Modules\Kernel\Api\WhatItWouldNeed;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatTheRehearsalFound;

/** One word carried out of an `either()` arm. */
final readonly class WhatARehearsalAnswered
{
    public function __construct(public string $said) {}
}

it('keeps what would start and what would be left out, in the order given', function (): void {
    $rehearsal = WhatStartingItWouldComeTo::rehearsed(
        Services::these(ServiceId::called('sabnzbd')),
        TheProfilesLeftOut::of(
            AProfileLeftOut::needing('torrent', WhatItWouldNeed::Torrent),
            AProfileLeftOut::needing('nzb', WhatItWouldNeed::Usenet),
        ),
    );
    $leftOut = [...$rehearsal->leftOut()];

    expect($rehearsal->wouldStart()->count())->toBe(1)
        ->and($rehearsal->leftOut())->toHaveCount(2)
        ->and($leftOut[0]->profile())->toBe('torrent')
        ->and($leftOut[0]->needs())->toBe(WhatItWouldNeed::Torrent)
        ->and($leftOut[1]->profile())->toBe('nzb')
        ->and($leftOut[1]->needs())->toBe(WhatItWouldNeed::Usenet);
});

it('keeps the profiles left out as a list however they are handed', function (): void {
    $leftOut = TheProfilesLeftOut::of(...['a' => AProfileLeftOut::needing('torrent', WhatItWouldNeed::Torrent)]);

    expect(array_keys([...$leftOut]))->toBe([0]);
});

it('refuses a profile left out that is named as nothing', function (): void {
    expect(fn(): AProfileLeftOut => AProfileLeftOut::needing(' ', WhatItWouldNeed::Usenet))
        ->toThrow(TheRehearsalSaysNothing::class, '`profile`');
});

it('says what each need is called on a screen', function (): void {
    expect(WhatItWouldNeed::Usenet->saidOnTheScreen())->toBe('health.rehearsal.needs.usenet')
        ->and(WhatItWouldNeed::Torrent->saidOnTheScreen())->toBe('health.rehearsal.needs.torrent');
});

it('answers the arm it was built with', function (): void {
    $rehearsal = WhatStartingItWouldComeTo::rehearsed(Services::none(), TheProfilesLeftOut::of());
    $found = static fn(WhatStartingItWouldComeTo $said): WhatARehearsalAnswered => new WhatARehearsalAnswered($said === $rehearsal ? 'found' : 'another');
    $met = static fn(Obstacle $why): WhatARehearsalAnswered => new WhatARehearsalAnswered($why->value);

    expect(WhatTheRehearsalFound::found($rehearsal)->either($found, $met)->said)->toBe('found')
        ->and(WhatTheRehearsalFound::met(Obstacle::StackDidNotAnswer)->either($found, $met)->said)->toBe(Obstacle::StackDidNotAnswer->value);
});
