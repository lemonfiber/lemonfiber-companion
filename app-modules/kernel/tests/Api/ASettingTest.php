<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ASettingValue;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhoseASettingIs;

it('holds a setting under its name with the spaces around it left off', function (): void {
    expect(ASetting::read(' vpn.provider ', WhoseASettingIs::theCores(), TheServicesReadingIt::these(), ASettingValue::nothing())->name())->toBe('vpn.provider');
});
