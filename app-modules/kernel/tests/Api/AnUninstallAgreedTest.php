<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\UninstallWasNotSurveyed;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhichRemoval;

/** The reading of the library, on a volume that unplugs where one is said. */
function theLibraryReading(string $volume): WhatTakingItOffComesTo
{
    return WhatTakingItOffComesTo::read(
        WhichRemoval::Media,
        WhatGoesAndWhatStays::said(
            'The library and the downloads',
            'Nothing',
        ),
        WhatItReaches::of(),
        4096,
        WhatToKnowFirst::said(
            WhatIsNotLemonfibers::of(),
            WhatIsStillComing::of(),
            WhatItCannotTake::of(),
            volume: $volume,
        ),
        HowMuchWasRead::everything(),
        'media-4096',
    );
}

it('carries the tier, the reading\'s name and whether to wait', function (): void {
    $agreed = AnUninstallAgreed::after(AnUninstall::of(theLibraryReading(''), WhereTakingItOffGot::surveyed()), WhetherToWait::ForTheDownloads, acknowledgedTheVolume: false);

    expect($agreed->tier())->toBe(WhichRemoval::Media)
        ->and($agreed->agreement())->toBe('media-4096')
        ->and($agreed->waiting())->toBe(WhetherToWait::ForTheDownloads);
});

it('agrees to a reading on a volume that unplugs once the volume is acknowledged', function (): void {
    $agreed = AnUninstallAgreed::after(AnUninstall::of(theLibraryReading('On a drive that unplugs'), WhereTakingItOffGot::surveyed()), WhetherToWait::GoAheadNow, acknowledgedTheVolume: true);

    expect($agreed->waiting())->toBe(WhetherToWait::GoAheadNow);
});

it('refuses a reading on a volume that unplugs when the volume was not acknowledged', function (): void {
    expect(fn(): AnUninstallAgreed => AnUninstallAgreed::after(AnUninstall::of(theLibraryReading('On a drive that unplugs'), WhereTakingItOffGot::surveyed()), WhetherToWait::GoAheadNow, acknowledgedTheVolume: false))
        ->toThrow(UninstallWasNotSurveyed::class, 'volume being acknowledged');
});

it('refuses an answer that was not a reading, however far it got', function (): void {
    foreach ([
        WhereTakingItOffGot::rehearsed(),
        WhereTakingItOffGot::complete(NamedOnTheManifest::under('gone'), NamedOnTheManifest::under('credentials')),
    ] as $got) {
        expect(fn(): AnUninstallAgreed => AnUninstallAgreed::after(AnUninstall::of(theLibraryReading(''), $got), WhetherToWait::GoAheadNow, acknowledgedTheVolume: true))
            ->toThrow(UninstallWasNotSurveyed::class, 'not a reading');
    }
});
