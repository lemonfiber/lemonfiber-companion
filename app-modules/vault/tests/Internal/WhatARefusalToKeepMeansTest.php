<?php

declare(strict_types=1);

namespace Modules\Vault\Tests\Internal;

use function expect;
use function it;

use Lemonfiber\Native\WhyNothingWasKept;
use Modules\Kernel\Api\WhySessionCannotBeKept;
use Modules\Vault\Internal\WhatARefusalToKeepMeans;

it('reads each of the device\'s refusals as the kernel names it', function (): void {
    expect(WhatARefusalToKeepMeans::of(WhyNothingWasKept::NoStoreOnThisDevice))->toBe(WhySessionCannotBeKept::DeviceHasNoSecureStorage)
        ->and(WhatARefusalToKeepMeans::of(WhyNothingWasKept::StoreWouldNotOpen))->toBe(WhySessionCannotBeKept::StoreWouldNotOpen);
});
