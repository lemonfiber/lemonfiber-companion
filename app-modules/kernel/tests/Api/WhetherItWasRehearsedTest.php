<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherItWasRehearsed;

it('reads the wire\'s yes as a rehearsal and its no as the thing itself', function (): void {
    expect(WhetherItWasRehearsed::said(rehearsed: true))->toBe(WhetherItWasRehearsed::Rehearsed)
        ->and(WhetherItWasRehearsed::said(rehearsed: false))->toBe(WhetherItWasRehearsed::CarriedOut);
});
