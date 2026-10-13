<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APluginReproof;
use Modules\Kernel\Api\PluginSaysNothing;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheProofs;

it('reads a proof again as a reading until it is asked, and as cleared only where it was asked and cleared', function (): void {
    $reading = APluginReproof::reported('tdarr', TheProofs::these(), TheAnswersOutOfContract::these(), asked: false, cleared: true);
    $held = APluginReproof::reported('tdarr', TheProofs::these(), TheAnswersOutOfContract::these(), asked: true, cleared: true);
    $kept = APluginReproof::reported('tdarr', TheProofs::these(), TheAnswersOutOfContract::these(), asked: true, cleared: false);

    expect([$reading->isAReading(), $reading->wasCleared()])->toBe([true, false])
        ->and([$held->isAReading(), $held->wasCleared()])->toBe([false, true])
        ->and([$kept->isAReading(), $kept->wasCleared()])->toBe([false, false])
        ->and($held->plugin())->toBe('tdarr');
});

it('refuses a proof again that names no plugin', function (): void {
    expect(static fn(): APluginReproof => APluginReproof::reported(' ', TheProofs::these(), TheAnswersOutOfContract::these(), asked: false, cleared: false))
        ->toThrow(PluginSaysNothing::class, 'plugin');
});
