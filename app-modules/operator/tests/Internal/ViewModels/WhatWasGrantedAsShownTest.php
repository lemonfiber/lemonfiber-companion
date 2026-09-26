<?php

declare(strict_types=1);

namespace Modules\Operator\Tests\Internal\ViewModels;

use function expect;
use function it;

use Modules\Operator\Internal\ViewModels\WhatWasGrantedAsShown;

it('carries no row of access where the invitation wrote nothing about it', function (): void {
    $nothing = WhatWasGrantedAsShown::nothing();

    expect([$nothing->wroteNothing, $nothing->libraries, $nothing->limit, $nothing->unratedSaid, $nothing->requestingSaid, $nothing->filtering])
        ->toBe([true, [], '', '', '', '']);
});
