<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Unfilled;

it('carries both the capability nothing fills and the service still asking', function (): void {
    // Two facts that only mean something together. A list of capability names
    // alone says something is missing without saying who notices, and who
    // notices is what an operator needs to decide whether to care.
    $unfilled = Unfilled::of(ServiceId::called('bazarr'), Capability::called('subtitle-provider'));

    expect($unfilled->asking()->named())->toBe('bazarr')
        ->and($unfilled->capability()->named())->toBe('subtitle-provider');
});
