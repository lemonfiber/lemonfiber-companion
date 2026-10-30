<?php

declare(strict_types=1);

namespace Modules\Sdk\Tests\Internal;

use function expect;
use function it;

use Modules\Sdk\Internal\InTheOrderSent;

it('keys every row by its position from nought, a list that arrived as an object too', function (): void {
    expect(InTheOrderSent::rows(['plex' => 'first', 'tdarr' => 'second']))->toBe([0 => 'first', 1 => 'second'])
        ->and(InTheOrderSent::rows([3 => 'first', 7 => 'second']))->toBe([0 => 'first', 1 => 'second']);
});
