<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\ASettingValue;

it('says a value exactly as written, and nothing as blank, telling the two apart', function (): void {
    expect([ASettingValue::value(' as written ')->said(), ASettingValue::value(' as written ')->isHeld()])->toBe([' as written ', true])
        ->and([ASettingValue::nothing()->said(), ASettingValue::nothing()->isHeld()])->toBe(['', false]);
});
