<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ACapabilityLeftUnfilled;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheCapabilitiesLeftUnfilled;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;

/** A reversal with nothing in it, rehearsed or carried out. */
function aRemovalReversalOfNothing(WhetherItWasRehearsed $rehearsed): ARunPutBack
{
    return ARunPutBack::reported($rehearsed, WhatWentBack::these(), ChangesAndWhy::these(), ChangesAndWhy::these());
}

it('reads a removal as removed only where the record was written, and as partial where its files went back and the record was not', function (): void {
    $leaves = TheCapabilitiesLeftUnfilled::these(ACapabilityLeftUnfilled::of('transcode', 'tdarr'));
    $reading = APluginRemoval::reported('tdarr', PluginLines::none(), $leaves, removed: false, wentBack: aRemovalReversalOfNothing(WhetherItWasRehearsed::Rehearsed));
    $whole = APluginRemoval::reported('tdarr', PluginLines::none(), $leaves, removed: true, wentBack: aRemovalReversalOfNothing(WhetherItWasRehearsed::CarriedOut));
    $notWritten = APluginRemoval::reported('tdarr', PluginLines::none(), $leaves, removed: false, wentBack: aRemovalReversalOfNothing(WhetherItWasRehearsed::CarriedOut));

    expect([$reading->isAReading(), $reading->wasRemoved(), $reading->isPartial()])->toBe([true, false, false])
        ->and([$whole->isAReading(), $whole->wasRemoved(), $whole->isPartial()])->toBe([false, true, false])
        ->and([$notWritten->isAReading(), $notWritten->wasRemoved(), $notWritten->isPartial()])->toBe([false, false, true]);
});

it('refuses a removal that names no plugin, and a capability left with nothing named', function (): void {
    expect(static fn(): APluginRemoval => APluginRemoval::reported(' ', PluginLines::none(), TheCapabilitiesLeftUnfilled::these(), removed: false, wentBack: aRemovalReversalOfNothing(WhetherItWasRehearsed::Rehearsed)))
        ->toThrow(PluginSaysNothing::class)
        ->and(static fn(): ACapabilityLeftUnfilled => ACapabilityLeftUnfilled::of('', 'tdarr'))->toThrow(PluginSaysNothing::class, 'capability')
        ->and(static fn(): ACapabilityLeftUnfilled => ACapabilityLeftUnfilled::of('transcode', ''))->toThrow(PluginSaysNothing::class, 'filled_by');
});
