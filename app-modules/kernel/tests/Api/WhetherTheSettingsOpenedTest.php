<?php

declare(strict_types=1);

namespace Modules\Kernel\Tests\Api;

use ArrayObject;

use function expect;
use function it;

use Modules\Kernel\Api\WhetherTheSettingsOpened;

it('says which arm it took, and whether the phone would not open the page', function (): void {
    $arm = static fn(WhetherTheSettingsOpened $said): string => $said->either(
        opened: static fn(): ArrayObject => new ArrayObject(['opened' => true]),
        wouldNot: static fn(): ArrayObject => new ArrayObject(['would not' => true]),
    )->getIterator()->key();

    expect([$arm(WhetherTheSettingsOpened::opened()), $arm(WhetherTheSettingsOpened::wouldNot())])->toBe(['opened', 'would not'])
        ->and([WhetherTheSettingsOpened::opened()->wouldNotOpen(), WhetherTheSettingsOpened::wouldNot()->wouldNotOpen()])->toBe([false, true]);
});
