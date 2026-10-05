<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\ThereIsNothingToAgreeTo;
use Modules\Kernel\Api\WhatNothingFills;

/** A choice of filler the stack worked out and wrote nothing for. */
function aFillToAgreeTo(): AFill
{
    return AFill::read(Capability::called('media-server'), ServiceId::called('plex'), Services::these(ServiceId::called('jellyfin')), Services::these(ServiceId::called('seerr')), WhatNothingFills::none(), '', '1a2b3c4d-5e6f7a8b-9c0d1e2f-0a0b0c0d');
}

it('agrees to the choice that was shown, naming its reading, with the reason trimmed', function (): void {
    $agreed = AFillAgreed::after(aFillToAgreeTo(), '  Plex plays the 4K files  ');

    expect($agreed->capability()->named())->toBe('media-server')
        ->and($agreed->service()->named())->toBe('plex')
        ->and($agreed->offer())->toBe('1a2b3c4d-5e6f7a8b-9c0d1e2f-0a0b0c0d')
        ->and($agreed->reason())->toBe('Plex plays the 4K files');
});

it('takes a blank reason as none', function (): void {
    expect(AFillAgreed::after(aFillToAgreeTo(), "  \t ")->reason())->toBe('');
});

it('has nothing to agree to where the choice was already made', function (): void {
    $made = AFill::made(Capability::called('indexer'), ServiceId::called('prowlarr'), Services::none(), Services::none(), WhatNothingFills::none(), '', 'cafe0001');

    expect(static fn(): AFillAgreed => AFillAgreed::after($made, ''))->toThrow(ThereIsNothingToAgreeTo::class, 'already made');
});
