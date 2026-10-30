<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\ServiceId;

it('holds the variable a service reads a setting under with the spaces around it left off', function (): void {
    expect(AServiceReadingIt::of(ServiceId::called('caddy'), ' TOKEN ', ASettingValue::nothing())->variable())->toBe('TOKEN');
});
