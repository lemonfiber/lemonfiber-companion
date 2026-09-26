<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhatToDoWithACopy;

it('asks for each by the name lemonfiber gives the action', function (): void {
    expect(WhatToDoWithACopy::Take->asked())->toBe('backup')
        ->and(WhatToDoWithACopy::PutBack->asked())->toBe('restore');
});
