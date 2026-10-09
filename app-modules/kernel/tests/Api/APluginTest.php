<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\TheRecipes;
use Modules\Kernel\Api\WhatVouchesForAPlugin;
use Tests\Support\APluginAsItArrives;

it('offers an update only where its record names the source it came from', function (): void {
    expect(APluginAsItArrives::held()->canBeUpdated())->toBeTrue()
        ->and(APlugin::named('tdarr', '2.1.0', 'Tdarr', WhatVouchesForAPlugin::recorded('', '', '', reviewed: false, upstream: '', licence: ''), TheRecipes::these())->canBeUpdated())->toBeFalse();
});
