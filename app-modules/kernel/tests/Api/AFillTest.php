<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\FillSaysNothing;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;

/** A choice of filler the stack worked out, with what it leaves unfilled. */
function aFillWorkedOut(string $agreement = '1a2b3c4d-5e6f7a8b-9c0d1e2f-0a0b0c0d'): AFill
{
    return AFill::read(
        Capability::called('media-server'),
        ServiceId::called('plex'),
        Services::these(ServiceId::called('jellyfin')),
        Services::these(ServiceId::called('seerr'), ServiceId::called('bazarr')),
        WhatNothingFills::these(Unfilled::of(ServiceId::called('tdarr'), Capability::called('transcoder'))),
        '',
        $agreement,
    );
}

it('carries every part of the reading as the stack worked it out', function (): void {
    $fill = aFillWorkedOut();

    expect($fill->capability()->named())->toBe('media-server')
        ->and($fill->now()->named())->toBe('plex')
        ->and($fill->was()->count())->toBe(1)
        ->and($fill->askedBy()->count())->toBe(2)
        ->and($fill->leaves()->count())->toBe(1)
        ->and($fill->why())->toBe('')
        ->and($fill->agreement())->toBe('1a2b3c4d-5e6f7a8b-9c0d1e2f-0a0b0c0d')
        ->and($fill->wasMade())->toBeFalse();
});

it('says a choice the stack made was made, with the reason it recorded and nothing answering before', function (): void {
    $made = AFill::made(Capability::called('indexer'), ServiceId::called('prowlarr'), Services::none(), Services::these(ServiceId::called('radarr')), WhatNothingFills::none(), 'Jackett is too slow here', 'cafe0001');

    expect($made->wasMade())->toBeTrue()
        ->and($made->was()->isEmpty())->toBeTrue()
        ->and($made->why())->toBe('Jackett is too slow here');
});

it('refuses a reading with no name, which no yes could quote', function (): void {
    expect(static fn(): AFill => aFillWorkedOut('  '))->toThrow(FillSaysNothing::class, '`agreement`')
        ->and(static fn(): AFill => AFill::made(Capability::called('indexer'), ServiceId::called('prowlarr'), Services::none(), Services::none(), WhatNothingFills::none(), '', ''))->toThrow(FillSaysNothing::class);
});
