<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use LogicException;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ChangesAndWhy;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\TheVersionsItMovesBetween;
use Modules\Kernel\Api\WhatPuttingTheOldVersionBackCameTo;
use Modules\Kernel\Api\WhatWentBack;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Tests\Support\APluginAsItArrives;
use Tests\Support\TheWordCarriedOut;

/** The update an answer is about. */
function theUpdateIn(ThePlugins $plugins): AnUpdate
{
    $update = null;
    $plugins->either(
        listed: static fn(): TheWordCarriedOut => new TheWordCarriedOut(''),
        install: static fn(): TheWordCarriedOut => new TheWordCarriedOut(''),
        update: static function (AnUpdate $about) use (&$update): TheWordCarriedOut {
            $update = $about;

            return new TheWordCarriedOut('');
        },
        removal: static fn(): TheWordCarriedOut => new TheWordCarriedOut(''),
    );

    return $update ?? throw new LogicException('Not an update.');
}

/** A reversal with nothing in it, rehearsed or carried out. */
function aReversalOfNothing(WhetherItWasRehearsed $rehearsed): ARunPutBack
{
    return ARunPutBack::reported($rehearsed, WhatWentBack::these(), ChangesAndWhy::these(), ChangesAndWhy::these());
}

it('reads an update as a reading only while going back is rehearsed and the new version has not held', function (): void {
    $reading = theUpdateIn(APluginAsItArrives::theUpdateReading());
    $notHeld = theUpdateIn(APluginAsItArrives::theUpdateNotHeld());

    expect($reading->isAReading())->toBeTrue()
        ->and($reading->held())->toBeFalse()
        ->and($reading->stopped())->toBe('')
        ->and($reading->restored()->wasNeeded())->toBeFalse()
        ->and($notHeld->isAReading())->toBeFalse()
        ->and($notHeld->held())->toBeFalse()
        ->and($notHeld->stopped())->toBe('The container would not start')
        ->and($notHeld->restored()->version())->toBe('2.1.0')
        ->and($notHeld->restored()->isPlaced())->toBeTrue()
        ->and($notHeld->restored()->isRunning())->toBeFalse()
        ->and($reading->versions()->from())->toBe('2.1.0')
        ->and($reading->versions()->to())->toBe('2.2.0');
});

it('refuses an update that names no plugin, versions that name either blank, and trims the reason it stopped', function (): void {
    $install = theUpdateIn(APluginAsItArrives::theUpdateReading())->install();
    $wentBack = aReversalOfNothing(WhetherItWasRehearsed::Rehearsed);
    $versions = TheVersionsItMovesBetween::of('2.1.0', '2.2.0');

    expect(static fn(): AnUpdate => AnUpdate::reported(' ', $versions, PluginLines::none(), $install, $wentBack, '', WhatPuttingTheOldVersionBackCameTo::notNeeded()))
        ->toThrow(PluginSaysNothing::class)
        ->and(static fn(): TheVersionsItMovesBetween => TheVersionsItMovesBetween::of(' ', '2.2.0'))->toThrow(PluginSaysNothing::class, 'from')
        ->and(static fn(): TheVersionsItMovesBetween => TheVersionsItMovesBetween::of('2.1.0', ''))->toThrow(PluginSaysNothing::class, 'to')
        ->and(AnUpdate::reported('tdarr', $versions, PluginLines::none(), $install, $wentBack, '  It would not start  ', WhatPuttingTheOldVersionBackCameTo::notNeeded())->stopped())->toBe('It would not start')
        ->and(static fn(): WhatPuttingTheOldVersionBackCameTo => WhatPuttingTheOldVersionBackCameTo::of(' ', placed: true, running: true))->toThrow(PluginSaysNothing::class);
});
