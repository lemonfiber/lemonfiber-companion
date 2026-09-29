<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\UninstallSaysNothing;
use Modules\Kernel\Api\WhatGoesAndWhatStays;

it('carries each half as the stack said it', function (): void {
    $words = WhatGoesAndWhatStays::said(' The containers ', 'Everything else');

    expect($words->removes())->toBe(' The containers ')
        ->and($words->keeps())->toBe('Everything else');
});

it('refuses either half left blank, and names the half', function (): void {
    expect(fn(): WhatGoesAndWhatStays => WhatGoesAndWhatStays::said(' ', 'Everything else'))->toThrow(UninstallSaysNothing::class, 'its `removes` blank')
        ->and(fn(): WhatGoesAndWhatStays => WhatGoesAndWhatStays::said('', 'Everything else'))->toThrow(UninstallSaysNothing::class, 'its `removes` blank')
        ->and(fn(): WhatGoesAndWhatStays => WhatGoesAndWhatStays::said('The containers', ' '))->toThrow(UninstallSaysNothing::class, 'its `keeps` blank')
        ->and(fn(): WhatGoesAndWhatStays => WhatGoesAndWhatStays::said('The containers', ''))->toThrow(UninstallSaysNothing::class, 'its `keeps` blank');
});
