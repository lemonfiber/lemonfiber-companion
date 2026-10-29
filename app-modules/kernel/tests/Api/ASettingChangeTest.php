<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASettingChange;

it('names the setting it changes with the spaces around it left off, whichever way it changes it', function (): void {
    expect([ASettingChange::toHold(' vpn.provider ', 'mullvad')->setting(), ASettingChange::backToDefault(' vpn.provider ')->setting()])->toBe(['vpn.provider', 'vpn.provider']);
});
