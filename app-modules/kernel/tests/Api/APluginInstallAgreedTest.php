<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginInstallAgreed;
use Modules\Kernel\Api\APluginSource;
use Modules\Kernel\Api\PluginInstallWasNotRehearsed;
use Modules\Kernel\Api\PluginLines;
use Tests\Support\APluginAsItArrives;

it('is agreed only against a reading, never against the listing or what an install came to', function (): void {
    foreach ([APluginAsItArrives::theListing(), APluginAsItArrives::theInstall(), APluginAsItArrives::thePutBack()] as $notAReading) {
        expect(static fn(): APluginInstallAgreed => APluginInstallAgreed::after($notAReading, APluginSource::typed('tdarr'), PluginLines::none()))
            ->toThrow(PluginInstallWasNotRehearsed::class);
    }

    expect(APluginInstallAgreed::after(APluginAsItArrives::theReading(), APluginSource::typed('tdarr'), PluginLines::none())->source()->said())->toBe('tdarr');
});
